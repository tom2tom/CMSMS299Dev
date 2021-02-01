<?php
/*
CMSMS admin-logout processing
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$_SESSION['logout_user_now'] = '1';
$modname = AppParams::get('loginmodule'); // = 'AdminLogin'; // DEBUG
if ($modname) {
	$ops = AppSingle::ModuleOperations();
	$modinst = $ops->get_module_instance($modname, '', true);
	if ($modinst) {
		$modinst->RunLogin();
	} else {
		exit('Invalid login module');
	}
} else {
	$name = AppParams::get('logintheme');
	$themeObject = Utils::get_theme_object($name);
	if ($themeObject) {
		$themeObject->display_customlogin_page();
	} else {
		exit('Invalid login theme');
	}
}
