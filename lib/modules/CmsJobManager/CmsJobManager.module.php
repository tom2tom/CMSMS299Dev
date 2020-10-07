<?php
# CmsJobManager: a core module for CMS Made Simple to allow management of
# asynchronous and cron jobs.
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CmsJobManager\Utils as ManagerUtils;
use CMSMS\Async\Job;
use CMSMS\CoreCapabilities;
use CMSMS\HookOperations;

final class CmsJobManager extends CMSModule //DEBUG implements AsyncJobManager
{
    const LOCKPREF = 'lock';
    const MANAGE_JOBS = 'Manage Jobs';
    const EVT_ONFAILEDJOB = 'OnJobFailed';
    const TABLE_NAME = CMS_DB_PREFIX.'mod_cmsjobmgr';

    private $_current_job;
    private $_lock;
//    private $ASYNCLOG = TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'debug.log';

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'siteadmin'; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetVersion() { return '0.4'; }
    public function HandlesEvents() { return TRUE; }
    public function HasAdmin() { return TRUE; }
    public function InitializeFrontend() {}
    public function IsAdminOnly()  { return FALSE; }
//    public function IsPluginModule() { return FALSE; }
//    public function LazyLoadAdmin() { return TRUE; }
//    public function LazyLoadFrontend() { return TRUE; }
    public function MinimumCMSVersion() { return '2.1.99'; }
    public function VisibleToAdminUser() { return $this->CheckPermission(self::MANAGE_JOBS); }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CoreCapabilities::CORE_MODULE:
//          case CoreCapabilities::PLUGIN_MODULE:
//          case CoreCapabilities::TASKS:
            case CoreCapabilities::JOBS_MODULE:
            case CoreCapabilities::SITE_SETTINGS:
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
                    $this->refresh_jobs();
            }
        }
        parent::DoEvent($originator, $eventname, $params);
    }

    public function InitializeAdmin()
    {
        HookOperations::add_hook('ExtraSiteSettings', [$this, 'ExtraSiteSettings']);
    }

    /**
     * Hook function to populate 'centralised' site settings UI
     * @internal
     * @since 2.9
     * @return array
     */
    public function ExtraSiteSettings()
    {
        //TODO check permission local or Site Prefs
        return [
         'title'=> $this->Lang('settings_title'),
         //'desc'=> 'useful text goes here', // optional useful text
         'url'=> $this->create_url('m1_','defaultadmin','',['activetab'=>'settings']), // if permitted
         //optional 'text' => custom link-text | explanation e.g need permission
        ];
    }

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
        return ($this->_lock && $this->_lock < time() - ManagerUtils::get_async_freq());
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

    // JobManager interface stuff

    /**
     * Load jobs for the specified module
     * @param mixed $module object|string module name
     */
    public function load_jobs_by_module($module)
    {
        ManagerUtils::load_jobs_by_module($module);
    }

    /**
     * Load a job having the specified identifier
     * @param int $job_id > 0
     * @throws InvalidArgumentException
     * @return mixed Job object | null
     */
    public function load_job_by_id(int $job_id)
    {
        return ManagerUtils::load_job_by_id($job_id);
    }

    /**
     * Record $job in the database. If the job's id property is 0, and a job with
     * the requisite name and module exists, its start time will be updated.
     * Otherwise a full update is done, or a new record is created
     *
     * @param Job $job
     * @return mixed bool or int job id
     */
    public function load_job(Job $job)
    {
        return ManagerUtils::load_job($job);
    }

    /**
     * An alias for the load_job method
     * @param Job $job
     */
    public function save_job(Job &$job)
    {
        return ManagerUtils::load_job($job);
    }

    /**
     * Remove $job from the database
     * @param Job $job
     * @throws BadMethodCallException
     */
    public function unload_job(Job $job)
    {
        ManagerUtils::unload_job($job);
    }

    /**
     * An alias for unload_job method
     * @param Job $job
     */
    public function delete_job(Job $job)
    {
        ManagerUtils::unload_job($job);
    }

    /**
     * Remove the job identified by $job_id from the database
     * @param int $job_id
     * @throws InvalidArgumentException
     */
    public function unload_job_by_id(int $job_id)
    {
        ManagerUtils::unload_job_by_id($job_id);
    }

    /**
     * Remove the job identified by $job_name and $module_name from the database
     * @param string $module_name
     * @param string $job_name
     * @throws InvalidArgumentException
     */
    public function unload_job_by_name(string $module_name, string $job_name)
    {
        ManagerUtils::unload_job_by_name($module_name, $job_name);
    }

    /**
     * Remove all jobs for the named module from the database e.g. when that module is uninstalled
     * @param string $module_name
     * @throws InvalidArgumentException
     */
    public function unload_jobs_by_module(string $module_name)
    {
        ManagerUtils::unload_jobs_by_module($module_name);
    }

    /**
     * An alias for the unload_jobs_by_module method
     * @param string $module_name
     */
    public function delete_jobs_by_module(string $module_name)
    {
        ManagerUtils::unload_jobs_by_module($module_name);
    }

    /**
     * Regenerate the tasks-cache database
     */
    public function refresh_jobs()
    {
        ManagerUtils::refresh_jobs();
    }

    /**
     * Initiate job-processing, after checking whether it's appropriate to do so
     */
    public function begin_async_processing()
    {
        static $_returnid = -1;

        // if this module is disabled (but running anyway i.e. pre-hooklist) - do nothing
        if (!$this->GetPreference('enabled')) {
            return;
        }

//      error_log('trigger_async_processing @start'."\n", 3, $this->ASYNCLOG);
        $id = 'aj_'; //custom id for this operation
        // if we're processing a prior job-manager request - do nothing
        if( isset($_REQUEST[$id.'cms_jobman']) ) {
//            error_log('jobman module: abort re-entry'."\n", 3, $this->ASYNCLOG);
            return;
        }

        // ensure this method only operates once-per-request
        if( $_returnid !== -1 ) {
//            error_log('jobman module: abort no repeat during request'."\n", 3, $this->ASYNCLOG);
            return;
        }
/* DEBUG sync operation
        $params = ['cms_jobman' => 1];
        $gCms = cmsms();
        include_once __DIR__.DIRECTORY_SEPARATOR.'action.process.php';
        return;
*/
        $joburl = $this->GetPreference('joburl');
        if (!$joburl) {
            $joburl = $this->create_url('aj_','process', '', ['cms_jobman'=>1]) . '&'.CMS_JOB_KEY.'=2';
        }
        $joburl = str_replace('&amp;', '&', $joburl);  //fix needed for direct use ??

        list($host, $path, $transport, $port) = $this->get_url_params($joburl);
/* DEBUG sync operation
        $root = CMS_ROOT_URL;
        $p = strpos($root, $host);
        $urlroot = substr($root, 0, $p+strlen($host));
        redirect($urlroot.$path);
*/
//        error_log('trigger_async_processing path '.$path."\n", 3, $this->ASYNCLOG);

        $remote = $transport.'://'.$host.':'.$port;
        if ($transport == 'tcp') {
            $context = stream_context_create();
        } else {
            //internal-use only, skip verification
            $opts = [
            'ssl' => [
//              'allow_self_signed' => true,
                'verify_host' => false,
                'verify_peer' => false,
             ],
            'tls' => [
//              'allow_self_signed' => true,
                'verify_host' => false,
                'verify_peer' => false,
             ]
            ];
            $context = stream_context_create($opts); //, $params);
        }

        $res = stream_socket_client($remote, $errno, $errstr, 1, STREAM_CLIENT_ASYNC_CONNECT, $context);
        if ($res) {
//            error_log('jobman module: open stream '.$remote."\n", 3, $this->ASYNCLOG);
            $req = "GET $path HTTP/1.1\r\nHost: {$host}\r\nContent-type: text/plain\r\nContent-length: 0\r\nConnection: Close\r\n\r\n";
            fputs($res, $req);
//            error_log('stream-socket sent: '.$req."\n", 3, $this->ASYNCLOG);
            stream_socket_shutdown($res, STREAM_SHUT_RDWR);
            if ($errno ==  0) {
                return;
            } //else {
//              error_log('stream-socket client failure: '.$remote."\n", 3, $this->ASYNCLOG);
//            }
        }

        if ($errno > 0) {
//            error_log('stream-socket error: '.$errstr."\n", 3, $this->ASYNCLOG);
            debug_to_log($this->GetName().': stream-socket error: '.$errstr);
        } else {
//            error_log('stream-socket error: connection failure'."\n", 3, $this->ASYNCLOG);
            debug_to_log($this->GetName().': stream-socket error: connection failure');
        }
    }

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
