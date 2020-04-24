<?php
# Template(s) operations performer
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
use CMSMS\TemplateOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
	exit;
}

check_login();
$userid = get_userid();
$pmod = check_permission($userid,'Manage Templates');
$urlext = get_secure_param();
$themeObject = cms_utils::get_theme_object();

cleanArray($_REQUEST);
$template_id = isset($_REQUEST['tpl']) ? (int)$_REQUEST['tpl'] : null; //< 0 for a group
$template_multi = $_REQUEST['tpl_select'] ?? null;  //id(s) array for a bulk operation

switch ($_REQUEST['op']) {
	case 'copy':
		$padd = $pmod || check_permission($userid,'Add Templates');
		if( !$padd ) exit;
		if( $template_id ) {
			try {
				$n = TemplateOperations::operation_copy($tpl_id);
				$themeObject->ParkNotice('success',lang_by_realm('layout','msg_template_copied'));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'delete':
		if( !$pmod ) exit;
		if( $template_multi ) { $template_id = $template_multi; }
		if( $template_id ) {
			try {
				$n = TemplateOperations::operation_delete($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_template_deleted',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'deleteall':
		if( !$pmod ) exit;
		if( $template_multi ) { $template_id = $template_multi; }
		if( $template_id ) {
			try {
				$n = TemplateOperations::operation_deleteall($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'replace':
		if( $template_id ) {
			try {
				$n = TemplateOperations::operation_replace($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'applyall':
		if( $template_id ) {
			try {
				$n = TemplateOperations::operation_applyall($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'reset':
		if( !$pmod ) exit;
		if( !empty($_REQUEST['type']) ) {
			try {
				$type = CmsLayoutTemplateType::load($_REQUEST['type']);
				$type->reset_content_to_factory();
				$type->save();
				$themeObject->ParkNotice('success',lang_by_realm('layout','msg_template_reset',$type->get_langified_display_value()));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		} else {
			$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
		}
		redirect('listtemplates.php'.$urlext.'&_activetab=types');
		break;
	case 'import':
		if( !$pmod ) exit;
		if( $template_multi ) { $template_id = $template_multi; }
		if( $template_id ) {
			try {
				$n = TemplateOperations::operation_import($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_template_imported',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'export':
		if( !$pmod ) exit;
		if( $template_multi ) { $template_id = $template_multi; }
		if( $template_id ) {
			try {
				$n = TemplateOperations::operation_export($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,lang_by_realm('layout','msg_template_exported',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
}

redirect('listtemplates.php'.$urlext);
