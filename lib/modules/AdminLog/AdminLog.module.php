<?php
# AdminLog - a CMSMS module providing functionality for working with the
#   CMSMS audit log
# Copyright (C) 2017-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

require_once(__DIR__.'/lib/class.storage.php');
require_once(__DIR__.'/lib/class.auditor.php');
require_once(__DIR__.'/lib/class.event.php');

final class AdminLog extends \CMSModule
{
    protected $storage;
    protected $auditor;

    public function InitializeAdmin()
    {
        parent::InitializeAdmin();

        //NOTE these cannot be used in multi-handler lists, cuz returned params are not suitable for next in list!
        \CMSMS\HookManager::add_hook('localizeperm',function($perm_source,$perm_name) {
                if( $perm_source != 'AdminLog' ) return;
                $key = 'perm_'.str_replace(' ','_',$perm_name);
                return $this->Lang($key);
            });
        \CMSMS\HookManager::add_hook('getperminfo',function($perm_source,$perm_name) {
                if( $perm_source != 'AdminLog' ) return;
                $key = 'permdesc_'.str_replace(' ','_',$perm_name);
                return $this->Lang($key);
            });
    }

    public function SetParameters()
    {
        $this->storage = new \AdminLog\storage( $this );
        $this->auditor = new \AdminLog\auditor( $this, $this->storage );

        try {
            \CMSMS\AuditManager::set_auditor( $this->auditor );
        }
        catch( \Exception $e ) {
            // ignore any error.
        }
    }

    public function GetFriendlyName() { return $this->Lang('friendlyname');  }
    public function GetVersion() { return '1.0'; }
    public function GetHelp() { return $this->Lang('help'); }
    public function HasAdmin() { return true; }
    public function GetAdminSection() { return 'siteadmin'; }
    public function IsAdminOnly() { return true; }
    public function VisibleToAdminUser() { return $this->CheckPermission('Modify Site Preferences'); }

    public function HasCapability($capability, $params = array())
    {
        if( $capability == \CmsCoreCapabilities::TASKS ) return true;
        if( $capability == 'clicommands' ) return true;
    }

    public function get_tasks()
    {
        $out = [];
        $out[] = new \AdminLog\AutoPruneLogTask();
        $out[] = new \AdminLog\ReduceLogTask();
        return $out;
    }

    public function get_cli_commands( $app )
    {
        if( ! $app instanceof \CMSMS\CLI\App ) throw new \LogicException(__METHOD__.' Called from outside of cmscli');
        if( !class_exists('\\CMSMS\\CLI\\GetOptExt\\Command') ) throw new \LogicException(__METHOD__.' Called from outside of cmscli');

        $out = [];
        $out[] = new \AdminLog\ClearLogCommand( $app );
        return $out;
    }
} // class
