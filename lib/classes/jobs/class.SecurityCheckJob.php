<?php
/*
Class SecurityCheckJob: for periodic checks for and warnings about several system-settings.
Copyright (C) 2016-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS\jobs;

//use CMSMS\AppParams;
use CMSMS\AdminAlerts\TranslatableAlert;
use CMSMS\Async\CronJob;
use CMSMS\Async\RecurType;
use const CMS_ROOT_PATH;
use const CONFIG_FILE_LOCATION;
use function cms_join_path;

class SecurityCheckJob extends CronJob
{
    public function __construct()
    {
        parent::__construct();
        $this->name = 'Core\SecurityCheck';
        $this->frequency = RecurType::RECUR_DAILY;
    }

    /**
     * @ignore
     * @return int 0|1|2 indicating execution status
     */
    public function execute()
    {
        // check if config is writable
        if( is_writable(CONFIG_FILE_LOCATION) ) {
            $alert = new TranslatableAlert('Modify Site Preferences');
            $alert->name = __CLASS__.'config'; // so that there can only ever be one alert of this type at a time.
            $alert->msgkey = 'config_writable';
            $alert->priority = $alert::PRIORITY_HIGH;
            $alert->titlekey = 'security_issue';
            $alert->save();
        }

        // check if install-file exists
        $pattern = cms_join_path(CMS_ROOT_PATH,'cmsms-*-install.php');
        $files = glob($pattern);
        if( $files ) {
            $fn = basename($files[0]);
            $alert = new TranslatableAlert('Modify Site Preferences');
            $alert->name = __CLASS__.'install';
            $alert->msgkey = 'installfileexists';
            $alert->msgargs = $fn;
            $alert->priority = $alert::PRIORITY_HIGH;
            $alert->titlekey = 'security_issue';
            $alert->save();
        }

        // check if mail is configured
        // not really a security issue... but meh, it saves another class.
/*        if( !AppParams::get('mail_is_set',false) ) {
            $alert = new TranslatableAlert('Modify Site Preferences');
            $alert->name = __CLASS__.'mail';
            $alert->msgkey = 'info_mail_notset';
            $alert->priority = $alert::PRIORITY_HIGH;
            $alert->titlekey = 'config_issue';
            $alert->save();
        }
*/
        //TODO presence of a module having CapabilityType::EMAIL_MODULE
        return 2; // TODO
    }
}
\class_alias('CMSMS\jobs\SecurityCheckJob', 'CmsSecurityCheckTask', false);
