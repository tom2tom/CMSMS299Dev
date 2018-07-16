<?php
/*
Module: CoreAdminLogin - supports themes' login/out processes
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\internal\Smarty;

/**
 * Module: admin login/out processor
 * @since 2.3
 * @package CMS
 * @license GPL
 */

class CoreAdminLogin extends CMSModule //uses CMSMS\AdminLogin
{
    // minimum methods used by metafile processor
    public function GetAuthor() { return 'Robert Campbell'; }
    public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }
    public function GetName() { return 'CoreAdminLogin'; }
    public function GetVersion() { return '0.2'; }
    public function IsAdminOnly() { return true; }

    public function GetChangeLog()
    {
        return ''.@file_get_contents(cms_join_path(__DIR__,'lib','doc','changelog.htm'));
    }

    public function GetHelp()
    {
        return ''.@file_get_contents(cms_join_path(__DIR__,'lib','doc','modhelp.htm'));
    }

    public function HasCapability($capability, $parts = [])
    {
        if ($capability == 'adminlogin') return true;
    }

    // interface methods

    /**
     * @return stuff usable upstream (but only if further processing is needed)
     */
    public function ProcessRequest() : array
    {
        //parameters for included code
        $usecsrf = true;
        $config = cms_config::get_instance();

        $fp = cms_join_path(__DIR__, 'function.login.php');
        require_once $fp;

        $smarty = Smarty::get_instance();

        $smarty->assign('mod', $this);
        $csrf = bin2hex(random_bytes(16));
        $smarty->assign('csrf', $csrf);
        $smarty->assign('changepwhash', $changepwhash ?? '');

        $parts = [];
        //some results from the included function
        $parts['infomessage'] = $infomessage ?? '';
        $parts['warnmessage'] = $warnmessage ?? '';
        $parts['errmessage'] = $errmessage ?? '';
        $smarty->assign($parts);

        $saved = $smarty->template_dir;
        $smarty->template_dir = __DIR__ . DIRECTORY_SEPARATOR . 'templates';
        $form = $smarty->fetch('core.tpl');
        $smarty->template_dir = $saved;

        $_SESSION[$csrf_key] = $csrf;
        //$themeobj = cms_utils::get_theme_object();
        //TODO setup any specific themeobject header / footer inclusions

        $parts['changepwtoken'] = $changepwtoken ?? '';
        $parts['form'] = $form;

        return $parts;
    }

    public function DoLogin() {}

    public function GrantAccess() {}
} // class
