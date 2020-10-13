<?php
# utility-methods for CmsJobManager, a CMS Made Simple module
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# See license details at the top of file CmsJobManager.module.php

namespace CmsJobManager;

use BadMethodCallException;
use CmsJobManager;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Async\CronJob;
use CMSMS\Async\Job;
use CMSMS\Async\RecurType;
use CMSMS\IRegularTask;
use CMSMS\ModuleOperations;
use CMSMS\SysDataCache;
use CMSMS\SysDataCacheDriver;
use CMSMS\Utils as AppUtils;
use CmsRegularTask;
use InvalidArgumentException;
use LogicException;
use Throwable;
use const ASYNCLOG;
use const CMS_ROOT_PATH;
use const TMP_CACHE_LOCATION;
use function cms_db_prefix;
use function debug_to_log;

final class Utils
{
    public static function get_async_freq() : int
    {
        $minutes = AppParams::get('jobinterval', 3);
        $minutes = max(3, min(60, (int)$minutes));
        return $minutes * 60; // seconds
    }
    /**
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
        $now = time();
        switch ($job->frequency) {
        case $job::RECUR_15M:
            $out = $now + 900;
            break;
        case $job::RECUR_30M:
            $out = $now + 1800;
            break;
        case $job::RECUR_HOURLY:
            $out = $now + 3600;
            break;
        case $job::RECUR_120M:
            $out = $now + 7200;
            break;
        case $job::RECUR_180M:
            $out = $now + 10800;
            break;
        case $job::RECUR_12H:
            $out = $now + 43200;
            break;
        case $job::RECUR_DAILY:
            $out = strtotime('+1 day', $now);
            break;
        case $job::RECUR_WEEKLY:
            $out = strtotime('+1 week', $now);
            break;
        case $job::RECUR_MONTHLY:
            $out = strtotime('+1 month', $now);
            break;
        case $job::RECUR_ALWAYS:
            $out = $now;
            break;
//      case $job::RECUR_ONCE:
        default:
            $out = 0;
            break;
        }
        if ($out) {
            debug_to_log("adjusted to {$out} -- {$now} // {$job->until}");
            if (!$job->until || $out <= $job->until) {
               return $out;
            }
        }
        return 0;
    }

    public static function process_errors()
    {
        $fn = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'cmsjobman_errjobs.log';
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

        // have jobs to increase error count on.
        $db = AppSingle::Db();
        $sql = 'UPDATE '.CmsJobManager::TABLE_NAME.' SET errors = errors + 1 WHERE id IN ('.implode(',', $job_ids).')';
        $db->Execute($sql);
        debug_to_log('Increased error count on '.count($job_ids).' jobs ');
    }

    /**
     * Record id of job where error occurred, for later processing
     * @param int $job_id
     */
    public static function put_error(int $job_id)
    {
        $fn = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'cmsjobman_errjobs.log';
        $fh = fopen($fn, 'a');
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

        if (!is_object($job)) {
            return;
        }
        self::put_error($job->id);
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
     * Load a job for each CmsRegularTask object and Job object that is found
     * @return int count of job(s) loaded
    */
    public static function refresh_jobs() : int
    {
        $res = 0;

        // Get job objects from files
        $patn = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR.'class.*.php';
        $files = glob($patn);
        foreach( $files as $p) {
            $tmp = explode('.',basename($p));
            if (count($tmp) == 4 && $tmp[2] == 'task') {
                $classname = $tmp[1].'Task';
            }
            else {
                $classname = $tmp[1];
            }
            require_once $p;
            $obj = new $classname;
            if ($obj instanceof CmsRegularTask || $obj instanceof IRegularTask) {
//              if (!$obj->test($now)) continue; ALWAYS RECORD TASK
                try {
                    $job = new RegularTask($obj);
                } catch (Throwable $t) {
                    continue;
                }
            }
            elseif ($obj instanceof Job) {
                $job = $obj;
            }
            else {
                continue;
            }
            if (self::load_job($job)) {
                ++$res;
            }
        }

        // Get job objects from modules
        if (!SysDataCache::get('modules')) {
            $obj = new SysDataCacheDriver('modules',
                function () {
                    $db = AppSingle::Db();
                    $query = 'SELECT * FROM '.cms_db_prefix().'modules';
                    return $db->GetArray($query);
                });
            SysDataCache::add_cachable($obj);
        }

        $ops = ModuleOperations::get_instance();
        $modules = $ops->get_modules_with_capability('tasks');

        if (!$modules) {
            if (defined('ASYNCLOG')) {
                error_log('async action No task-capable modules present'."\n", 3, ASYNCLOG);
            }
            return $res;
        }
        foreach( $modules as $one) {
            if (!is_object($one)) $one = Utils::get_module($one);
            if (!method_exists($one,'get_tasks')) continue;

            $tasks = $one->get_tasks();
            if (!$tasks) continue;
            if (!is_array($tasks)) $tasks = [$tasks];

            foreach( $tasks as $obj) {
                if (! is_object($obj)) continue;
                if ($obj instanceof CmsRegularTask || $obj instanceof IRegularTask) {
//                    if (! $obj->test()) continue;  ALWAYS RECORD TASK
                    try {
                        $job = new RegularTask($obj);
                    } catch (Throwable $t) {
                        continue;
                    }
                }
                elseif ($obj instanceof Job) {
                    $job = $obj;
                }
                else {
                    continue;
                }
                $job->module = $one->GetName();
                if (self::load_job($job)) {
                    ++$res;
                }
            }
        }
        return $res;
    }

    public static function load_job(Job $job)
    {
        $db = AppSingle::Db();
        if ($job->id == 0) {
            $sql = 'SELECT id FROM '.CmsJobManager::TABLE_NAME.' WHERE name = ? AND module = ?';
            $dbr = $db->GetOne($sql, [$job->name, $job->module]);
            if ($dbr) {
                $sql = 'UPDATE '.CmsJobManager::TABLE_NAME.' SET start = ? WHERE id = ?';
                $db->Execute($sql, [$job->start, $dbr]);
                return $dbr;
            }

            if (self::job_recurs($job)) {
                $recurs = $job->frequency;
                $until = $job->until;
            } else {
                $recurs = $until = null;
            }
            $sql = 'INSERT INTO '.CmsJobManager::TABLE_NAME.' (name,created,module,errors,start,recurs,until,data) VALUES (?,?,?,?,?,?,?,?)';
            $dbr = $db->Execute($sql, [$job->name, $job->created, $job->module, $job->errors, $job->start, $recurs, $until, serialize($job)]);
            $new_id = $db->Insert_ID();
            try {
                $job->set_id($new_id);
                return $new_id;
            } catch (LogicException $e) {
                return 0;
            }
        } else {
            // note... we do not ever play with the module, the data, or recurs/until stuff for existing jobs.
            $sql = 'UPDATE '.CmsJobManager::TABLE_NAME.' SET start = ? WHERE id = ?';
            $db->Execute($sql, [$job->start, $job->id]);
            return $job->id;
        }
    }

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
            if ($db->Execute($sql, [$module_name])) {
                return;
            }
        }
        throw new InvalidArgumentException('Invalid module name passed to '.__METHOD__);
    }
}
