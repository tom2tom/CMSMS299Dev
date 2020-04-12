<?php
# AdminLog - a CMSMS module providing functionality for working with the
#   CMSMS audit log
# Copyright (C) 2017-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

use AdminLog\auditor;
use AdminLog\AutoPruneLogTask;
use AdminLog\Command\ClearLogCommand;
use AdminLog\ReduceLogTask;
use AdminLog\storage;
use CMSMS\AuditOperations;
use CMSMS\HookManager;

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
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function InitializeAdmin()
    {
        parent::InitializeAdmin();

        $this->storage = new storage( $this );
        $this->auditor = new auditor( $this, $this->storage );

        try {
            AuditOperations::set_auditor( $this->auditor );
        }
        catch( Exception $e ) {
            // ignore any error.
        }

        //NOTE these must be accessed with first_result hook-method
        HookManager::add_hook('localizeperm',function($perm_source,$perm_name) {
                if( $perm_source != 'AdminLog' ) return;
                $key = 'perm_'.str_replace(' ','_',$perm_name);
                return $this->Lang($key);
            });
        HookManager::add_hook('getperminfo',function($perm_source,$perm_name) {
                if( $perm_source != 'AdminLog' ) return;
                $key = 'permdesc_'.str_replace(' ','_',$perm_name);
                return $this->Lang($key);
            });
    }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CmsCoreCapabilities::CORE_MODULE:
            case CmsCoreCapabilities::TASKS:
                return true;
            case 'clicommands':
                return class_exists('CMSMS\\CLI\\App'); //TODO better namespace
            default:
                return false;
        }
    }

    public function get_tasks()
    {
        $out = [];
        $out[] = new AutoPruneLogTask();
        $out[] = new ReduceLogTask();
        return $out;
    }

    /**
     * @since 2.3
     * @throws LogicException
     * @param CMSMS\CLI\App $app (exists only in App mode) TODO better namespace
     * @return array
     */
    public function get_cli_commands( $app ) : array
    {
        $out = [];
        if( parent::get_cli_commands($app) !== null ) {
            $out[] = new ClearLogCommand( $app );
        }
        return $out;
    }
} // class
