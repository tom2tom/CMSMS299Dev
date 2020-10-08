<?php
# Class ClearCacheTask: for periodic ....
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
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

namespace CMSMS\tasks;

use CMSMS\AdminUtils;
use CMSMS\AppParams;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;

class ClearCacheTask extends CronJob
{
    const LIFETIME_SITEPREF = self::class.'\\\\auto_clear_cache_age';

    private $_age_days;

    public function __construct()
    {
        parent::__construct();
        $this->_age_days = (int)AppParams::get(self::LIFETIME_SITEPREF, 0);
        if( $this->_age_days != 0 ) {
            $this->frequency = RecurType::RECUR_DAILY;
        } else {
            $this->frequency = RecurType::RECUR_NONE;
        }
    }

    public function execute()
    {
        if( $this->_age_days != 0 ) {
            AdminUtils::clear_cached_files($this->_age_days);
        }
    }
} // class

\class_alias('CMSMS\tasks\ClearCacheTask', 'ClearCacheTask', false);
