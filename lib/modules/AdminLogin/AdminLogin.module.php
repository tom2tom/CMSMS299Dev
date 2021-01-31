<?php
/*
CMSMS module: AdminLogin - supports module-managed and theme-managed login/out
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppSingle;
use CMSMS\CoreCapabilities;
//use CMSMS\Crypto;
use CMSMS\IAuthModule;

/**
 * Module: admin login/out processor
 * @since 2.99
 * @package CMS
 * @license GPL
 */
class AdminLogin extends CMSModule implements IAuthModule
{
//    public function GetAdminDescription() { return $this->Lang('publictip'); }
//    public function GetAdminSection() { return 'extensions'; }
    public function GetAuthor() { return ''; }
    public function GetAuthorEmail() { return ''; }
    public function GetChangeLog() { return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm'); }
    public function GetFriendlyName() { return $this->Lang('publicname'); }
    public function GetHelp() { return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'modhelp.htm'); }
    public function GetName() { return 'AdminLogin'; }
    public function GetVersion() { return '0.2'; }
    public function IsAdminOnly() { return true; }
    public function MinimumCMSVersion() { return '2.99.0'; }
//    public function HasAdmin() { return false; }
//    public function VisibleToAdminUser() { return false; }

    public function HasCapability($capability, $params = [])
    {
        switch ($capability) {
            case CoreCapabilities::CORE_MODULE:
            case CoreCapabilities::LOGIN_MODULE:
                return true;
            default:
                return false;
        }
    }

    // IAuthModule interface methods

    /**
     * Process the current login 'phase', and generate appropriate
     * login-page-content for use by caller
     *
     * @return array with member 'form' comprising login-form content,
     *  plus related parameters
     */
    public function fetch_login_panel() : array
    {
        $id = '';
        require_once __DIR__ . DIRECTORY_SEPARATOR . 'method.fetchpanel.php';
        return $data;
    }

    /**
     * Perform the current stage of the login process
     */
    public function display_login_page()
    {
        $id = 'm1_';
        $params = AppSingle::ModuleOperations()->GetModuleParameters($id);
        echo $this->DoAction('login', $id, $params, null);
    }
} // class
