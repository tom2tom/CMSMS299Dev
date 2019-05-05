<?php
# Clear locks
# Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\Lock;
use CMSMS\LockOperations;
use CMSMS\UserOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();

$userid = get_userid();
$urlext = get_secure_param();
$themeObject = cms_utils::get_theme_object();

cleanArray($_REQUEST);

$type = (isset($_REQUEST['type']) ) ? trim($_REQUEST['type']) : 'template';
//TODO support groups
$type = strtolower($type);
switch( $type ) {
case 'tpl':
case 'templates':
case 'template':
	$type = 'template';
	$op = 'listtemplates';
	break;
case 'css':
case 'stylesheets':
case 'stylesheet':
	$type = 'stylesheet';
	$op = 'liststyles';
	break;
default:
	return;
}

$is_admin = UserOperations::get_instance($userid,1);
if( $is_admin ) {
	// clear all locks of type content
	$db = cmsms()->GetDb();
	$sql = 'DELETE FROM '.CMS_DB_PREFIX.Lock::LOCK_TABLE.' WHERE type = ?';
	$db->Execute($sql,[$type]);
	cms_notice("Cleared all $type locks");
} else {
	// clear only my locks
	LockOperations::delete_for_user($type);
	cms_notice("Cleared his own $type locks");
}

$themeObject->ParkNotice('info',lang_by_realm('layout','msg_lockscleared'));
redirect($op.'.php'.$urlext);
