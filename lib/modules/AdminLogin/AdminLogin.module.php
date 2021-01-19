<?php
/*
Module: AdminLogin - standalone and theme-managed login/out
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
use CMSMS\Crypto;
use CMSMS\IAuthModuleInterface;

/**
 * Module: admin login/out processor
 * @since 2.99
 * @package CMS
 * @license GPL
 */
class AdminLogin extends CMSModule implements IAuthModuleInterface
{
    // minimum methods used by metafile processor
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetName() { return 'AdminLogin'; }
    public function GetVersion() { return '0.2'; }
    public function IsAdminOnly() { return true; }
    public function MinimumCMSVersion() { return '2.8.900'; }

    public function GetChangeLog()
    {
        return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'changelog.htm');
    }

    public function GetHelp()
    {
        return ''.@file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'modhelp.htm');
    }

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

    // interface methods

    /**
     * Process the current login 'phase', and generate appropriate
     * page-content for use by caller
     * No header / footer inclusions (js, css) are done (i.e. assumes upstream does that)
     * @return array including login-form content and related parameters
     */
    public function StageLogin() : array
    {
        //parameters for included function
        $usecsrf = true;
        $config = AppSingle::Config();

        $fp = cms_join_path(__DIR__, 'function.login.php');
        require_once $fp;

        $csrf = Crypto::random_string(16, true); //encryption-grade hash not needed
        $_SESSION[$csrf_key] = $csrf;

        $smarty = AppSingle::App()->GetSmarty();
        $smarty->assign('mod', $this)
         ->assign('actionid', '')
         ->assign('loginurl', 'login.php')
         ->assign('forgoturl', 'login.php?forgotpw=1')
         ->assign('csrf', $csrf)
         ->assign('changepwhash', $changepwhash ?? '')
         ->assign('iserr', !empty($errmessage));
        if (!empty($tplvars)) $smarty->assign($tplvars);

        $saved = $smarty->template_dir;
        $smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
        $data = ['form' => $smarty->fetch('core.tpl')];
        $smarty->template_dir = $saved;

        //some results from included function also for upstream
        if (!empty($tplvars)) $data += $tplvars;
        if (!empty($infomessage)) $data['infomessage'] = $infomessage;
        if (!empty($warnmessage)) $data['warnmessage'] = $warnmessage;
        if (!empty($errmessage)) $data['errmessage'] = $errmessage;
        if (!empty($changepwhash)) $data['changepwhash'] = $changepwhash;
        if (!empty($changepwtoken)) $data['changepwtoken'] = $changepwtoken;

        return $data;
    }

    /**
     * Perform the entire login process without theme involvement
     */
    public function RunLogin()
    {
        $id = '__';
        $params = AppSingle::ModuleOperations()->GetModuleParameters($id);
        $this->DoAction('admin_login', $id, $params, null);
    }
} // class
