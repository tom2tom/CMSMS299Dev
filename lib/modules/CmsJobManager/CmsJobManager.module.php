<?php
# CmsJobManager: a core module for CMS Made Simple to allow management of
# asynchronous and cron jobs.
# Copyright (C) 2016-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSMS\Async\Job;
use CMSMS\Async\JobManagerInterface;
use CMSMS\Async\RegularTask;
use CMSMS\HookManager;
use CMSMS\ModuleOperations;

final class CmsJobManager extends CMSModule implements JobManagerInterface
{
    const LOCKPREF = 'lock';
    const ASYNCFREQ_PREF = 'asyncfreq';
    const MANAGE_JOBS = 'Manage Jobs';
    const EVT_ONFAILEDJOB = 'OnJobFailed';

    private $_current_job;
    private $_lock;

    public function __construct()
    {
        parent::__construct();
        if ($this->GetPreference('enabled')) {
            HookManager::add_hook('PostRequest', [$this, 'trigger_async_processing'], HookManager::PRIORITY_LOW);
        }
// ?? for event-processing purposes
//        $this->RegisterModulePlugin();
    }

    public static function table_name() { return CMS_DB_PREFIX.'mod_cmsjobmgr'; }

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'siteadmin'; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetVersion() { return '0.3'; }
    public function HandlesEvents() { return TRUE; }
    public function HasAdmin() { return TRUE; }
    public function IsAdminOnly()  { return TRUE; }
//    public function IsPluginModule() { return TRUE; }
    public function LazyLoadAdmin() { return TRUE; }
    public function LazyLoadFrontend() { return TRUE; }
    public function MinimumCMSVersion() { return '2.1.99'; }
    public function VisibleToAdminUser() { return $this->CheckPermission(self::MANAGE_JOBS); }

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

    protected function &create_new_template($str)
    {
        $smarty = $this->GetActionTemplateObject();
        return $smarty->createTemplate($this->GetTemplateResource($str),null,null,$smarty);
    }

    /**
     * @ignore
     * @internal
     */
    public function &get_current_job()
    {
        return $this->_current_job;
    }

