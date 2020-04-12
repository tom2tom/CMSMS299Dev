<?php
# CmsJobManager: a core module for CMS Made Simple to allow management of
# asynchronous and cron jobs.
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CmsJobManager\JobQueue;
use CmsJobManager\utils;
use CMSMS\Async\AsyncJobManager;
use CMSMS\Async\Job;
use CMSMS\Async\RegularTask;
use CMSMS\ModuleOperations;

final class CmsJobManager extends CMSModule implements AsyncJobManager
{
    const LOCKPREF = 'lock';
    const ASYNCFREQ_PREF = 'asyncfreq';
    const MANAGE_JOBS = 'Manage Jobs';
    const EVT_ONFAILEDJOB = 'OnJobFailed';
    const TABLE_NAME = CMS_DB_PREFIX.'mod_cmsjobmgr';

    private $_current_job;
    private $_lock;

/*  public function __construct()
    {
        parent::__construct();
// why would this essentially-async module be a plugin ? anyway, ignored with lazy-loading. Just to force-load module each session ?
//        $this->RegisterModulePlugin();
    }
*/
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'siteadmin'; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetVersion() { return '0.3'; }
    public function HandlesEvents() { return TRUE; }
    public function HasAdmin() { return TRUE; }
    public function IsAdminOnly()  { return FALSE; }
//    public function IsPluginModule() { return TRUE; } //not actually a plugin, but trigger module load ??
//    public function LazyLoadAdmin() { return TRUE; }
//    public function LazyLoadFrontend() { return TRUE; }
    public function MinimumCMSVersion() { return '2.1.99'; }
    public function VisibleToAdminUser() { return $this->CheckPermission(self::MANAGE_JOBS); }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CmsCoreCapabilities::CORE_MODULE:
//          case CmsCoreCapabilities::PLUGIN_MODULE:
//          case CmsCoreCapabilities::TASKS:
            case CmsCoreCapabilities::JOBS_MODULE:
                return TRUE;
            default:
                return FALSE;
        }
    }

    public function DoEvent($originator, $eventname, &$params)
    {
        if ($originator == 'Core') {
            switch ($eventname) {
                case 'ModuleInstalled':
                case 'ModuleUninstalled':
                case 'ModuleUpgraded':
                    $this->check_for_jobs_or_tasks(TRUE);
            }
        }
        parent::DoEvent($originator, $eventname, $params);
    }

