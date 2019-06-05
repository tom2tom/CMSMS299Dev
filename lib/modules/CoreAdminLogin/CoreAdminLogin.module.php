<?php
/*
Module: CoreAdminLogin - standalone and theme-support login/out
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
BUT WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\IAuthModuleInterface;

/**
 * Module: admin login/out processor
 * @since 2.3
 * @package CMS
 * @license GPL
 */

class CoreAdminLogin extends CMSModule implements IAuthModuleInterface
{
    // minimum methods used by metafile processor
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetName() { return 'CoreAdminLogin'; }
    public function GetVersion() { return '0.2'; }
    public function IsAdminOnly() { return true; }

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
        return ($capability == 'adminlogin');
    }

    // interface methods

    /**
     * Process the current login 'phase', and generate appropriate page-content
	 * for use upstream
     * No header / footer inclusions (js, css) are done (i.e. assumes upstream does that)
     * @return array including login-form content and related parameters
     */
    public function StageLogin() : array
    {
        //parameters for included function
        $usecsrf = true;
        $config = cms_config::get_instance();

        $fp = cms_join_path(__DIR__, 'function.login.php');
        require_once $fp;

        $csrf = bin2hex(random_bytes(16));

        $smarty = CmsApp::get_instance()->GetSmarty();
        $smarty->assign('mod', $this)
         ->assign('actionid', '')
         ->assign('loginurl', 'login.php')
         ->assign('forgoturl', 'login.php?forgotpw=1')
         ->assign('csrf', $csrf)
         ->assign('changepwhash', $changepwhash ?? '')
         ->assign('iserr', !empty($errmessage));

        $saved = $smarty->template_dir;
        $smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
        $data = ['form' => $smarty->fetch('core.tpl')];
        $smarty->template_dir = $saved;

        $_SESSION[$csrf_key] = $csrf;

        //some results from included function also for upstream
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
        $fp = cms_join_path(__DIR__, 'action.login.php');
        require_once $fp;
    }
} // class
