<?php
#BEGIN_LICENSE
#-------------------------------------------------------------------------
# CmsJobManager: a core module for CMS Made Simple to allow management of
# asynchronous and cron jobs.
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
#-------------------------------------------------------------------------
#END_LICENSE

use \CMSMS\Async\Job, \CMSMS\Async\CronJobTrait, \CMSMS\HookManager;

final class CmsJobManager extends \CMSModule implements \CMSMS\Async\JobManagerInterface
{
    const LOCKPREF = 'lock';
    const ASYNCFREQ_PREF = 'asyncfreq';
    const MANAGE_JOBS = 'Manage Jobs';
    const EVT_ONFAILEDJOB = 'CmsJobManager::OnJobFailed';

    private $_current_job;
    private $_lock;

    public function __construct()
    {
        parent::construct();
        //alternative to hard-coded method call
        HookManager::add_hook('PostRequest', [$this, 'trigger_async_processing'], HookManager::PRIORITY_LOW);
    }

    public static function table_name() { return CMS_DB_PREFIX.'mod_cmsjobmgr'; }

    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetVersion() { return '0.2'; }
    public function MinimumCMSVersion() { return '2.1.99'; }
    public function GetAuthor() { return 'Calguy1000'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function IsPluginModule() { return TRUE; }
    public function HasAdmin() { return TRUE; }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'siteadmin'; }
    public function IsAdminOnly()  { return TRUE; }
    public function LazyLoadFrontend() { return FALSE; }
    public function LazyLoadAdmin() { return FALSE; }
    public function VisibleToAdminUser() { return $this->CheckPermission(self::MANAGE_JOBS); }
    public function GetHelp() { return $this->Lang('help'); }
    public function HandlesEvents() { return TRUE; }

