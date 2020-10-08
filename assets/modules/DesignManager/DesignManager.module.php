<?php
# Module: DesignManager - A CMSMS addon module to provide designs management.
# Copyright (C) 2012-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

final class DesignManager extends CMSModule
{
    public function GetFriendlyName()  { return $this->Lang('friendlyname'); }
    public function GetVersion()  { return '2.0'; }
    public function MinimumCMSVersion()  { return '2.2.911'; }
//    public function LazyLoadAdmin() { return true; }
//    public function LazyLoadFrontend() { return true; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function HasAdmin() { return true; }
    public function GetAdminSection() { return 'layout'; }
    public function IsAdminOnly() { return true; }
    public function GetHelp() { return $this->Lang('help_module'); }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Manage Designs');
    }

    public function GetHeaderHTML()
    {
        $out = '';
        $urlpath = $this->GetModuleURLPath();
        $fmt = '<link rel="stylesheet" type="text/css" href="%s/%s" />';
        $cssfiles = [
        'css/module.css',
        ];
        foreach( $cssfiles as $one ) {
            $out .= sprintf($fmt,$urlpath,$one).PHP_EOL;
        }
        add_page_headtext($out, false);
    }

    public function GetAdminMenuItems()
    {
        $out = [];
        if( $this->VisibleToAdminUser() ) $out[] = CmsAdminMenuItem::from_module($this);
/*
        $state = $this->VisibleToAdminUser();

        if ($state) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'layout';  //aka presentation
            $obj->title = $this->Lang('prompt_action_styles');
            $obj->description = $this->Lang('title_action_styles');
            $obj->action = 'liststyles';
            $obj->icon = false;
            $out[] = $obj;

            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'layout';
            $obj->title = $this->Lang('prompt_action_templates');
            $obj->description = $this->Lang('title_action_templates');
            $obj->action = 'listtemplates';
            $obj->icon = false;
            $out[] = $obj;

            $config = cms_config::get_instance();
            if (1) { //DEBUG $config['develop_mode']) {
                $obj = new CmsAdminMenuItem();
                $obj->module = $this->GetName();
                $obj->section = 'layout';
                $obj->title = $this->Lang('prompt_action_designs');
                $obj->description = $this->Lang('title_action_designs');
                $obj->action = 'defaultadmin';
                $obj->icon = false;
                $out[] = $obj;
            }
        }

        if ($this->CheckPermission('Modify Site Preferences')) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'layout';
            $obj->title = $this->Lang('prompt_action_settings');
            $obj->description = $this->Lang('title_action_settings');
            $obj->action = 'admin_settings';
            $obj->icon = false;
            $out[] = $obj;
        }
*/
        return $out;
    }

    public function GetEventHelp( $eventname )
    {
        return $this->Lang('event_help_'.$eventname);
    }

    public function GetEventDescription( $eventname )
    {
        return $this->Lang('event_desc_'.$eventname);
    }
} // class
