<?php
/*
AdminLog module task: clear from the log the records older than a specified interval
Copyright (C) 2017-2020 CMS Made Simple Foundation <foundationcmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace AdminLog;

use AdminLog\storage;
use CMSMS\AppParams;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\Utils;

class PruneLogJob extends CronJob
{
    const LIFETIME_SITEPREF = 'AdminLog'.AppParams::NAMESPACER.'lifetime';  // was  'adminlog_lifetime' c.f. AppParams::NAMESPACER

    public function __construct()
    {
        parent::__construct();
        $this->name = 'AdminLog\\Prune';
        $this->frequency = RecurType::RECUR_DAILY;
        $this->module = 'AdminLog';
    }

    /**
     * Perform the task
     */
    public function execute()
    {
        $oneday = 86400; //i.e. 24 * 3600 seconds
        $onemonth = $oneday * 30;
        $lifetime = (int)AppParams::get(self::LIFETIME_SITEPREF, $onemonth);
        if ($lifetime < 1) {
            $lifetime = 0;
        }
        $lifetime = max($lifetime, $oneday);
        $limit = time() - $lifetime;

        $mod = Utils::get_module('AdminLog');
        $storage = new storage($mod);
        $storage->clear_older_than($limit);
    }
}
