<?php
/*
Class ClearCacheJob: for periodic cleanup of all file-caches
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace CMSMS\jobs;

use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;

class ClearCacheJob extends CronJob
{
    const LIFETIME_SITEPREF = 'auto_clear_cache_age'; // value recorded via siteprefs UI, not this-job-specific

    private $_age_days;

    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\ClearCache';
        $this->_age_days = (int)AppParams::get(self::LIFETIME_SITEPREF, 0);
        if ($this->_age_days != 0) {
            $this->frequency = RecurType::RECUR_DAILY;
        } else {
            $this->frequency = RecurType::RECUR_NONE;
        }
    }

    /**
     * @ignore
     * @return int 0|1|2 indicating execution status
      */
    public function execute()
    {
        if ($this->_age_days != 0) {
            try {
                AdminUtils::clear_cached_files($this->_age_days);
                return 2;
            } catch (Throwable $t) {
                return 0;
            }
        }
        return 1;
    }
}

\class_alias('CMSMS\jobs\ClearCacheJob', 'ClearCacheTask', false);