    protected function set_current_job($job = null)
    {
        if (!is_null($job) && !$job instanceof Job) throw new InvalidArgumentException('Invalid data passed to '.__METHOD__);
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
        if ($this->_lock && $this->_lock < time() - utils::get_async_freq()) return TRUE;
        return FALSE;
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

    /* 'deep' checks occur upon:
     * any module change (per $this->DoEvent())
     * any system uprade - TODO initiated how ?
     * 12-hourly signature-check of <root>/lib/tasks dir (per WatchTasks task)
     */
    protected function check_for_jobs_or_tasks($deep)
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
     * Create jobs from CmsRegularTask objects that need to be executed.
     */
    protected function create_jobs_from_eligible_tasks()
    {
        $now = time();
        $res = FALSE;

        // 1.  Get task objects from files.
        // fairly expensive to iterate a directory and load files and create objects.
        $patn = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'tasks'.DIRECTORY_SEPARATOR.'class.*.task.php';
        $files = glob($patn);
        foreach ($files as $p) {
            $tmp = explode('.',basename($p));
            if (count($tmp) == 4) { //this could reasonably be omitted
                require_once $p;
                $classname = $tmp[1].'Task';
                $obj = new $classname;
                if (!$obj instanceof CmsRegularTask) {
					continue;
				}
//                if (!$obj->test($now)) {
//					continue; //OR ALWAYS RECORD THE TASK?
//				}
                $job = new RegularTask($obj);
                if ($this->load_job($job)) $res = TRUE;
            }
        }

        // 2.  Get task objects from modules.
        $opts = ModuleOperations::get_instance();
        $modules = $opts->get_modules_with_capability('tasks');
        if (!$modules) return $res;
        foreach ($modules as $one) {
            if (!is_object($one)) $one = cms_utils::get_module($one);
            if (!method_exists($one,'get_tasks')) {
				continue;
			}
            $tasks = $one->get_tasks();
            if (!$tasks) {
				continue;
			}
            if (!is_array($tasks)) $tasks = [$tasks];

            foreach ($tasks as $obj) {
                if (!is_object($obj)) continue;
                if (!$obj instanceof CmsRegularTask) {
					continue;
				}
//                if (!$obj->test($now)) continue;  //ALWAYS RECORD THE TASK?
                $job = new RegularTask($obj);
                $job->module = $one->GetName();
                if ($this->load_job($job)) $res = TRUE;
            }
        }

        return $res;
    }

    // JobManager interface stuff - maybe into a relevant class or trait?

    public function load_jobs_by_module($module)
    {
        if (!is_object($module)) $module = cms_utils::get_module($module);
        if (!method_exists($module,'get_tasks')) return;

        $tasks = $module->get_tasks();
        if (!$tasks) return;
        if (!is_array($tasks)) $tasks = [$tasks];

        foreach ($tasks as $obj) {
            if (!is_object($obj)) continue;
            if (!$obj instanceof CmsRegularTask) continue;
            $job = new RegularTask($obj);
            $job->module = $module->GetName();
            $this->load_job($job);
        }
    }

    public function load_job_by_id($job_id)
    {
        $job_id = (int) $job_id;
        if ($job_id < 1) throw new InvalidArgumentException('Invalid job_id passed to '.__METHOD__);

        $db = $this->GetDb();
        $sql = 'SELECT * FROM '.self::table_name().' WHERE id = ?';
        $row = $db->GetRow($sql, [ $job_id]);
        if (!is_array($row) || !count($row)) return;

        $obj = unserialize($row['data']);
        $obj->set_id($row['id']);
        return $obj;
    }

    public function load_job(Job &$job)
    {
        $recurs = $until = null;
        if (utils::job_recurs($job)) {
            $recurs = $job->frequency;
            $until = $job->until;
        }
        $db = $this->GetDb();
        if (!$job->id) {
            $sql = 'SELECT id FROM '.self::table_name().' WHERE name = ? AND module = ?';
            $dbr = $db->GetOne($sql,[$job->name,$job->module]);
            if($dbr) {
                $sql = 'UPDATE '.self::table_name().' SET start = ? WHERE id = ?';
                $db->Execute($sql,[$job->start,$dbr]);
                return $dbr;
            }
            $sql = 'INSERT INTO '.self::table_name().' (name,created,module,errors,start,recurs,until,data) VALUES (?,?,?,?,?,?,?,?)';
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
            $sql = 'UPDATE '.self::table_name().' SET start = ? WHERE id = ?';
            $db->Execute($sql,[$job->start,$job->id]);
            return $job->id;
        }
    }

    public function save_job(Job &$job)
    {
        return $this->load_job($job);
    }

    public function unload_job(Job &$job)
    {
        if (!$job->id) throw new BadMethodCallException('Cannot delete a job that has no id');
        $db = $this->GetDb();
        $sql = 'DELETE FROM '.self::table_name().' WHERE id = ?';
        $db->Execute($sql,[$job->id]);
    }

    public function delete_job(Job &$job)
    {
        $this->unload_job($job);
    }

    public function unload_job_by_id($job_id)
    {
        $job_id = (int) $job_id;
        if ($job_id < 1) throw new InvalidArgumentException('Invalid job_id passed to '.__METHOD__);

        $db = $this->GetDb();
        $sql = 'DELETE FROM '.self::table_name().' WHERE id = ?';
        $db->Execute($sql,[$job_id]);
    }

    public function unload_job_by_name($module_name, $job_name)
    {
        $db = $this->GetDb();
        $sql = 'SELECT id FROM '.self::table_name().' WHERE X = ? AND X = ?';
        $job_id = $db->GetOne($sql,[$module_name, $job_name]);
        if (!$job_id) throw new InvalidArgumentException('Invalid identifier(s) passed to '.__METHOD__);

        $sql = 'DELETE FROM '.self::table_name().' WHERE id = ?';
        $db->Execute($sql,[$job_id]);
    }

    public function unload_jobs_by_module($module_name)
    {
        $module_name = trim($module_name);
        if (!$module_name) throw new InvalidArgumentException('Invalid module name passed to '.__METHOD__);

        $db = $this->GetDb();
        $sql = 'DELETE FROM '.self::table_name().' WHERE module = ?';
        $db->Execute($sql,[$module_name]);
    }

    public function delete_jobs_by_module($module_name)
    {
        $this->unload_jobs_by_module($module_name);
    }

    public function refresh_jobs()
    {
        $this->create_jobs_from_eligible_tasks();
    }

    public function trigger_async_processing()
    {
        // if this module is disabled (but running anyway i.e. pre-hooklist) - do nothing
        if (!$this->GetPreference('enabled')) {
            return;
        }

        global $params;
/*
        if (defined('ASYNCLOG')) {
            error_log('trigger_async_processing @1: $params: '.print_r($params, true)."\n", 3, ASYNCLOG);
        }
*/
        // if we're processing a prior job-manager request - do nothing
        if (isset($params['cms_jobman'])) {
/*
            if (defined('ASYNCLOG')) {
                error_log('prior job detected: prevent reentrance'."\n", 3, ASYNCLOG);
            }
*/
            return; // no re-entrance
        }

        // if we're not yet ready to re-trigger - do nothing
        $last_trigger = (int) $this->GetPreference('last_processing');
        $now = time();
        $gap = $this->GetPreference('jobinterval');
        if ($last_trigger >= $now - $gap * 60) {
            return;
        }

        if (!$this->check_for_jobs_or_tasks(false)) {
/*
            if (defined('ASYNCLOG')) {
                error_log('no current job'."\n", 3, ASYNCLOG);
            }
*/
            return; // nothing to do
/*        } else {
		    if (defined('ASYNCLOG')) {
		        error_log('trigger_async_processing @4'."\n", 3, ASYNCLOG);
		    }
*/
        }

        $joburl = $this->GetPreference('joburl');
        if (!$joburl) {
            $joburl = $this->create_url('aj_','process', '', ['cms_jobman'=>1]) . '&cmsjobtype=2';
        }
        $joburl = str_replace('&amp;', '&', $joburl);  //fix needed for direct use ??

/*        if (defined('ASYNCLOG')) {
            error_log('async job url: '.$joburl."\n", 3, ASYNCLOG);
        }
*/
        list ($host, $path, $transport, $port) = $this->get_url_params($joburl);
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

    //Since 2.3
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
} // class
