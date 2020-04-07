<?php
#CMSMS admin-login processing
#Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AppState;
use CMSMS\ModuleOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
AppState::add_state(AppState::STATE_LOGIN_PAGE);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$modname = cms_siteprefs::get('loginmodule');
if ($modname) {
/*  $params = (new GetParameters())->get_action_values(['module','id','action']);
	$module = $params['module'];
	if ($module && $module != $modname) throw new RuntimeException('Invalid login-module parameter');
	$id = $params['id'];
	if (!$id) $id = '__';
	$action = $params['action'];
	if (!$action) $action = 'admin_login';
*/
	$modops = ModuleOperations::get_instance();
	$modinst = $modops->get_module_instance($modname, '', true);
	if (!$modinst) {
		throw new RuntimeException('Invalid login module');
	}
/*	$params = array_diff_key($params, ['module'=>1,'id'=>1,'action'=>1]);
	$content = $modinst->DoActionBase($action, $id, $params, null, $smarty);

	$themeobj = cms_utils::get_theme_object();
	$themeobj->SetTitle($modinst->Lang('logintitle'));
	$themeobj->set_content($content);

	cms_admin_sendheaders();
	header('Content-Language: ' . CmsNlsOperations::get_current_language());
	echo $themeobj->do_loginpage('login');
*/
    $modinst->RunLogin();
} else {
	$themename = cms_siteprefs::get('logintheme');
	$themeobj = cms_utils::get_theme_object($themename);
	if ($themeobj) {
		$themeobj->do_login();
	} else {
		throw new RuntimeException('Invalid login theme');
	}
}
