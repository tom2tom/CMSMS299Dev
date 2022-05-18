<?php
/*
CMSMS admin-login processing
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
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
use CMSMS\AppState;
use CMSMS\Lone;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
AppState::set(AppState::LOGIN_PAGE | AppState::ADMIN_PAGE);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$name = AppParams::get('loginprocessor');
if (!$name) {
	$name = AppParams::get('logintheme');
	$themeObject = AdminTheme::get_instance($name);
	if ($themeObject) {
		if (method_exists($themeObject, 'display_login_page')) {
			$themeObject->display_login_page();
			exit;
		} else {
			$name = 'module'; // fall back to using the default
		}
	} else {
		exit('Invalid login theme');
	}
}

$ops = Lone::get('ModuleOperations');
if ($name == 'module') {
	$mod = $ops->GetAdminLoginModule();
} else {
	$mod = $ops->get_module_instance($name, '', true);
}
if ($mod) {
	try {
		$mod->display_login_page();
	} catch (Thowable $t) {
		exit($t->GetMessage());
	}
} else {
	exit('Invalid login module');
}
