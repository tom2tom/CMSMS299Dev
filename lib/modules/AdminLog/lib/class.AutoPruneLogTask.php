<?php
/*
AdminLog module task: clear the log
Copyright (C) 2017-2019 CMS Made Simple Foundation <foundationcmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace AdminLog;

use AdminLog\storage;
use cms_siteprefs;
use cms_utils;
use CmsRegularTask;

class AutoPruneLogTask implements CmsRegularTask
{
    const LASTRUN_SITEPREF = 'AdminLog\\\\lastprune'; //sep was ::, now cms_siteprefs::NAMESPACER
    const LIFETIME_SITEPREF = 'AdminLog\\\\lifetime';  // was  'adminlog_lifetime'

    protected static function mod()
    {
        static $_mod;
        if( !$_mod ) $_mod = cms_utils::get_module('AdminLog');
        return $_mod;
    }

    public function get_name()
    {
        return self::class;
    }

    public function get_description()
    {
        return self::mod()->Lang('prunelog_description');
    }

    protected function get_lifetime()
    {
        $onemonth = 30 * 24 * 3600;

        $lifetime = (int)cms_siteprefs::get(self::LIFETIME_SITEPREF,$onemonth);
        if( $lifetime < 1 ) return;
        return $lifetime;
    }

    public function test($time = 0)
    {
        $lifetime = $this->get_lifetime();
        if( $lifetime < 1 ) return FALSE;

        if( !$time ) $time = time();
        $oneday = 24 * 3600;
        $last_execute = (int)cms_siteprefs::get(self::LASTRUN_SITEPREF,0);
        return ($last_execute < $time - $oneday );
    }

    public function execute($time = 0)
    {
        if( !$time ) $time = time();
        $oneday = 24 * 3600;
        $storage = new storage( self::mod() );
        $lifetime = $this->get_lifetime();
        $lifetime = max($lifetime,$oneday);
        $the_time = $time - $lifetime;
        $storage->remove_older_than( $the_time );
        return TRUE;
    }

    public function on_success($time = 0)
    {
        if( !$time ) $time = time();
        cms_siteprefs::set(self::LASTRUN_SITEPREF,$time);
    }

    public function on_failure($time = 0)
    {
    }
} // class
