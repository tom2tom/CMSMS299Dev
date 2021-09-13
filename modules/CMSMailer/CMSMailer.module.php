<?php
/*
CMSMailer module: send email via intra-site mechanism or external platform.
Copyright (C) 2008-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This module is a component of CMS Made Simple.

This module is free software; you may redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

This module is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AdminMenuItem;
use CMSMS\CoreCapabilities;
use CMSMS\HookOperations;

if (!extension_loaded('mbstring'))
{
    echo '<h1 style="color:red;">ERROR: PHP&quot;s "Multibyte String" extension is required by the mailer class in the CMSMailer module</h1>';
    return;
}

class CMSMailer extends CMSModule
{
    public $platformed = false; // const: whether to support some mass-mailers like MailChimp

    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return ''; }
    public function GetAdminDescription() { return $this->Lang('publictip'); }
    public function GetAdminSection() { return 'extensions'; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('publicname'); }
    public function GetHelp() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'modhelp.htm'); }
    public function GetVersion() { return '6.3'; }
    public function HasAdmin() { return true; }
    public function IsAdminOnly() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function MinimumCMSVersion() { return '2.99'; }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }

    public function VisibleToAdminUser()
    {
        $names = [
            'Modify Site Preferences',
            'Modify Mail Preferences',
        ];
        if ($this->platformed) {
            $names += [
                'ModifyEmailGateways',
                'ViewEmailGateways',
//N/A           'ModifyEmailTemplates', maybe for changing 'this is a CC' message ?
            ];
        }
        return $this->CheckPermission($names);
    }

    public function GetAdminMenuItems()
    {
        $out = [];

        if ($this->VisibleToAdminUser()) {
            // user is entitled to see the main page in the navigation
            $obj = AdminMenuItem::from_module($this);
            $obj->title = $this->Lang('settings_title');
            $out[] = $obj;
        }

        return $out;
    }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CoreCapabilities::CORE_MODULE:
            case CoreCapabilities::EMAIL_MODULE:
            case CoreCapabilities::SITE_SETTINGS:
                return true;
            default:
                return false;
        }
    }

    public function InitializeAdmin()
    {
        if ($this->VisibleToAdminUser()) { // TODO during async processing this is called when there is no user!
            HookOperations::add_hook('ExtraSiteSettings', [$this, 'ExtraSiteSettings']);
        }
    }

    /**
     * Hook function to populate 'centralised' site settings UI
     * @internal
     * @return array
     */
    public function ExtraSiteSettings()
    {
        return [
          'title' => $this->Lang('settings_title'),
        //'desc' => 'useful text goes here', // optional useful text
          'url' => $this->create_action_url('m1_', 'defaultadmin', ['activetab'=>'internal']), // if permitted
        //optional 'text' => custom link-text | explanation e.g. need permission
        ];
    }
}
