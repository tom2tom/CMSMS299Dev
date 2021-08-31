<?php
/*
Class: job to clear admin-log-table records older than a specified interval
Copyright (C) 2017-2021 CMS Made Simple Foundation <foundationcmsmadesimple.org>

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
namespace CMSMS\jobs;

use CMSMS\AppParams;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\Log\dbstorage;

class PruneLogJob extends CronJob
{
    const LIFETIME_SITEPREF = 'adminlog_lifetime'; // value recorded via siteprefs UI, not this-job-specific

    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\PruneLog';
        $this->frequency = RecurType::RECUR_DAILY;
    }

    /**
     * Perform the job
     * @return int 0|1|2 indicating execution status
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

        // TODO televant-logger->clear_older_than($limit);
        $storage = new dbstorage();
        $storage->clear_older_than($limit);
        return 2; // TODO
    }
}