/*    public function InitializeFrontend()
   {
//2.3 does nothing        $this->RestrictUnknownParams();
    }
*/
    public function GetEventHelp($name)
    {
        return $this->Lang('evthelp_'.$name);
    }

    public function GetEventDescription($name)
    {
        return $this->Lang('evtdesc_'.$name);
    }

    protected function create_new_template($str)
    {
        $smarty = $this->GetActionTemplateObject();
        return $smarty->createTemplate($this->GetTemplateResource($str),null,null,$smarty);
    }

    public function get_current_job()
    {
        return $this->_current_job;
    }

    protected function set_current_job($job = null)
    {
        if (!(is_null($job) || $job instanceof Job)) {
            throw new InvalidArgumentException('Invalid data passed to '.__METHOD__);
        }
        $this->_current_job = $job;
    }

    protected function is_locked()
    {
        $this->_lock = (int) $this->GetPreference(self::LOCKPREF);
        return ($this->_lock > 0);
    }

    protected function lock_expired()
    {
        $this->_lock = (int) $this->GetPreference(self::LOCKPREF);
        return ($this->_lock && $this->_lock < time() - utils::get_async_freq());
    }

    protected function lock()
    {
        $this->_lock = time();
        $this->SetPreference(self::LOCKPREF,$this->_lock);
    }

    protected function unlock()
    {
        $this->_lock = null;
        $this->RemovePreference(self::LOCKPREF);
    }

    /**
     * 'deep' checks occur upon:
     * any module change (per $this->DoEvent())
     * any system upgrade - TODO initiated how ?
     * 12-hourly signature-check of <root>/lib/tasks dir (per WatchTasks task)
     * @param bool $deep
     * @return boolean whether an update was done
     */
    protected function check_for_jobs_or_tasks(bool $deep)
    {
        if (!$deep) {
            // this is cheaper
            if (JobQueue::get_jobs(TRUE)) {
                return TRUE;
            }
            $deep = TRUE;
        }

        // maybe check for tasks, which is more expensive
        if ($deep) {
            if ($this->create_jobs_from_eligible_tasks()) {
                return TRUE;
            }
        } else {
            $now = time();
            $lastcheck = (int) $this->GetPreference('last_check');
            if ($lastcheck < $now - 900) {
                $this->SetPreference('last_check',$now);
                if ($this->create_jobs_from_eligible_tasks()) {
                    return TRUE;
                }
            }
        }
        audit('','CmsJobManager','Found nothing to do');
        return FALSE;
    }

    /**
     * Create jobs from task-objects that need to be executed.
     *
     * @return bool
     */
    protected function create_jobs_from_eligible_tasks() : bool
    {
//        $now = time();
        $res = FALSE;

        // 1.  Get task objects from files in the tasks folder
        // fairly expensive to iterate a directory and load files and create objects.
        $patn = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR.'class.*.task.php';
        $files = glob($patn);
        foreach ($files as $p) {
            $tmp = explode('.',basename($p));
            if (count($tmp) == 4) { //this could reasonably be omitted
                require_once $p;
                $classname = $tmp[1].'Task';
                $obj = new $classname();
                if ($obj instanceof Job) {
                    if ($this->load_job($obj)) {
                        $res = TRUE;
                    } else {
                        throw new Exception('Failed to record job:'.$job->name);
                    }
                } elseif ($obj instanceof CmsRegularTask) {
                    $job = new RegularTask($obj);
                    if ($this->load_job($job)) {
                        $res = TRUE;
                    } else {
                        throw new Exception('Failed to record job: '.$job->name);
                    }
                }
            }
        }

        // 2.  Get task objects from modules
        $modops = ModuleOperations::get_instance();
        $modules = $modops->GetMethodicModules('get_tasks',TRUE);
        if (!$modules) return $res;
        foreach ($modules as $one) {
            $modinst = $modops->get_module_instance($one);
            $tasks = $modinst->get_tasks();
            if (!$tasks) {
                continue;
            }
            if (!is_array($tasks)) $tasks = [$tasks];

            foreach ($tasks as $obj) {
                if ($obj instanceof Job) {
                    if ($this->load_job($obj)) {
                        $res = TRUE;
                    } else {
                        throw new Exception('Failed to record job:'.$job->name);
                    }
                } elseif ($obj instanceof CmsRegularTask) {
                    $job = new RegularTask($obj);
                    $job->module = $one;
                    if ($this->load_job($job)) {
                        $res = TRUE;
                    } else {
                        throw new Exception('Failed to record job: '.$job->name);
                    }
                } elseif (is_file($obj)) {
                //TODO also support task(s) reported as class-file path
                }
            }
        }

        return $res;
    }

    // JobManager interface stuff - maybe into a relevant class or trait?

    /**
     * Load jobs for the specified module
     * @param mixed $module object|string module name
     */
    public function load_jobs_by_module($module)
    {
        if (!is_object($module)) $module = cms_utils::get_module($module);
        if (!method_exists($module,'get_tasks')) return;

        $tasks = $module->get_tasks();
        if (!$tasks) return;
        if (!is_array($tasks)) $tasks = [$tasks];

        foreach ($tasks as $obj) {
            if ($obj instanceof Job) {
                $this->load_job($obj);
            } elseif ($obj instanceof CmsRegularTask) {
                $job = new RegularTask($obj);
                $job->module = $module->GetName();
                $this->load_job($job);
            } elseif (is_file($obj)) {
            //TODO also support task(s) reported as class-file path
            }
        }
    }

    /**
     * Load a job having the specified identifier
     * @param int $job_id > 0
     * @throws InvalidArgumentException
     * @return mixed Job object | null
     */
    public function load_job_by_id(int $job_id)
    {
        $job_id = (int) $job_id;
        if ($job_id < 1) throw new InvalidArgumentException('Invalid job_id passed to '.__METHOD__);

        $db = $this->GetDb();
        $sql = 'SELECT * FROM '.self::TABLE_NAME.' WHERE id = ?';
        $row = $db->GetRow($sql, [ $job_id]);
        if (!$row) return;

        $obj = unserialize($row['data']); //, ['allowed_classes'=>['CMSMS\\Async\\Job']]);
        $obj->set_id($row['id']);
        return $obj;
    }

    /**
     * Record $job in the database. If the job's id property is 0, and a job with
     * the requisite name and module exists, its start time will be updated.
     * Otherwise a full update is done, or a new record is created
     *
     * @param Job $job
     * @return mixed bool or int job id
     */
    public function load_job(Job $job) : int
    {
        if (utils::job_recurs($job)) {
            $recurs = $job->frequency;
            $until = $job->until;
        } else {
            $recurs = $until = null;
        }

        $db = $this->GetDb();
        if (!$job->id) {
            $sql = 'SELECT id FROM '.self::TABLE_NAME.' WHERE name = ? AND module = ?';
            $dbr = $db->GetOne($sql,[$job->name,$job->module]);
            if ($dbr) {
                $sql = 'UPDATE '.self::TABLE_NAME.' SET start = ? WHERE id = ?';
                $db->Execute($sql,[$job->start,$dbr]);
                return $dbr;
            }
            //TODO consider merits of DT field for created etc
            $sql = 'INSERT INTO '.self::TABLE_NAME.' (name,created,module,errors,start,recurs,until,data) VALUES (?,?,?,?,?,?,?,?)';
            $dbr = $db->Execute($sql,[$job->name,$job->created,$job->module,$job->errors,$job->start,$recurs,$until,serialize($job)]);
            $new_id = $db->Insert_ID();
            try {
                $job->set_id($new_id);
                return $new_id;
            } catch (LogicException $e) {
                return 0;
            }
        } else {
            // note... we do not at any time play with the module, the data, or recurs/until stuff for existing jobs.
            $sql = 'UPDATE '.self::TABLE_NAME.' SET start = ? WHERE id = ?';
            $db->Execute($sql,[$job->start,$job->id]);
            return $job->id;
        }
    }

    /**
     * An alias for the load_job method
     * @param Job $job
     */
    public function save_job(Job $job)
    {
        return $this->load_job($job);
    }

    /**
     * Remove $job from the database
     * @param Job $job
     * @throws BadMethodCallException
     */
    public function unload_job(Job $job)
    {
        if (!$job->id) throw new BadMethodCallException('Cannot delete a job that has no id');
        $db = $this->GetDb();
        $sql = 'DELETE FROM '.self::TABLE_NAME.' WHERE id = ?';
        $db->Execute($sql,[$job->id]);
    }

    /**
     * An alias for unload_job method
     * @param Job $job
     */
    public function delete_job(Job $job)
    {
        $this->unload_job($job);
    }

    /**
     * Remove the job identified by $job_id from the database
     * @param int $job_id
     * @throws InvalidArgumentException
     */
    public function unload_job_by_id(int $job_id)
    {
        $job_id = (int) $job_id;
        if ($job_id < 1) throw new InvalidArgumentException('Invalid job_id passed to '.__METHOD__);

        $db = $this->GetDb();
        $sql = 'DELETE FROM '.self::TABLE_NAME.' WHERE id = ?';
        $db->Execute($sql,[$job_id]);
    }

    /**
     * Remove the job identified by $job_name and $module_name from the database
     * @param string $module_name
     * @param string $job_name
     * @throws InvalidArgumentException
     */
    public function unload_job_by_name(string $module_name, string $job_name)
    {
        $db = $this->GetDb();
        $sql = 'SELECT id FROM '.self::TABLE_NAME.' WHERE name = ? AND module = ?';
        $job_id = $db->GetOne($sql,[$job_name, $module_name]);
        if (!$job_id) throw new InvalidArgumentException('Invalid identifier(s) passed to '.__METHOD__);

        $sql = 'DELETE FROM '.self::TABLE_NAME.' WHERE id = ?';
        $db->Execute($sql,[$job_id]);
    }

    /**
     * Remove all jobs for the named module from the database e.g. when that module is uninstalled
     * @param string $module_name
     * @throws InvalidArgumentException
     */
    public function unload_jobs_by_module(string $module_name)
    {
        $module_name = trim($module_name);
        if (!$module_name) throw new InvalidArgumentException('Invalid module name passed to '.__METHOD__);

        $db = $this->GetDb();
        $sql = 'DELETE FROM '.self::TABLE_NAME.' WHERE module = ?';
        $db->Execute($sql,[$module_name]);
    }

    /**
     * An alias for the unload_jobs_by_module method
     * @param string $module_name
     */
    public function delete_jobs_by_module(string $module_name)
    {
        $this->unload_jobs_by_module($module_name);
    }

    /**
     * Regenerate the tasks database
     */
    public function refresh_jobs()
    {
        $this->create_jobs_from_eligible_tasks();
    }

    /**
     * Initiate job-processing, after checking whether it's appropriate to do so
     */
    public function begin_async_processing()
    {
        // if this module is disabled (but running anyway i.e. pre-hooklist) - do nothing
        if (!$this->GetPreference('enabled')) {
            return;
        }

        global $params; //stupid when run from a hook

        if (defined('ASYNCLOG')) {
            error_log('trigger_async_processing: $params: '.print_r($params, true)."\n", 3, ASYNCLOG);
        }

        // if we're processing a prior job-manager request - do nothing
        if (isset($params['cms_jobman'])) {

            if (defined('ASYNCLOG')) {
                error_log('prior job detected: prevent reentrance'."\n", 3, ASYNCLOG);
            }

            return; // no re-entrance
        }

        // if we're not yet ready to re-trigger - do nothing
        $last_trigger = (int) $this->GetPreference('last_processing');
        $now = time();
        $gap = (int)$this->GetPreference('jobinterval',1);
        if ($last_trigger >= $now - $gap * 60) {

            if (defined('ASYNCLOG')) {
                error_log('check again later'."\n", 3, ASYNCLOG);
            }

            return;
        }

        //profiler indicates this check is very trivial, even with deep scans
        if (!$this->check_for_jobs_or_tasks(true)) {

            if (defined('ASYNCLOG')) {
                error_log('no current job'."\n", 3, ASYNCLOG);
            }

            return; // nothing to do
/*        } else {
            if (defined('ASYNCLOG')) {
                error_log('trigger_async_processing @4'."\n", 3, ASYNCLOG);
            }
*/
        }

        $joburl = $this->GetPreference('joburl');
        if (!$joburl) {
            $joburl = $this->create_url('aj_','process', '', ['cms_jobman'=>1]) . '&'.CMS_JOB_KEY.'=2';
        }
        $joburl = str_replace('&amp;', '&', $joburl);  //fix needed for direct use ??

        if (defined('ASYNCLOG')) {
            error_log('async job url: '.$joburl."\n", 3, ASYNCLOG);
        }

        list($host, $path, $transport, $port) = $this->get_url_params($joburl);
        if (!$host) {
            if (defined('ASYNCLOG')) {
                error_log('bad async-job url: '.$joburl."\n", 3, ASYNCLOG);
            }
            return;
        }
        if (defined('ASYNCLOG')) {
            error_log('host: '.$host."\n", 3, ASYNCLOG);
            error_log('path: '.$path."\n", 3, ASYNCLOG);
            error_log('transport: '.$transport."\n", 3, ASYNCLOG);
            error_log('port: '.$port."\n", 3, ASYNCLOG);
        }

        $remote = $transport.'://'.$host.':'.$port;
        //TODO generally support the websocket protocol
        $opts = ['http' => ['method' => 'POST']];
        if ($transport != 'tcp') {
            //internal-use only, can skip verification
            $opts['ssl'] = [
//              'allow_self_signed' => TRUE,
                'verify_host' => FALSE,
                'verify_peer' => FALSE,
            ];
        }
        $context = stream_context_create($opts); //, $params);

        $res = stream_socket_client($remote, $errno, $errstr, 1, STREAM_CLIENT_ASYNC_CONNECT, $context);
        if ($res) {
            if (defined('ASYNCLOG')) {
                error_log('stream-socket client created: '.$remote."\n", 3, ASYNCLOG);
            }
            $req = "GET $path HTTP/1.1\r\nHost: {$host}\r\nContent-type: text/plain\r\nContent-length: 0\r\nConnection: Close\r\n\r\n";
            fputs($res, $req);
            if (defined('ASYNCLOG')) {
                error_log('stream-socket sent: '.$req."\n", 3, ASYNCLOG);
            }

            stream_socket_shutdown($res, STREAM_SHUT_RDWR);

            if ($errno == 0) {
                $this->SetPreference('last_processing',$now+1);
                return;
            }
        } else {
            if (defined('ASYNCLOG')) {
                error_log('stream-socket client failure: '.$remote."\n", 3, ASYNCLOG);
            }
        }

        if (defined('ASYNCLOG')) {
            if ($errno > 0) {
                error_log('stream-socket error: '.$errstr."\n", 3, ASYNCLOG);
            } else {
                error_log('stream-socket error: connection failure'."\n", 3, ASYNCLOG);
            }
        }

    }

    /**
     * Parse $url into parts suitable for stream creation
     * @since 2.3
     * @param string $url
     * @return array
     */
    protected function get_url_params(string $url) : array
    {
        $urlparts = parse_url($url);
        if (!$urlparts || empty($urlparts['host'])) {
            return [null, null, null, null];
        }
        $host = $urlparts['host'];
        $path = $urlparts['path'] ?? '';
        if (!empty($urlparts['query'])) $path .= '?'.$urlparts['query'];
        $scheme = $urlparts['scheme'] ?? 'http';
        $secure = (strcasecmp($scheme,'https') == 0);
        if ($secure) {
            $opts = stream_get_transports();
            if (in_array('tls', $opts)) {
                $transport = 'tls';
            } elseif (in_array('ssl', $opts)) { //deprecated PHP7
                $transport = 'ssl';
            } else {
                $transport = 'tcp';
                $secure = false;
            }
        } else {
            $transport = 'tcp';
        }
        $port = $urlparts['port'] ?? (($secure) ? 443 : 80);
        return [$host, $path, $transport, $port];
    }

    /**
     * static function to initiate async processing
     * @since 2.3
     */
    public static function begin_async_work()
    {
        global $params;
        $params = []; //hack, for ?

        (new self())->begin_async_processing();
    }
} // class
