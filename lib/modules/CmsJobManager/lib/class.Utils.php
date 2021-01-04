<?php
/*
Utility-methods for CMS Made Simple module: CmsJobManager
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file CmsJobManager.module.php
*/
namespace CmsJobManager;

use BadMethodCallException;
use CmsJobManager;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Async\CronJob;
use CMSMS\Async\Job;
use CMSMS\Async\RecurType;
use CMSMS\Async\RegularJob;
use CMSMS\IRegularTask;
use CMSMS\ModuleOperations;
use CMSMS\SysDataCacheDriver;
use CMSMS\Utils as AppUtils;
use CmsRegularTask;
use InvalidArgumentException;
use LogicException;
use Throwable;
use const ASYNCLOG;
use const CMS_DB_PREFIX;
use const CMS_ROOT_PATH;
use const TMP_CACHE_LOCATION;
use function cms_join_path;
use function debug_to_log;

final class Utils
{
	const LOGFILE = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'cmsjobman_errjobs.log';

    /**
     * Get the interval between job-runs
     * @return int seconds
     */
    public static function get_async_freq() : int
    {
        return AppParams::get('jobinterval', 180); //seconds
    }

    /**
     * Check whether the specified Job recurs
     * @param Job $job
     * @return bool
     */
    public static function job_recurs(Job $job) : bool
    {
        if ($job instanceof CronJob) {
            return $job->frequency != RecurType::RECUR_NONE;
        }
        return false;
    }

    /**
     * Get a timestamp representing the earliest time when the specified
     * job will next be processed. Or 0 if it's not to be processed.
     * @param Job $job
     * @return int
     */
    public static function calculate_next_start_time(Job $job) : int
    {
        if (!self::job_recurs($job)) {
            return 0;
        }
        $now = $job->start;
        if ($now == 0) {
            return 0;
        }
        switch ($job->frequency) {
        case RecurType::RECUR_15M:
            $out = $now + 900;
            break;
        case RecurType::RECUR_30M:
            $out = $now + 1800;
            break;
        case RecurType::RECUR_HOURLY:
            $out = $now + 3600;
            break;
        case RecurType::RECUR_120M:
            $out = $now + 7200;
            break;
        case RecurType::RECUR_180M:
            $out = $now + 10800;
            break;
        case RecurType::RECUR_12H:
            $out = $now + 43200;
            break;
        case RecurType::RECUR_DAILY:
            $out = strtotime('+1 day', $now);
            break;
        case RecurType::RECUR_WEEKLY:
            $out = strtotime('+1 week', $now);
            break;
        case RecurType::RECUR_MONTHLY:
            $out = strtotime('+1 month', $now);
            break;
        case RecurType::RECUR_ALWAYS:
            $out = $now;
            break;
//      case RecurType::RECUR_ONCE:
        default:
            $out = 0;
            break;
        }
        if ($out) {
            $out = max($out, time()+1); // next start cannot be < time()
            if (!$job->until || $out <= $job->until) {
                debug_to_log("adjusted to $out -- $now // {$job->until}");
//                $d = $out - $now;
//                error_log($job->name." next start @ last-start + $d)"."\n", 3, ASYNCLOG);
                return $out;
            }
        }
        return 0;
    }

    /**
     * Transfer the file-stored job-errors log data to the database
     */
    public static function process_errors()
    {
        $fn = self::LOGFILE;
        if (!is_file($fn)) {
            return;
        }

        $data = file_get_contents($fn);
        @unlink($fn);
        if (!$data) {
            return;
        }

        $tmp = explode("\n", $data);
        if (!is_array($tmp) || !count($tmp)) {
            return;
        }

        $job_ids = [];
        foreach ($tmp as $one) {
            $one = (int) $one;
            if ($one < 1) {
                continue;
            }
            if (!in_array($one, $job_ids)) {
                $job_ids[] = $one;
            }
        }

        // we have job(s) whose error count needs to be increased
        $db = AppSingle::Db();
        $sql = 'UPDATE '.CmsJobManager::TABLE_NAME.' SET errors = errors + 1 WHERE id IN ('.implode(',', $job_ids).')';
        $db->Execute($sql);
        debug_to_log('Increased error count on '.count($job_ids).' jobs ');
    }

    /**
     * Record (in a file) the id of a job where an error occurred, for later processing
     * @param int $job_id
     */
    public static function put_error(int $job_id)
    {
        $fh = fopen(self::LOGFILE, 'a');
        fwrite($fh, $job_id."\n");
        fclose($fh);
    }

