<?php
/*
Procedure to clear locks
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

//NOTE since 2.99, something like this is performed by an async Job

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\LockOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$type = trim($_REQUEST['type'] ?? 'template'); // no sanitizeVal() cuz only specific values recognized
$type = strtolower($type);
switch ($type) {
case 'tpl':
case 'templates':
	$type = 'template';
	// no break here
case 'template':
	$op = 'listtemplates';
	break;
case 'css':
case 'stylesheets':
	$type = 'stylesheet';
	// no break here
case 'stylesheet':
	$op = 'liststyles';
	break;
//TODO support groups, themes etc
//case 'themes':
//$type = 'theme';
//case 'theme':
//	$op = TM module action TODO
//	break;
default:
	return;
}

$userid = get_userid();
$is_admin = AppSingle::UserOperations()->UserInGroup($userid,1);
if ($is_admin) {
	// clear all locks of type content
	$db = cmsms()->GetDb();
	$sql = 'DELETE FROM '.CMS_DB_PREFIX.LockOperations::LOCK_TABLE.' WHERE type = ?';
	$db->Execute($sql,[$type]);
	cms_notice("Cleared all $type locks");
} else {
	// clear only my locks
	LockOperations::delete_for_user($type);
	cms_notice("Cleared his own $type locks");
}

AppSingle::Theme()->ParkNotice('info',lang_by_realm('layout','msg_lockscleared'));
$urlext = get_secure_param();
redirect($op.'.php'.$urlext);