    public function InitializeFrontend()
    {
        $this->RegisterModulePlugin();
//2.3 does nothing        $this->RestrictUnknownParams();
    }

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
        $tpl = $smarty->CreateTemplate($this->GetTemplateResource($str),null,null,$smarty);
        return $tpl;
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
        if (!is_null($job) && !$job instanceof \CMSMS\Async\Job) throw new \InvalidArgumentException('Invalid data passed to '.__METHOD__);
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
        if ($this->_lock && $this->_lock < time() - \CmsJobManager\utils::get_async_freq()) return TRUE;
        return FALSE;
    }

    protected function lock()
    {
        $this->_lock = time();
        $this->SetPreference(self::LOCKPREF,$this->_lock);
    }

    protected function unlock()
    {
        $this->_unlock = null;
        $this->RemovePreference(self::LOCKPREF);
    }

    protected function check_for_jobs_or_tasks($deep)
    {
        //TODO refine this algorithm
        if (!$deep) {
            // this is cheaper
            if (\CmsJobManager\JobQueue::get_jobs(TRUE)) {
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
            $lastcheck = (int) $this->GetPreference('tasks_lastcheck');
            if ($lastcheck < $now - 900) {
                $this->SetPreference('tasks_lastcheck',$now);
                if ($this->create_jobs_from_eligible_tasks()) {
                    return TRUE;
                }
            }
        }
        audit('','CmsJobManager','Found nothing to do');
        return FALSE;
    }

    /**
     * Create jobs from CmsRegularTask objects that we find, and that need to be executed.
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
            if (count($tmp) == 4) { //this could reasonably be omittted
                require_once $p;
                $classname = $tmp[1].'Task';
                $obj = new $classname;
                if (!$obj instanceof CmsRegularTask) continue;
                if (!$obj->test($now)) continue; //OR ALWAYS RECORD THE TASK?
                $job = new CMSMS\Async\RegularTask($obj);
                if ($this->load_job($job)) $res = TRUE;
            }
        }

        // 2.  Get task objects from modules.
        $opts = ModuleOperations::get_instance();
        $modules = $opts->get_modules_with_capability('tasks');
        if (!$modules) return $res;
        foreach ($modules as $one) {
            if (!is_object($one)) $one = \cms_utils::get_module($one);
            if (!method_exists($one,'get_tasks')) continue;

            $tasks = $one->get_tasks();
            if (!$tasks) continue;
            if (!is_array($tasks)) $tasks = [$tasks];

            foreach ($tasks as $onetask) {
                if (!is_object($onetask)) continue;
                if (!$onetask instanceof CmsRegularTask) continue;
                if (!$onetask->test()) continue;  //OR ALWAYS RECORD THE TASK?
                $job = new \CMSMS\Async\RegularTask($onetask);
                $job->module = $one->GetName();
                if ($this->load_job($job)) $res = TRUE;
            }
        }

        return $res;
    }

    // JobManager interface stuff - maybe into a relevant class?

    public function load_jobs_by_module($module)
    {
        if (!is_object($module)) $module = \cms_utils::get_module($module);
        if (!method_exists($module,'get_tasks')) return;

        $tasks = $module->get_tasks();
        if (!$tasks) return;
        if (!is_array($tasks)) $tasks = [$tasks];

        foreach ($tasks as $onetask) {
            if (!is_object($onetask)) continue;
            if (!$onetask instanceof CmsRegularTask) continue;
            $job = new \CMSMS\Async\RegularTask($onetask);
            $job->module = $module->GetName();
            $this->load_job($job);
        }
    }

    public function load_job_by_id($job_id)
    {
        $job_id = (int) $job_id;
        if ($job_id < 1) throw new \InvalidArgumentException('Invalid job_id passed to '.__METHOD__);

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
        if (\CmsJobManager\utils::job_recurs($job)) {
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
            } catch (\LogicException $e) {
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
        if (!$job->id) throw new \BadMethodCallException('Cannot delete a job that has no id');
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
        if ($job_id < 1) throw new \InvalidArgumentException('Invalid job_id passed to '.__METHOD__);

        $db = $this->GetDb();
        $sql = 'DELETE FROM '.self::table_name().' WHERE id = ?';
        $db->Execute($sql,[$job_id]);
    }

    public function unload_job_by_name($module_name, $job_name)
    {
        $db = $this->GetDb();
        $sql = 'SELECT id FROM '.self::table_name().' WHERE X = ? AND X = ?';
        $job_id = $db->GetOne($sql,[$module_name, $job_name]);
        if (!$job_id) throw new \InvalidArgumentException('Invalid identifier(s) passed to '.__METHOD__);

        $sql = 'DELETE FROM '.self::table_name().' WHERE id = ?';
        $db->Execute($sql,[$job_id]);
    }

    public function unload_jobs_by_module($module_name)
    {
        $module_name = trim($module_name);
        if (!$module_name) throw new \InvalidArgumentException('Invalid module name passed to '.__METHOD__);

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
        // flag to make sure this method only works once per request
        // and anyhow, preserve a returnid.
        static $_returnid = -1;
        if ($_returnid !== -1) {
            return;
        }

        // if we're processing a prior job-manager request - do nothing
        if (isset($_REQUEST['cms_jobman'])) {
            return; // no re-entrance
        }

        // if we're not yet ready to re-trigger - do nothing
        $last_trigger = (int) $this->GetPreference('last_async_trigger');
        $now = time();
        $gap = \CmsJobManager\utils::get_async_freq(); //TODO consider module preference instead of $config
        if ($last_trigger >= $now - $gap) {
            return;
        }

        $deep = TRUE; //TODO algorithm for this

        if (!$this->check_for_jobs_or_tasks($deep)) {
            return; // nothing to do
        }

        list ($host, $path, $transport, $port) = $this->GetActionParams('CmsJobManager', 'process', ['cms_jobman'=>1, 'cmsjobtype'=>2]);

        $remote = $transport.'://'.$host.':'.$port;

        if ($transport == 'tcp') {
            $context = stream_context_create();
        } else {
            //internal-use only, skip verification
            $opts = [
            'ssl' => [
//              'allow_self_signed' => TRUE,
                'verify_host' => FALSE,
                'verify_peer' => FALSE,
             ],
            'tls' => [
//              'allow_self_signed' => TRUE,
                'verify_host' => FALSE,
                'verify_peer' => FALSE,
             ]
            ];
            $context = stream_context_create($opts); //, $params);
        }

        $res = stream_socket_client($remote, $errno, $errstr, 1, STREAM_CLIENT_ASYNC_CONNECT, $context);
        if ($res) {
            $req = "GET $path HTTP/1.1\r\nHost: {$host}\r\nContent-type: text/plain\r\nContent-length: 0\r\nConnection: Close\r\n\r\n";
            fputs($res, $req);
            stream_socket_shutdown($res, STREAM_SHUT_RDWR);

            $this->SetPreference('last_async_trigger',$now+1);
//            return;
        }

/*        if ($errno > 0) {
            error_log('stream-socket error: '.$errstr."\n", 3, $logfile);
        } else {
            error_log('stream-socket error: connection failure'."\n", 3, $logfile);
        }
*/
    }

    protected function GetActionParams($modname, $action, $params = [])
    {
        //TODO support custom URL == module preference or config setting
//        $config = \cmsms()->GetConfig();

        $root = CMS_ROOT_URL;
        if (empty($_SERVER['HTTPS'])) {
            $transport = 'tcp';
            $port = 80;
        } else {
            $opts = stream_get_transports();
            if (in_array('tls', $opts)) {
                $transport = 'tls';
                $port = 443;
            } elseif (in_array('ssl', $opts)) { //deprecated PHP7
                $transport = 'ssl';
                $port = 443;
            } else {
                $transport = 'tcp';
                $port = 80;
            }
        }

        $p = strpos($root, '://');
        $host = substr($root, $p + 3);
        if (($p = strpos($host, '/')) !== FALSE)  {
            $path = substr($host, $p);
            $host = substr($host, 0, $p);
        } else {
            $path = '';
        }

        $id = 'aj_';

        //THIS IS CUSTOM - COULD REVERT TO index.php
        $path .= '/jobinterface.php?mact='.$modname.','.$id.','.$action.',0';

        if ($params) {
            $ignores = ['assign', 'id', 'returnid', 'action', 'module'];
            foreach ($params as $key => $value) {
                if (!in_array($key, $ignores)) {
                    $path .= '&'.$id.rawurlencode($key).'='.rawurlencode($value);
                }
            }
        }
        return [$host, $path, $transport, $port];
    }

} // class CmsJobManager
