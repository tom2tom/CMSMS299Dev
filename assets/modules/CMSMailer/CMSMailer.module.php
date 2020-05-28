<?php
# CMSMailer light-module: a wrapper around an external email manager
# Copyright (C) 2015-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMailer\Mailer;
use CMSMS\AdminMenuItem;
use CMSMS\CoreCapabilities;
use CMSMS\HookOperations;
use CMSMS\IResource;
use CMSMS\ResourceMethods;

class CMSMailer implements IResource
{
    protected $mailer;
    protected $methods;

    public function __call($name, $args)
    {
        if (!isset($this->mailer)) {
            $this->mailer = new Mailer();
        }
        if (method_exists($this->mailer, $name)) {
            return call_user_func([$this->mailer, $name], ...$args);
        }

        if (!isset($this->methods)) {
            $this->methods = new ResourceMethods($this, __DIR__);
        }
        if (method_exists($this->methods, $name)) {
            return call_user_func([$this->methods, $name], ...$args);
        }
    }

    public function GetAdminDescription() { return $this->Lang('moddescription'); }
    public function GetAdminSection() { return 'extensions'; }
    public function GetChangeLog() { return file_get_contents(dirname(__FILE__).DIRECTORY_SEPARATOR.'changelog.inc'); }
    public function GetDependencies() { return []; }
    public function GetFriendlyName() { return $this->Lang('friendlyname'); }
    public function GetHelp() { return $this->Lang('help_module'); }
    public function GetVersion() { return '6.3.0'; }
    public function HasAdmin() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function MinimumCMSVersion() { return '2.8.900'; }
    public function UninstallPreMessage() { return $this->Lang('confirm_uninstall'); }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission('Modify Mail Preferences') ||
               $this->CheckPermission('Modify Site Preferences');
    }

    public function GetAdminMenuItems()
    {
        if( $this->VisibleToAdminUser() ) return [AdminMenuItem::from_module($this)];
        return [];
    }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
//          case CoreCapabilities::TASKS: CHECKME ANY NEEDED?
            case CoreCapabilities::SITE_SETTINGS:
            case 'handles_email': //TODO CoreCapabilities enum value for this
                return true;
        }
        return false;
    }

    public function InitializeAdmin()
    {
        HookOperations::add_hook('ExtraSiteSettings', [$this, 'ExtraSiteSettings']);
    }

    /**
     * Hook function to populate 'centralised' site settings UI
     * @internal
     * @return array
     */
    public function ExtraSiteSettings()
    {
        //TODO check permission $this->VisibleToAdminUser()
        return [
         'title' => $this->Lang('settings_title'),
         //'desc' => 'useful text goes here', // optional useful text
         'url' => $this->create_url('m1_','defaultadmin'), // if permitted
         //optional 'text' => custom link-text | explanation e.g need permission
        ];
    }
} // class
