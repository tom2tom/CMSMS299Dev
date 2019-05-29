<?php

class WatchTasksTask implements CmsRegularTask
{
    const  LASTEXECUTE_SITEPREF   = __CLASS__;
    const  ENABLED_SITEPREF = 'taskschanged';
    const  STATUS_SITEPREF = __CLASS__.'signature';

    public function get_name()
    {
        return __CLASS__; //assume no namespace
    }

    public function get_description()
    {
        return __CLASS__; //lazy
    }

    public function test($time = '')
    {
        if( !cms_siteprefs::get(self::ENABLED_SITEPREF,1) ) return FALSE;

        // do we need to do this task now? (half-daily intervals)
        if( !$time ) $time = time();
        $last_execute = cms_siteprefs::get(self::LASTEXECUTE_SITEPREF,0);
        return ($time - 12*3600) >= $last_execute;
    }

    public function execute($time = '')
    {
        $mod = CmsApp::get_instance()->GetJobManager();
		if( $mod ) {
			$sig = '';
			$files = scandir(__DIR__);
			foreach( $files as $file ) {
				$fp = __DIR__.DIRECTORY_SEPARATOR.$file;
				$sig .= filesize($fp).filemtime($fp);
			}
			$sig = cms_utils::hash_string($sig);
			$saved = cms_siteprefs::get(self::STATUS_SITEPREF,'');
			if( $saved != $sig ) {
				cms_siteprefs::set(self::STATUS_SITEPREF,$sig);
				$mod->check_for_jobs_or_tasks(TRUE);
			}
			return TRUE;
		}
		return FALSE;
    }

    public function on_success($time = '')
    {
        if( !$time ) $time = time();
        cms_siteprefs::set(self::LASTEXECUTE_SITEPREF,$time);
    }

    public function on_failure($time = '')
    {
        // nothing here.
    }
}
