<?php
/*
ModuleManager: a CMS Made Simple module for managing installed modules,
including browsing remotely-stored modules, viewing information about them,
and downloading or upgrading
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppSingle;
use CMSMS\CoreCapabilities;
use ModuleManager\operations;

const MINIMUM_REPOSITORY_VERSION = '1.5';

class ModuleManager extends CMSModule
{
    const _dflt_request_url = 'https://www.cmsmadesimple.org/ModuleRepository/request/v2';

    private $_operations;

    public function GetAdminDescription() { return $this->Lang('admindescription'); }
    public function GetAdminSection() { return 'siteadmin'; }
    public function GetAuthor() { return 'calguy1000'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetName() { return 'ModuleManager'; }
    public function GetVersion() { return '2.2'; }
    public function HasAdmin() { return TRUE; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function IsAdminOnly() { return TRUE; }
//    public function IsPluginModule() { return FALSE; } default
//    public function LazyLoadAdmin() { return TRUE; }
    public function MinimumCMSVersion() { return '2.2.3'; }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
    public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }
    public function VisibleToAdminUser() { return ($this->CheckPermission('Modify Site Preferences') || $this->CheckPermission('Modify Modules')); }

    /**
     * @internal
     */
    public function get_operations()
    {
        if( !$this->_operations ) $this->_operations = new operations( $this );
        return $this->_operations;
    }

    protected function _DisplayErrorPage($id, &$params, $returnid, $message='')
    {
        $smarty = AppSingle::Smarty();
        $tpl = $smarty->createTemplate($this->GetTemplateResource('error.tpl')); //,null,null,$smarty);

        $tpl->assign('title_error', $this->Lang('error'))
         ->assign('message', $message)
         ->assign('link_back',$this->CreateLink($id,'defaultadmin',$returnid, $this->Lang('back_to_module')))
         ->display();
    }

    public function Install()
    {
        $this->SetPreference('module_repository',self::_dflt_request_url);
    }

    public function Upgrade($oldversion, $newversion)
    {
        $this->SetPreference('module_repository',self::_dflt_request_url);
    }

    public function DoAction($action, $id, $params, $returnid=-1)
    {
        @set_time_limit(9999);
/*
        $smarty = CmsApp::get_instance()->GetSmarty();
        $smarty->assign($this->GetName(), $this);
        $smarty->assign('mod', $this);
*/
        return parent::DoAction( $action, $id, $params, $returnid );
    }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CoreCapabilities::CORE_MODULE:
                return true;
//            case 'clicommands':
//                return class_exists('CMSMS\\CLI\\App'); // TODO better namespace
            default:
                return false;
        }
    }

    /* *
     * @since MAYBE IN FUTURE
     * @throws LogicException
     * @param CMSMS\CLI\App $app (exists only in App mode) TODO better namespace
     * @return array
     * /
    public function get_cli_commands( $app ) : array
    {
        if( parent::get_cli_commands($app) === null ) return [];

        $out = [];
        $out[] = new Command\PingModuleServerCommand( $app );
        $out[] = new Command\ModuleExistsCommand( $app );
        $out[] = new Command\ModuleExportCommand( $app );
        $out[] = new Command\ModuleImportCommand( $app );
        $out[] = new Command\ModuleInstallCommand( $app );
        $out[] = new Command\ModuleUninstallCommand( $app );
        $out[] = new Command\ModuleRemoveCommand( $app );
        $out[] = new Command\ListModulesCommand( $app );
        $out[] = new Command\ReposListCommand( $app );
        $out[] = new Command\ReposDependsCommand( $app );
        $out[] = new Command\ReposGetXMLCommand( $app );
        return $out;
    }
*/
} // class
