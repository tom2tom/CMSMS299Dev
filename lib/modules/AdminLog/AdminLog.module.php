<?php
/*
AdminLog - a CMSMS module providing functionality for working with the CMSMS audit log
Copyright (C) 2017-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use AdminLog\auditor;
//use AdminLog\Command\ClearLogCommand;
use AdminLog\PruneLogJob;
use AdminLog\ReduceLogJob;
use AdminLog\storage;
use CMSMS\AuditOperations;
use CMSMS\CoreCapabilities;
use CMSMS\HookOperations;

final class AdminLog extends CMSModule
{
    const LIFETIME_SITEPREF = 'AdminLog\\\\lifetime';  //c.f. cms_siteprefs::NAMESPACER formerly 'adminlog_lifetime';

    protected $storage;
    protected $auditor;

    public function GetFriendlyName() { return $this->Lang('friendlyname');  }
    public function GetVersion() { return '1.1'; }
    public function GetHelp() { return $this->Lang('help'); }
    public function HasAdmin() { return true; }
    public function GetAdminSection() { return 'siteadmin'; }
    public function IsAdminOnly() { return true; }
    public function MinimumCMSVersion() { return '2.8.900'; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function InitializeAdmin()
    {
        parent::InitializeAdmin();

        $this->storage = new storage($this);
        $this->auditor = new auditor($this, $this->storage);

        try {
            AuditOperations::set_auditor($this->auditor);
        } catch(Throwable $t) {
            // ignore any error.
        }

        HookOperations::add_hook('ExtraSiteSettings', [$this, 'ExtraSiteSettings']);
        //NOTE these must be accessed with first_result hook-method
        HookOperations::add_hook('localizeperm',function($perm_source,$perm_name) {
                if( $perm_source != 'AdminLog' ) return;
                $key = 'perm_'.str_replace(' ','_',$perm_name);
                return $this->Lang($key);
            });
        HookOperations::add_hook('getperminfo',function($perm_source,$perm_name) {
                if( $perm_source != 'AdminLog' ) return;
                $key = 'permdesc_'.str_replace(' ','_',$perm_name);
                return $this->Lang($key);
            });
    }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CoreCapabilities::CORE_MODULE:
            case CoreCapabilities::TASKS:
            case CoreCapabilities::SITE_SETTINGS:
                return true;
//            case 'clicommands':
//                return class_exists('CMSMS\\CLI\\App'); //TODO better namespace
            default:
                return false;
        }
    }

    /**
     * Hook function to populate 'centralised' site settings UI
     * @internal
     * @since 2.9
     * @return array
     */
    public function ExtraSiteSettings()
    {
        //TODO check permission local or Site Prefs
        return [
         'title'=> $this->Lang('settings_title'),
         //'desc'=> 'useful text goes here', // optional useful text
         'url'=> $this->create_url('m1_','defaultadmin','',['activetab'=>'settings']), // if permitted
         //optional 'text' => custom link-text | explanation e.g need permission
        ];
    }

    public function get_tasks()
    {
        return [
            new PruneLogJob(),
            new ReduceLogJob(),
        ];
    }

    /* *
     * @since MAYBE IN FUTURE
     * @throws LogicException
     * @param CMSMS\CLI\App $app (exists only in App mode) TODO better namespace
     * @return array
     * /
    public function get_cli_commands( $app ) : array
    {
        $out = [];
        if( parent::get_cli_commands($app) !== null ) {
            $out[] = new ClearLogCommand( $app );
        }
        return $out;
    }
*/
} // class