    /**
     * @param mixed $job
     * @param string $errmsg
     * @param string $errfile
     * @param string $errline
     */
    public static function joberrorhandler($job, string $errmsg, string $errfile, string $errline)
    {
        debug_to_log('Fatal error occurred processing async jobs at: '.$errfile.':'.$errline);
        debug_to_log('Msg: '.$errmsg);

        if (is_object($job)) {
            self::put_error($job->id);
        }
    }

    /**
     * Shutdown function
     */
    public static function errorhandler()
    {
        $err = error_get_last();
        if (is_null($err)) {
            return;
        }
        if ($err['type'] != E_ERROR) {
            return;
        }
        $mod = ModuleOperations::get_instance()->get_module_instance('CmsJobManager');
        $job = $mod->get_current_job();
        if ($job) {
            self::joberrorhandler($job, $err['message'], $err['file'], $err['line']);
        }
    }

    /**
     * Populate or refresh the database tasks-store for each discovered
     * CmsRegularTask object and Job object
     *
     * @param bool $force optional flag whether to clear the store before polling. Default false
     * @return int count of job(s) processed
     */
    public static function refresh_jobs(bool $force = false) : int
    {
        $res = 0;

        if ($force) {
            $db = AppSingle::Db();
            $db->Execute('DELETE FROM '.CmsJobManager::TABLE_NAME);
            $db->Execute('ALTER TABLE '.CmsJobManager::TABLE_NAME.' AUTO_INCREMENT=1');
        }

        // Get job objects from files
        $patn = cms_join_path(CMS_ROOT_PATH, 'lib', 'classes', 'tasks', 'class.*.php');
        $files = glob($patn);
        foreach ($files as $p) {
            $classname = 'CMSMS\\tasks\\';
            $tmp = explode('.', basename($p));
            if (count($tmp) == 4 && $tmp[2] == 'task') {
                $classname .= $tmp[1].'Task';
            } else {
                $classname .= $tmp[1];
            }
            require_once $p;
            try {
                $obj = new $classname();
                if ($obj instanceof CmsRegularTask || $obj instanceof IRegularTask) {
//                  if (!$obj->test($now)) continue; ALWAYS RECORD TASK
                    try {
                        $job = new RegularJob($obj);
                    } catch (Throwable $t) {
                        continue;
                    }
                } elseif ($obj instanceof Job) {
                    $job = $obj;
                } else {
                    continue;
                }
                if (self::load_job($job) > 0) {
                    ++$res;
                }
            } catch (Throwable $t) {
                continue;
            }
        }

        // Get job objects from modules
        $cache = AppSingle::SysDataCache();
        if (!$cache->get('modules')) {
            $obj = new SysDataCacheDriver('modules',
                function() {
                    $db = AppSingle::Db();
                    $query = 'SELECT * FROM '.CMS_DB_PREFIX.'modules';
                    return $db->GetArray($query);
                });
            $cache->add_cachable($obj);
        }

        $ops = ModuleOperations::get_instance();
        $modules = $ops->get_modules_with_capability('tasks');

        if (!$modules) {
            if (defined('ASYNCLOG')) {
                error_log('async action No task-capable modules present'."\n", 3, ASYNCLOG);
            }
            return $res;
        }
        foreach ($modules as $one) {
            if (!is_object($one)) {
                $one = AppUtils::get_module($one);
            }
            if (!method_exists($one, 'get_tasks')) {
                continue;
            }

            $tasks = $one->get_tasks();
            if (!$tasks) {
                continue;
            }
            if (!is_array($tasks)) {
                $tasks = [$tasks];
            }

            foreach ($tasks as $obj) {
                if (!is_object($obj)) {
                    continue;
                }
                if ($obj instanceof CmsRegularTask || $obj instanceof IRegularTask) {
//                    if (! $obj->test()) continue;  ALWAYS RECORD TASK
                    try {
                        $job = new RegularJob($obj);
                    } catch (Throwable $t) {
                        continue;
                    }
                } elseif ($obj instanceof Job) {
                    $job = $obj;
                } else {
                    continue;
                }
                $job->module = $one->GetName();
                if (self::load_job($job) > 0) {
                    ++$res;
                }
            }
        }
        return $res;
    }

