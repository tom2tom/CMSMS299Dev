<?php

use CMSMS\AdminAlerts\TranslatableAlert;

class CmsVersionCheckTask implements CmsRegularTask
{
    const  LASTRUN_SITEPREF = self::class.'\\\\lastexecute'; //sep was ::, now cms_siteprefs::NAMESPACER
    const  ENABLED_SITEPREF = self::class.'\\\\checkversion';

    public function get_name()
    {
        return self::class;
    }

    public function get_description()
    {
        return lang_by_realm('tasks','versioncheck_taskdescription');
    }

    public function test($time = 0)
    {
        if( !cms_siteprefs::get(self::ENABLED_SITEPREF,1) ) return FALSE;

        // do we need to do this task now? (daily intervals)
        if( !$time ) $time = time();
        $last_execute = (int)cms_siteprefs::get(self::LASTRUN_SITEPREF,0);
        return ($time - 24*3600) >= $last_execute;
    }

    private function fetch_latest_cmsms_ver()
    {
        $remote_ver = 'error';
        $req = new cms_http_request();
        $req->setTimeout(10);
        $req->execute(CMS_DEFAULT_VERSIONCHECK_URL);
        if( $req->getStatus() == 200 ) {
            $remote_ver = trim($req->getResult());
            if( strpos($remote_ver,':') !== FALSE ) {
                list($tmp,$remote_ver) = explode(':',$remote_ver,2);
                $remote_ver = trim($remote_ver);
            }
        }
        return $remote_ver;
    }

    public function execute($time = 0)
    {
        // do the task.
        $remote_ver = $this->fetch_latest_cmsms_ver();
        if( version_compare(CMS_VERSION,$remote_ver) < 0 ) {
            $alert = new TranslatableAlert(['Modify Site Preferences']);
            $alert->name = 'CMSMS Version Check';
            $alert->titlekey = 'new_version_avail_title';
            $alert->msgkey = 'new_version_avail2';
            $alert->msgargs = [ CMS_VERSION, $remote_ver ];
            $alert->save();
            cms_notice('CMSMS version '.$remote_ver.' is available');
        }
        return TRUE;
    }

    public function on_success($time = 0)
    {
        if( !$time ) $time = time();
        cms_siteprefs::set(self::LASTRUN_SITEPREF,$time);
    }

    public function on_failure($time = 0)
    {
        // nothing here.
    }
}
