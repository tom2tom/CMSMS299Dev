<?php
/*
OutMailer module: send email via intra-site mechanism or external platform.
Copyright (C) 2005-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This module is a component of CMS Made Simple.

This module is free software; you may redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of that license, or
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
    echo '<h1 style="color:red;">ERROR: PHP&quot;s "Multibyte String" extension is required by the mailer class in the OutMailer module</h1>';
    return;
}

/**
 * @since 3.0
 * Formerly CMSMailer module & class
 */
class OutMailer extends CMSModule
{
    public function GetAdminDescription() { return $this->Lang('publictip'); }
    public function GetAdminSection() { return 'extensions'; }
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return ''; }
    public function GetChangeLog() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('publicname'); }
    public function GetHelp() { return file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'modhelp.htm'); }
    public function GetName() { return 'OutMailer'; }
    public function GetVersion() { return '6.3'; }
    public function HasAdmin() { return true; }
    public function IsAdminOnly() { return true; }
    public function InstallPostMessage() { return $this->Lang('postinstall'); }
    public function MinimumCMSVersion() { return '2.999'; }
    public function UninstallPostMessage() { return $this->Lang('postuninstall'); }
//  public function UninstallPreMessage() { return $this->Lang('really_uninstall'); }

    public function VisibleToAdminUser()
    {
        return $this->CheckPermission([
           'Modify Site Preferences',
           'Modify Mail Preferences',
           'Modify Email Gateways',
           'View Email Gateways',
//         'Modify Email Templates', maybe for changing 'this is a CC' message ?
         ]);
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
          'url' => $this->create_action_url('', 'defaultadmin', ['activetab'=>'internal']), // if permitted
        //optional 'text' => custom link-text | explanation e.g. need permission
        ];
    }
}

\class_alias(OutMailer::class, 'CMSMailer', false);