    /**
     * Update or initialize the recorded data for the supplied Job, and if
     * relevant, update the Job's id-property
     * @param Job $job
     * @return int id of updated|inserted Job | 0 upon error
     */
    public static function load_job(Job $job) : int
    {
        $db = AppSingle::Db();
        if ($job->id == 0) {
            $sql = 'SELECT id,start FROM '.CmsJobManager::TABLE_NAME.' WHERE name = ? AND module = ?';
            $dbr = $db->GetRow($sql, [$job->name, $job->module]);
            if ($dbr) {
                if ($dbr['start'] > 0) {
                    $job->set_id((int)$dbr['id']);
                    $sql = 'UPDATE '.CmsJobManager::TABLE_NAME.' SET start = ? WHERE id = ?'; //update next-start
                    $db->Execute($sql, [$job->start, $job->id]);
                }
                return $job->id; // maybe still 0
            }
            $now = time();
            if (self::job_recurs($job)) {
                $start = min($job->start, $now);
                $recurs = $job->frequency;
                $until = $job->until;
            } else {
                $start = 0; //$job->start;
                $recurs = RecurType::RECUR_NONE;
                $until = 0;
            }
            $sql = 'INSERT INTO '.CmsJobManager::TABLE_NAME.' (name,created,module,errors,start,recurs,until,data) VALUES (?,?,?,?,?,?,?,?)';
            $dbr = $db->Execute($sql, [$job->name, $job->created, $job->module, $job->errors, $start, $recurs, $until, serialize($job)]);
            if ($dbr) {
                $new_id = $db->Insert_ID();
                try {
                    $job->set_id($new_id);
                    return $new_id;
                } catch (LogicException $e) {
                    // nothing here
                }
            }
            return 0;
        } else {
            // note... we don't play with the module, the data, or recurs/until stuff for existing jobs.
            $sql = 'UPDATE '.CmsJobManager::TABLE_NAME.' SET start = ? WHERE id = ?';
            $dbr = $db->Execute($sql, [$job->start, $job->id]);
            return ($db->affected_rows() > 0) ? $job->id : 0;
        }
    }

    /**
     * @throws InvalidArgumentException if $job_id is invalid
     * @throws ? if Job-unserialize() fails
     */
    public static function load_job_by_id($job_id)
    {
        $job_id = (int) $job_id;
        if ($job_id > 0) {
            $db = AppSingle::Db();
            $sql = 'SELECT * FROM '.CmsJobManager::TABLE_NAME.' WHERE id = ?';
            $row = $db->GetRow($sql, [$job_id]);
            if (!is_array($row) || !count($row)) {
                return;
            }

            $obj = unserialize($row['data']);
            $obj->set_id($row['id']);
            return $obj;
        }
        throw new InvalidArgumentException('Invalid job_id passed to '.__METHOD__);
    }

    public static function load_jobs_by_module($module)
    {
        if (!is_object($module)) {
            $module = AppUtils::get_module($module);
        }

        if (!method_exists($module, 'get_tasks')) {
            return;
        }
        $tasks = $module->get_tasks();
        if (!$tasks) {
            return;
        }

        if (!is_array($tasks)) {
            $tasks = [$tasks];
        }

        foreach ($tasks as $obj) {
            if (!is_object($obj)) {
                continue;
            }
            if ($obj instanceof CmsRegularTask) {
                $job = new RegularTask($obj);
            } elseif ($obj instanceof Job) {
                $job = $obj;
            } else {
                continue;
            }
            $job->module = $module->GetName();
            self::load_job($job);
        }
    }

    public static function unload_job(Job $job)
    {
        if ($job->id > 0) {
            $db = AppSingle::Db();
            $sql = 'DELETE FROM '.CmsJobManager::TABLE_NAME.' WHERE id = ?';
            if ($db->Execute($sql, [$job->id])) {
                return;
            }
        }
        throw new BadMethodCallException('Cannot delete a job that has no id');
    }

    public static function unload_job_by_id($job_id)
    {
        $job_id = (int) $job_id;
        if ($job_id > 0) {
            $db = AppSingle::Db();
            $sql = 'DELETE FROM '.CmsJobManager::TABLE_NAME.' WHERE id = ?';
            if ($db->Execute($sql, [$job_id])) {
                return;
            }
        }
        throw new InvalidArgumentException('Invalid job_id passed to '.__METHOD__);
    }

    public static function unload_job_by_name($module_name, $job_name)
    {
        if ($module_name) {
            $db = AppSingle::Db();
            $sql = 'DELETE FROM '.CmsJobManager::TABLE_NAME.' WHERE module = ? AND name = ?';
            if ($db->Execute($sql, [$module_name, $job_name])) {
                return;
            }
        }
        throw new InvalidArgumentException('Invalid identifier(s) passed to '.__METHOD__);
    }

    public static function unload_jobs_by_module($module_name)
    {
        if ($module_name) {
            $db = AppSingle::Db();
            $sql = 'DELETE FROM '.CmsJobManager::TABLE_NAME.' WHERE module = ?';
            $db->Execute($sql, [$module_name]); // don't care if this fails i.e. no jobs
            return;
        }
        throw new InvalidArgumentException('Invalid module name passed to '.__METHOD__);
    }
}
