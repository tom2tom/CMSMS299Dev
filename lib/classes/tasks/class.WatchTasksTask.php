<?php
# Class WatchTasksTask: for periodic checks for new asunc tasks to be processed
# Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CmsApp;
use CMSMS\AppParams;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\Crypto;

class WatchTasksTask extends CronJob
{
    const ENABLED_SITEPREF = self::class.'\\\\taskschanged';
    const STATUS_SITEPREF = self::class.'\\\\signature';

    public function __construct()
    {
        parent::__construct();
        if( AppParams::get(self::ENABLED_SITEPREF,1) ) {
	        $this->frequency = RecurType::RECUR_HALFDAILY;
		} else {
	        $this->frequency = RecurType::RECUR_NONE;
		}
    }

    public function execute()
    {
        $mod = CmsApp::get_instance()->GetJobManager();
        if( $mod ) {
            $sig = '';
            $files = scandir(__DIR__);
            foreach( $files as $file ) {
                $fp = __DIR__.DIRECTORY_SEPARATOR.$file;
                $sig .= filesize($fp).filemtime($fp);
            }
            $sig = Crypto::hash_string($sig);
            $saved = AppParams::get(self::STATUS_SITEPREF,'');
            if( $saved != $sig ) {
                AppParams::set(self::STATUS_SITEPREF,$sig);
                $mod->check_for_jobs_or_tasks(true);
            }
        }
    }
}

\class_alias('CMSMS\tasks\WatchTasksTask','WatchTasksTask', false);
