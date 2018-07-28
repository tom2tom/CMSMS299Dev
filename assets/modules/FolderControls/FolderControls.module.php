<?php
/*
FolderControls module for CMSMS
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

final class FolderControls extends CMSModule
{
    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'extensions'; }
    public function GetAuthor() { return 'tomph'; }
    public function GetAuthorEmail() { return 'tomph@cmsmadesimple.org'; }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help'); }
    public function GetName() { return 'FolderControls'; }
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
        return $this->CheckPermission('Modify Site Preferences');
    }

    public function GetChangeLog()
    {
        return ''.@file_get_contents(cms_join_path(__DIR__, 'lib', 'doc', 'changelog.htm'));
    }

    public function GetAdminMenuItems()
    {
        if ($this->VisibleToAdminUser()) {
            return [CmsAdminMenuItem::from_module($this)];
        }
    }

    /**
     * Get array of access-control properties for folder $dirpath
     * @param string $dirpath absolute or otherwise-php-discoverable filepath
     * @return array of parameters
     */
    public function GetControls(string $dirpath) : array
    {
        $ob = new FolderControls\ControlSet();
        return $ob->get_for_folder($dirpath);
    }
} // class
