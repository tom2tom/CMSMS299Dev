<?php
#-------------------------------------------------------------------------
# CMSContentManager - A CMSMS module to provide page-content management.
# Copyright (C) 2013-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
#-------------------------------------------------------------------------

final class CMSContentManager extends CMSModule
{
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetVersion() { return '1.2'; }
    public function GetHelp() { return CmsLangOperations::lang_from_realm('help','help_cmscontentmanager_help'); }
    public function GetAuthor() { return 'calguy1000'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetChangeLog() { return @file_get_contents(dirname(__FILE__).'/changelog.inc'); }
    public function IsPluginModule() { return FALSE; }
    public function HasAdmin() { return TRUE; }
    public function LazyLoadAdmin() { return TRUE; }
    public function LazyLoadFrontend() { return TRUE; }
    public function GetAdminSection() { return 'content'; }
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function MinimumCMSVersion() { return "1.99-alpha0"; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
    public function UninstallPreMessage() { return $this->Lang('preuninstall'); }

    /**
     * Tests wether the currently logged in user has the ability to edit ANY content page
     */
    public function CanEditContent($content_id = -1)
    {
        if( $this->CheckPermission('Manage All Content') ) return TRUE;
        if( $this->CheckPermission('Modify Any Page') ) return TRUE;

        $pages = author_pages(get_userid(FALSE));
        if( count($pages) == 0 ) return FALSE;

        return $content_id <= 0 || in_array($content_id,$pages);
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
            $obj->section = 'siteadmin';
            $obj->title = $this->Lang('title_contentmanager_settings');
            $obj->description = $this->Lang('desc_contentmanager_settings');
            $obj->action = 'admin_settings';
            $out[] = $obj;
        }
        return $out;
    }

} // class
