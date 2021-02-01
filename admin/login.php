<?php
/*
CMSMS admin-login processing
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
AppState::add_state(AppState::STATE_LOGIN_PAGE);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$name = AppParams::get('loginprocessor');
if (!$name) {
	$name = AppParams::get('logintheme');
	$themeObject = AdminTheme::get_instance($name);
	if ($themeObject) {
		if (method_exists($themeObject, 'display_login_page')) {
			$themeObject->display_login_page();
		} else {
			$name = 'module'; // fall back to using the default
		}
	} else {
		exit('Invalid login theme');
	}
}

$ops = AppSingle::ModuleOperations();
if ($name == 'module') {
	$name = AppParams::get('loginmodule', $ops::STD_LOGIN_MODULE);
}
$modinst = $ops->get_module_instance($name, '', true);
if ($modinst) {
	try {
		$modinst->display_login_page();
	} catch (Thowable $t) {
		exit($t->GetMessage());
	}
} else {
	exit('Invalid login module');
}
