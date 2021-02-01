<?php
/*
Module: DesignManager - A CMSMS module to provide design management.
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AdminMenuItem;
//use CMSMS\AppSingle;

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
        if( $this->VisibleToAdminUser() ) $out[] = AdminMenuItem::from_module($this);
/*
        $state = $this->VisibleToAdminUser();

        if ($state) {
            $obj = new CMSMS\AdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'layout';  //aka presentation
            $obj->title = $this->Lang('prompt_action_styles');
            $obj->description = $this->Lang('title_action_styles');
            $obj->action = 'liststyles';
            $obj->name = 'styles';
            $obj->icon = false;
            $out[] = $obj;

            $obj = new CMSMS\AdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'layout';
            $obj->title = $this->Lang('prompt_action_templates');
            $obj->description = $this->Lang('title_action_templates');
            $obj->action = 'listtemplates';
            $obj->name = 'tpls';
            $obj->icon = false;
            $out[] = $obj;

            $config = CMSMS\AppSingle::Config();
            if (1) { //DEBUG $config['develop_mode']) {
                $obj = new CMSMS\AdminMenuItem();
                $obj->module = $this->GetName();
                $obj->section = 'layout';
                $obj->title = $this->Lang('prompt_action_designs');
                $obj->description = $this->Lang('title_action_designs');
                $obj->action = 'defaultadmin';
                $obj->name = 'default';
                $obj->icon = false;
                $out[] = $obj;
            }
        }

        if ($this->CheckPermission('Modify Site Preferences')) {
            $obj = new CMSMS\AdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'layout';
            $obj->title = $this->Lang('prompt_action_settings');
            $obj->description = $this->Lang('title_action_settings');
            $obj->action = 'admin_settings';
            $obj->name = 'set';
            $obj->icon = false;
            $out[] = $obj;
        }
*/
        return $out;
    }

    public function GetEventHelp( $eventname )
    {
        return $this->Lang('event_help_'.strtolower($eventname));
    }

    public function GetEventDescription( $eventname )
    {
        return $this->Lang('event_desc_'.strtolower($eventname));
    }
} // class
