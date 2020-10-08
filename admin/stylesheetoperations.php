<?php
# Stylesheeet(s) operations performer
# Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppState;
use CMSMS\StylesheetOperations;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
	exit;
}

check_login();
$userid = get_userid();
$pmod = check_permission($userid,'Manage Stylesheets');
$urlext = get_secure_param();
$themeObject = Utils::get_theme_object();

cleanArray($_REQUEST);
$css_id = isset($_REQUEST['css']) ? (int)$_REQUEST['css'] : null; //< 0 for a group
$css_multi = $_REQUEST['css_select'] ?? null; //id(s) array for a bulk operation

switch ($_REQUEST['op']) {
	case 'copy':
		if (!$pmod) exit;
		if ($css_id) {
			try {
				$n = StylesheetOperations::operation_copy($css_id);
				$themeObject->ParkNotice('success',lang_by_realm('layout','msg_stylesheet_copied'));
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
				$n = StylesheetOperations::operation_delete($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_stylesheet_deleted',$n));
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
				$n = StylesheetOperations::operation_deleteall($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'replace':
		if ($css_id) {
			try {
				$n = StylesheetOperations::operation_replace($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		// multi for this one?
		break;
	case 'append':
		if ($css_id) {
			try {
				$n = StylesheetOperations::operation_append($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'prepend':
		if ($css_id) {
			try {
				$n = StylesheetOperations::operation_prepend($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'remove':
		//multi for this one ?
		if ($css_id) {
			try {
				$n = StylesheetOperations::operation_remove($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_pages_updated',$n));
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
				$n = StylesheetOperations::operation_import($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_stylesheet_imported',$n));
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
				$n = StylesheetOperations::operation_export($css_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_stylesheet_exported',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
}

redirect('liststyles.php'.$urlext);
