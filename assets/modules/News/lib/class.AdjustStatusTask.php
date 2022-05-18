<?php
/*
Task: update articles' status in accord with their start/end times
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

namespace News;

use CMSMS\Lone;
use CMSMS\Utils;
use CmsRegularTask; // TODO update to Job
use const CMS_DB_PREFIX;

final class AdjustStatusTask implements CmsRegularTask
{
    const PREFNAME = 'StatusUpdateAt';

    public function get_name()
    {
        return __CLASS__;
    }

    public function get_description()
    {
        return 'Task which updates the recorded status of news items'; //$mod->Lang('TODO')
    }

    public function test($time = 0)
    {
        if( !$time ) $time = time();
        $mod = Utils::get_module('News');
        $lastrun = (int) $mod->GetPreference(self::PREFNAME);
        return $lastrun <= ($time - 3600); // hardcoded to hourly
    }

    public function on_success($time = 0)
    {
        if( !$time ) $time = time();
        $mod = Utils::get_module('News');
        $mod->SetPreference(self::PREFNAME,$time);
    }

    public function on_failure($time = 0) {}

    public function execute($time = 0)
    {
        $db = Lone::get('Db');
        if( !$time ) $time = time();
        $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status=\'archived\' WHERE (status=\'published\' OR status=\'final\') AND end_time IS NOT NULL AND end_time BETWEEN 1 AND ?';
        $db->execute($query,[$time]);
        $query = 'UPDATE '.CMS_DB_PREFIX.'module_news SET status=\'published\' WHERE status=\'final\' AND start_time IS NOT NULL AND start_time BETWEEN 1 AND ?';
        $db->execute($query,[$time]);
        return true;
    }
} // class
