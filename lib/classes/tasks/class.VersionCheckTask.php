<?php
# Class VersionCheckTask: for periodic checks for and warnings about a newer version of CMSMS
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

use CMSMS\AdminAlerts\TranslatableAlert;
use CMSMS\AppParams;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use CMSMS\HttpRequest;
use const CMS_DEFAULT_VERSIONCHECK_URL;
use const CMS_VERSION;
use function cms_notice;

class VersionCheckTask extends CronJob
{
    const  ENABLED_SITEPREF = self::class.'\\\\checkversion';

    public function __construct()
    {
        parent::__construct();
        if( AppParams::get(self::ENABLED_SITEPREF,1) ) {
            $this->frequency = RecurType::RECUR_DAILY;
        } else {
            $this->frequency = RecurType::RECUR_NONE;
        }
    }

    public function execute()
    {
        $remote_ver = $this->fetch_latest_cmsms_ver();
        if( $remote_ver && version_compare(CMS_VERSION,$remote_ver) < 0 ) {
            $alert = new TranslatableAlert(['Modify Site Preferences']);
            $alert->name = 'CMSMS Version Check';
            $alert->titlekey = 'new_version_avail_title';
            $alert->msgkey = 'new_version_avail2';
            $alert->msgargs = [ CMS_VERSION, $remote_ver ];
            $alert->save();
            cms_notice('CMSMS version '.$remote_ver.' is available');
        }
    }

    private function fetch_latest_cmsms_ver()
    {
        $req = new HttpRequest();
        $req->setTimeout(10);
        $req->execute(CMS_DEFAULT_VERSIONCHECK_URL);
        if( $req->getStatus() == 200 ) {
            $remote_ver = trim($req->getResult());
            if( strpos($remote_ver,':') !== false ) {
                list($tmp,$remote_ver) = explode(':',$remote_ver,2);
                $remote_ver = trim($remote_ver);
            }
            return $remote_ver;
        }
        return '';
    }
}

\class_alias('CMSMS\tasks\VersionCheckTask','CmsVersionCheckTask', false);