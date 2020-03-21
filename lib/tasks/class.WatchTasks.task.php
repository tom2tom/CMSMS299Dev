<?php

class WatchTasksTask implements CmsRegularTask
{
    const  LASTRUN_SITEPREF = self::class.'\\\\lastexecute'; //sep was ::, now cms_siteprefs::NAMESPACER
    const  ENABLED_SITEPREF = self::class.'\\\\taskschanged';
    const  STATUS_SITEPREF = self::class.'\\\\signature';

    public function get_name()
    {
        return self::class;
    }

    public function get_description()
    {
        return lang_by_realm('tasks','watchtasks_taskdescription');
    }

    public function test($time = 0)
    {
        if( !cms_siteprefs::get(self::ENABLED_SITEPREF,1) ) return FALSE;

        // do we need to do this task now? (half-daily intervals)
        if( !$time ) $time = time();
        $last_execute = (int)cms_siteprefs::get(self::LASTRUN_SITEPREF,0);
        return ($time - 12*3600) >= $last_execute;
    }

    public function execute($time = 0)
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
