<?php
#Module: admin login/out
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

/**
 * Module: admin login/out
 * @since 2.3
 * @package CMS
 * @license GPL
 */

class CoreAdminLogin extends CMSModule
{
    public $loginaction = 'admin_login';
    public $logoutaction = 'admin_login';
	// mimimal methods used by metafile processor
    public function GetName() { return 'CoreAdminLogin'; }
    public function GetVersion() { return '0.0.2'; }
	public function GetAuthor() { return 'Robert Campbell'; }
	public function GetAuthorEmail() { return 'calguy1000@cmsmadesimple.org'; }

	public function GetChangeLog()
	{
		return ''.@file_get_contents(cms_join_path(__DIR__,'lib','doc','changelog.htm'));
	}

	public function GetHelp()
	{
		return ''.@file_get_contents(cms_join_path(__DIR__,'lib','doc','modhelp.htm'));
	}

    public function DoAction($name, $id, $params, $returnid = '')
    {
        switch( $name ) {
            case 'default':
            case 'login':
                $name = $this->loginaction;
                break;
            case 'logout':
                $name = $this->logoutaction;
                break;
        }
        return parent::DoAction($name, $id, $params, $returnid);
    }

    public function HasCapability($capability, $params = [])
    {
        if( $capability == 'adminlogin' ) return true;
    }

    protected function getLoginUtils()
    {
        static $_obj = null;
        if( !$_obj ) $_obj = new \CoreAdminLogin\LoginUtils( $this );
        return $_obj;
    }
} // class
