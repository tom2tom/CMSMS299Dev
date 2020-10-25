<?php
#CMSContentManager - A CMSMS module to provide page-content management.
#Copyright (C) 2013-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AdminMenuItem;
use CMSMS\CoreCapabilities;
use CMSMS\HookOperations;

final class CMSContentManager extends CMSModule
{
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'content'; }
    public function GetAuthor() { return 'calguy1000'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'doc'.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help_module'); }
    public function GetVersion() { return '2.0'; }
    public function HasAdmin() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function IsAdminOnly() { return true; }
//    public function LazyLoadAdmin() { return true; }
//    public function LazyLoadFrontend() { return true; }
    public function MinimumCMSVersion() { return '2.2.911'; }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
    public function UninstallPreMessage() { return $this->Lang('preuninstall'); }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CoreCapabilities::CORE_MODULE:
            case CoreCapabilities::SITE_SETTINGS:
                return true;
            default:
                return false;
        }
    }

    public function InitializeAdmin()
    {
        HookOperations::add_hook('ExtraSiteSettings',[$this,'ExtraSiteSettings']);
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
         'url'=> $this->create_url('m1_','admin_settings'), // if permitted
         //optional 'text' => custom link-text | explanation e.g need permission
        ];
    }

    /**
     * Tests whether the currently logged in user has the ability to edit ANY content page
     */
    public function CanEditContent($content_id = -1)
    {
        if( $this->CheckPermission('Manage All Content') ) return true;
        if( $this->CheckPermission('Modify Any Page') ) return true;

        $pages = author_pages(get_userid(false));
        if( !$pages ) return false;
        if( $content_id <= 0 ) return true;
        return in_array($content_id,$pages);
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

        if( $this->CheckPermission('Add Pages') || $this->CheckPermission('Remove Pages') || $this->CanEditContent() ) {
            // user is entitled to see the main page in the navigation
            $obj = AdminMenuItem::from_module($this);
            $obj->title = $this->Lang('title_contentmanager');
            $out[] = $obj;
        }

        if( $this->CheckPermission('Modify Site Preferences') ) {
            $obj = new AdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'siteadmin';
            $obj->title = $this->Lang('title_contentmanager_settings');
            $obj->description = $this->Lang('desc_contentmanager_settings');
            $obj->icon = false;
            $obj->action = 'admin_settings';
            $out[] = $obj;
        }
        return $out;
    }

    public function GetContentEditor($page_id)
    {
        if( $page_id < 1 ) {
            //TODO create new object
            return null;
        }

        $db = cmsms()->GetDb();
        $params = $db->GetRow('SELECT * FROM '.CMS_DB_PREFIX.'content WHERE content_id=?',[$page_id]);
        if( $params ) {
            switch( $params['type'] ) {
                case 'content':
                case 'Content':
                    $type = 'Content';
                    break;
                case 'errorpage':
                case 'ErrorPage':
                    $type = 'ErrorPage';
                    break;
                case 'link':
                case 'Link':
                    $type = 'Link';
                    break;
                case 'pagelink':
                case 'PageLink':
                    $type = 'PageLink';
                    break;
                case 'sectionheader':
                case 'SectionHeader':
                    $type = 'SectionHeader';
                    break;
                case 'separator':
                case 'Separator':
                    $type = 'Separator';
                    break;
                default:
                    $type = null;
                    break;
            }
            if( $type ) {
                $classname = 'CMSContentManager\\contenttypes\\'.$type;
                return new $classname($params);
            } else {
                //TODO API needed to retrieve one of these
            }
        }
        return null;
    }
} // class
