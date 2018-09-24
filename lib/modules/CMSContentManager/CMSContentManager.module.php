<?php
# CMSContentManager - A CMSMS module to provide page-content management.
# Copyright (C) 2013-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

final class CMSContentManager extends CMSModule
{
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetVersion() { return '1.2'; }
    public function GetHelp() { return $this->Lang('help_module'); }
    public function GetAuthor() { return 'calguy1000'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetChangeLog() { return @file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.inc'); }
    public function HasAdmin() { return true; }
    public function LazyLoadAdmin() { return true; }
    public function LazyLoadFrontend() { return true; }
    public function GetAdminSection() { return 'content'; }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function IsAdminOnly() { return true; }
    public function MinimumCMSVersion() { return '1.99-alpha0'; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
    public function UninstallPreMessage() { return $this->Lang('preuninstall'); }

    /**
     * Tests whether the currently logged in user has the ability to edit ANY content page
     */
    public function CanEditContent($content_id = -1)
    {
        if( $this->CheckPermission('Manage All Content') ) return true;
        if( $this->CheckPermission('Modify Any Page') ) return true;

        $pages = author_pages(get_userid(false));
        if( count($pages) == 0 ) return false;

        return $content_id <= 0 || in_array($content_id,$pages);
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
            $out .= sprintf($fmt,$urlpath,$one)."\n";
        }
        return $out;
    }

    public function GetAdminMenuItems()
    {
        $out = [];

        // user is entitled to see the main page in the navigation.
        if( $this->CheckPermission('Add Pages') || $this->CheckPermission('Remove Pages') || $this->CanEditContent() ) {
            $obj = CmsAdminMenuItem::from_module($this);
            $out[] = $obj;
        }

        if( $this->CheckPermission('Modify Site Preferences') ) {
            $obj = new CmsAdminMenuItem();
            $obj->module = $this->GetName();
            $obj->section = 'content';
            $obj->title = $this->Lang('title_contentmanager_settings');
            $obj->description = $this->Lang('desc_contentmanager_settings');
			$obj->icon = false;
            $obj->action = 'admin_settings';
            $out[] = $obj;
        }
        return $out;
    }

} // class
