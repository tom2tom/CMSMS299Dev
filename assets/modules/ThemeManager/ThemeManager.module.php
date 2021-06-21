<?php
/*
ThemeManager module for CMSMS.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This module is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

This module is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AdminMenuItem;
use CMSMS\AppSingle;

class ThemeManager extends CMSModule
{
    // export/import parameters
    const DTD_VERSION = 1.2;
    const DTD_MINVERSION = 1.2;

    public function AllowSmartyCaching() { return true; }
    public function GetAdminDescription() { return $this->Lang('publictip'); }
    public function GetAdminSection() { return 'layout'; }
    public function GetAuthor() { return 'Daniel Noor'; }
    public function GetAuthorEmail() { return 'danielnoor@cottagenetworks.com'; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetDependencies() { return ['FileManager' => '1.0.3']; }
    public function GetFriendlyName() { return $this->Lang('publicname'); }
    public function GetHelp() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'modhelp.htm'); }
    public function GetName() { return 'ThemeManager'; }
    public function GetVersion() { return '0.9'; }
    public function HasAdmin() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function IsPluginModule() { return false; }
//  public function LazyLoadAdmin() { return true; }
//  public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.99.0'; }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
    public function UninstallPreMessage() { return $this->Lang('preuninstall'); }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Manage Themes'); //TODO etc e.g. site settings
    }

    public function CheckAccess($perm = 'Manage Themes')
    {
        return $this->CheckPermission($perm);
    }

    public function GetAdminMenuItems()
    {
        $out = [];

        if ($this->VisibleToAdminUser()) {
            $out[] = AdminMenuItem::from_module($this);
        }
/*
        if ($this->CheckPermission('Modify Site Preferences')) {
            $obj = new AdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'todo';
            $obj->title = $this->Lang('TODO');
            $obj->description = $this->Lang('TODO');
            $obj->action = 'settings';
            $obj->name = 'set';
            $obj->url = $this->create_url('m1_', $obj->action);
            $out[] = $obj;
        }

        if (some other test) {
            $obj = new AdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'extensions';
            $obj->title = $this->Lang('TODO');
            $obj->description = $this->Lang('TODO');
            $obj->action = 'list';
            $obj->name = 'list';
            $obj->url = $this->create_url('m1_', $obj->action, null, [PARAMS]);
            $out[] = $obj;
        }
*/
        return $out;
    }

    public function DisplayErrorPage($id, $params, $returnid, $message = '')
    {
        $smarty = AppSingle::Smarty();
        $tpl = $smarty->createTemplate($this->GetTemplateResource('error.tpl')); //,null,null,$smarty);
        $tpl->assign('title_error', $this->Lang('error'))
         ->assign('message', $message)
         ->assign('link_back', $this->CreateLink($id, 'list', $returnid, $this->Lang('back_to_module')))
         ->display();
        return '';
    }
}
