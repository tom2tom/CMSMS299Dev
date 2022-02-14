<?php
/*
Stylesheeet(s) operations performer
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

use CMSMS\SingleItem;
use CMSMS\StylesheetOperations;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();
$userid = get_userid();
$pmod = check_permission($userid,'Manage Stylesheets');
$themeObject = SingleItem::Theme();

if (isset($_REQUEST['css'])) {
	$css_id = (int)$_REQUEST['css']; //< 0 for a group
} elseif (isset($_REQUEST['grp'])) {
	$css_id = -(int)$_REQUEST['grp'];
} else {
	$css_id = null;
}

if (isset($_REQUEST['css_select'])) {  //id(s) array for a bulk operation
	//sanitize
	$css_multi = array_map($_REQUEST['css_select'], function ($v) {
		return (int)$v;
	});
} else {
	$css_multi = null;
}

$op = $_REQUEST['op'] ?? ''; // no sanitizeVal() etc, only exact matches accepted
switch (trim($op)) {
	case 'copy':
		if (!$pmod) exit;
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_copy($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				// TODO message if group(s) copied i.e. $css_id includes val(s) < 0
				$themeObject->ParkNotice($type,_ld('layout','msg_stylesheet_copied'));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'delete':
		if (!$pmod) exit;
		if ($css_multi) { $css_id = $css_multi; }
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_delete($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				// TODO message if group(s) deleted
				$themeObject->ParkNotice($type,_ld('layout','msg_stylesheet_deleted',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'deleteall':
		if (!$pmod) exit;
		if ($css_multi) { $css_id = $css_multi; }
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_deleteall($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'replace':
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_replace($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		// multi for this one?
		break;
	case 'append':
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_append($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'prepend':
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_prepend($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'remove':
		//multi for this one ?
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_remove($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'import':
		if (!$pmod) exit;
		if ($css_multi) { $css_id = $css_multi; }
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_import($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				// TODO message if group(s) imported
				$themeObject->ParkNotice($type,_ld('layout','msg_stylesheet_imported',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'export':
		if (!$pmod) exit;
		if ($css_multi) { $css_id = $css_multi; }
		if ($css_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = StylesheetOperations::operation_export($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				// TODO message if group(s) exported
				$themeObject->ParkNotice($type,_ld('layout','msg_stylesheet_exported',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
}

$root = SingleItem::Config()['admin_url']; // relative URL's don't work here
$urlext = get_secure_param();
redirect($root.'/liststyles.php'.$urlext);  // TODO .'&_activetab=' relevant tab name : 'sheets'|'groups'
