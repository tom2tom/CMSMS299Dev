<?php
/*
Class WatchJobsJob: for periodic checks for new async jobs to be processed.
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\jobs;

use CMSMS\AppParams;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\Crypto;
use CMSMS\internal\JobOperations;
use const CMS_ASSETS_PATH;

class WatchJobsJob extends CronJob
{
    const ENABLED_SITEPREF = 'WatchJobsJob'.AppParams::NAMESPACER.'jobschanged';
    const STATUS_SITEPREF = 'WatchJobsJob'.AppParams::NAMESPACER.'signature';

    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\WatchJobs';
        if (AppParams::get(self::ENABLED_SITEPREF,1)) {
            $this->frequency = RecurType::RECUR_HALFDAILY;
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
        //check for changes in this same folder
        $sig = '';
        $files = scandir(__DIR__);
        foreach ($files as $file) {
            $fp = __DIR__.DIRECTORY_SEPARATOR.$file;
            $sig .= filesize($fp).filemtime($fp);
        }
        $bp = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'jobs';
        $files = scandir($bp);
        foreach ($files as $file) {
            if (fnmatch('class.*.php', $file)) {
                $fp = $bp.DIRECTORY_SEPARATOR.$file;
                $sig .= filesize($fp).filemtime($fp);
            }
        }
        //TODO check for changed module-jobs, if event-processing is bad ...

        $sig = Crypto::hash_string($sig);
        $saved = AppParams::get(self::STATUS_SITEPREF,'');
        if ($saved != $sig) {
            AppParams::set(self::STATUS_SITEPREF,$sig);
            $num = (new JobOperations())->refresh_jobs(true);
            if ($num > 0) { return 2; } // TODO
        }
        return 1;
    }
}

//\class_alias('CMSMS\jobs\WatchJobsJob','WatchTasksTask', false); N/A
