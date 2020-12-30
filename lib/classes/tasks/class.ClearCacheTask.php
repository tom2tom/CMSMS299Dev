<?php
/*
Class ClearCacheTask: for periodic cleanup of all file-caches
Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\tasks;

use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;

class ClearCacheTask extends CronJob
{
    const LIFETIME_SITEPREF = 'auto_clear_cache_age'; // recorded via siteprefs UI, not this-job-specific

    private $_age_days;

    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\\ClearCache';
        $this->_age_days = (int)AppParams::get(self::LIFETIME_SITEPREF, 0);
        if ($this->_age_days != 0) {
            $this->frequency = RecurType::RECUR_DAILY;
        } else {
            $this->frequency = RecurType::RECUR_NONE;
        }
    }

    public function execute()
    {
        if ($this->_age_days != 0) {
            AdminUtils::clear_cached_files($this->_age_days);
        }
    }
}

\class_alias('CMSMS\tasks\ClearCacheTask', 'ClearCacheTask', false);
