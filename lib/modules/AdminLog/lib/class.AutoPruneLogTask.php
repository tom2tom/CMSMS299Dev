<?php
/*
AdminLog module task: clear the log
Copyright (C) 2017-2018 CMS Made Simple Foundation <foundationcmsmadesimple.org>
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
    const LASTEXECUTE_SITEPREF = 'AdminLog::Prune_lastexecute';
    const LIFETIME_SITEPREF = 'adminlog_lifetime';

    protected static function &mod()
    {
        static $_mod;
        if( !$_mod ) $_mod = cms_utils::get_module('AdminLog');
        return $_mod;
    }

    public function get_name() { return get_class($this); }

    public function get_description()
    {
        return self::mod()->Lang('prunelog_description');
    }

    protected function get_lifetime()
    {
        $oneday = 24 * 60 * 60;
        $onemonth = $oneday * 30;

        $lifetime = (int) cms_siteprefs::get(self::LIFETIME_SITEPREF,$onemonth);
        if( $lifetime < 1 ) return;
        return $lifetime;
    }

    public function test($time = '')
    {
        if( !$time ) $time = time();
        $oneday = 24 * 60 * 60;
        $lifetime = $this->get_lifetime();
        if( $lifetime < 1 ) return FALSE;

        $last_execute = cms_siteprefs::get(self::LASTEXECUTE_SITEPREF,0);
        IF( $last_execute < $time - $oneday ) return TRUE;
    }

    public function execute($time = '')
    {
        if( !$time ) $time = time();
        $mod = cms_utils::get_module('AdminLog');
        $storage = new storage( $mod );
        $lifetime = $this->get_lifetime();
        $lifetime = max($lifetime,$oneday);
        $the_time = $time() - $lifetime;
        $storage->remove_older_than( $the_time );
        return TRUE;
    }

    public function on_success($time = '')
    {
        if( !$time ) $time = time();
        cms_siteprefs::set(self::LASTEXECUTE_SITEPREF,$time);
    }

    public function on_failure($time = '')
    {
        if( !$time ) $time = time();
    }
} // class
