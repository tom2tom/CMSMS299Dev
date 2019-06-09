<?php

use CMSMS\AdminUtils;

class ClearCacheTask implements CmsRegularTask
{
    const  LASTEXECUTE_SITEPREF   = 'Core::ClearCache_lastexecute';
    const  CACHEDFILEAGE_SITEPREF = 'auto_clear_cache_age';

    private $_age_days;

    public function get_name()
    {
        return self::class; //assume no namespace
    }

    public function get_description()
    {
        return lang_by_realm('tasks','clearcache_taskdescription');
    }

    public function test($time = '')
    {
        $this->_age_days = (int)cms_siteprefs::get(self::CACHEDFILEAGE_SITEPREF,0);
        if( $this->_age_days == 0 ) return FALSE;

        // do we need to do this task now? (daily intervals)
        if( !$time ) $time = time();
        $last_execute = (int)cms_siteprefs::get(self::LASTEXECUTE_SITEPREF,0);
        if( ($time - 24*3600) >= $last_execute ) {
            // set this preference here... prevents multiple requests at or about the same time from getting here.
            cms_siteprefs::set(self::LASTEXECUTE_SITEPREF,$time);
            return TRUE;
        }
        return FALSE;
    }

    public function execute($time = '')
    {
        // do the task.
        AdminUtils::clear_cached_files($this->_age_days);
        return TRUE;
    }

    public function on_success($time = '')
    {
        if( !$time ) $time = time();
        cms_siteprefs::set(self::LASTEXECUTE_SITEPREF,$time);
    }

    public function on_failure($time = '')
    {
        // we failed, try again at the next request
        cms_siteprefs::remove(self::LASTEXECUTE_SITEPREF);
    }
} // class
