<?php
/*
TreeFiler module for CMSMS
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

final class TreeFiler extends CMSModule
{
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'files'; }
    public function GetAuthor() { return 'tomph'; }
    public function GetAuthorEmail() { return 'tomph@cmsmadesimple.org'; }
    public function GetEventDescription($name) { return $this->Lang('eventdesc_'.$name); }
    public function GetEventHelp($name) { return $this->Lang('eventhelp_'.$name); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetName() { return 'TreeFiler'; }
    public function GetVersion() { return '0.2'; }
    public function HasAdmin() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function IsAdminOnly() { return true; }
    public function LazyLoadAdmin() { return true; }
    public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.2.910'; }
    public function UninstallPostMessage() { return $this->Lang('uninstalled'); }
    public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }

    public function VisibleToAdminUser()
    {
        return $this->AccessAllowed() ||
		$this->CheckPermission('Modify Site Code'); // ||
//		$this->CheckPermission('Modify Site Assets');
    }

    public function GetChangeLog()
    {
        return ''.@file_get_contents(cms_join_path(__DIR__, 'lib', 'doc', 'changelog.htm'));
    }

    public function GetAdminMenuItems()
    {
        $out = [];

        if ($this->VisibleToAdminUser()) {
            $out[] = CmsAdminMenuItem::from_module($this);
        }
/* no settings
         if ($this->CheckPermission('Modify Site Preferences')) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'files';
            $obj->title = $this->Lang('module_settings_title');
            $obj->description = $this->Lang('module_settings_desc');
            $obj->action = 'admin_settings';
            $obj->url = $this->create_url('m1_', $obj->action);
            $out[] = $obj;
        }
*/
/* assets editing too dangerous
		if ($this->AssetAccessAllowed()) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'extensions';
            $obj->title = $this->Lang('module_assets_title');
            $obj->description = $this->Lang('module_assets_desc');
            $obj->action = 'defaultadmin';
            $obj->url = $this->create_url('m1_', $obj->action, null, ['astfiles'=>1]);
            $out[] = $obj;
		}
*/
        return $out;
    }

    public function AccessAllowed() {
        if ($this->CheckPermission('Modify Files')) {
            return true;
        }
        $config = cms_config::get_instance();
        return !empty($config['developer_mode']);
    }

    public function AdvancedAccessAllowed() {
        if ($this->CheckPermission('Modify Files') ||
           $this->CheckPermission('Modify Site Code')) {
            return true;
        }
        $config = cms_config::get_instance();
        return !empty($config['developer_mode']);
    }
/*
    public function AssetAccessAllowed() {
        if ($this->CheckPermission('Modify Site Assets')) {
            return true;
        }
        $config = cms_config::get_instance();
        return !empty($config['developer_mode']);
    }
*/
} // class
