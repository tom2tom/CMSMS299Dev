<?php
/*
Class WatchTasksJob: for periodic checks for new async jobs to be processed.
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\Crypto;

class WatchTasksJob extends CronJob
{
    const ENABLED_SITEPREF = 'WatchTasksJob'.AppParams::NAMESPACER.'taskschanged';
    const STATUS_SITEPREF = 'WatchTasksJob'.AppParams::NAMESPACER.'signature';

    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\\WatchTasks';
        if (AppParams::get(self::ENABLED_SITEPREF,1)) {
            $this->frequency = RecurType::RECUR_HALFDAILY;
        } else {
            $this->frequency = RecurType::RECUR_NONE;
        }
    }

    public function execute()
    {
        $mod = AppSingle::App()->GetJobManager();
        if ($mod) {
            //check for changes in this same folder
            $sig = '';
            $files = scandir(__DIR__);
            foreach( $files as $file ) {
                $fp = __DIR__.DIRECTORY_SEPARATOR.$file;
                $sig .= filesize($fp).filemtime($fp);
            }
            //TODO check for changed module-jobs, if event-processing is bad ...
            $sig = Crypto::hash_string($sig);
            $saved = AppParams::get(self::STATUS_SITEPREF,'');
            if ($saved != $sig) {
                AppParams::set(self::STATUS_SITEPREF,$sig);
                $mod->refresh_jobs(true);
            }
        }
    }
}

\class_alias('CMSMS\tasks\WatchTasksJob','WatchTasksTask', false);
