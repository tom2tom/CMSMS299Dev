<?php
/*
Template(s) operations performer
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
use CMSMS\TemplateOperations;
use CMSMS\TemplateType;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();
$userid = get_userid();
$pmod = check_permission($userid,'Manage Templates');
$themeObject = SingleItem::Theme();

if (isset($_REQUEST['tpl'])) {
	$tpl_id = (int)$_REQUEST['tpl']; //< 0 for a group
} elseif (isset($_REQUEST['grp'])) {
	$tpl_id = -(int)$_REQUEST['grp'];
} else {
	$tpl_id = null;
}

if (isset($_REQUEST['tpl_select'])) {  //id(s) array for a bulk operation
	//sanitize
	$template_multi = array_map($_REQUEST['tpl_select'], function ($v) {
		return (int)$v;
	});
} else {
	$template_multi = null;
}

$op = $_REQUEST['op'] ?? ''; // no sanitizeVal() etc due to specific acceptable values
switch (trim($op)) {
	case 'copy':
		$padd = $pmod || check_permission($userid,'Add Templates');
		if (!$padd) exit;
		if ($tpl_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = TemplateOperations::operation_copy($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				// TODO message if group(s) copied $tpl_id includes val(s) < 0
				$themeObject->ParkNotice($type,_ld('layout','msg_template_copied'));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'delete':
		if (!$pmod) exit;
		if ($template_multi) { $tpl_id = $template_multi; }
		if ($tpl_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = TemplateOperations::operation_delete($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				// TODO message if group(s) deleted
				$themeObject->ParkNotice($type,_ld('layout','msg_template_deleted',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'deleteall':
		if (!$pmod) exit;
		if ($template_multi) { $tpl_id = $template_multi; }
		if ($tpl_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = TemplateOperations::operation_deleteall($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'replace':
		if ($tpl_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = TemplateOperations::operation_replace($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'applyall':
		if ($tpl_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = TemplateOperations::operation_applyall($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_pages_updated',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'reset':
		if (!$pmod) exit;
		if (!empty($_REQUEST['type'])) {
			//value might be integer template-type id, or a string like Originator::Name
			if (is_numeric($_REQUEST['type'])) {
				$id = (int)$_REQUEST['type'];
			} else {
				$id = sanitizeVal($_REQUEST['type'],CMSSAN_PUNCT); // OR CMSSAN_PURESPC? OR CMSSAN_PURE?
			}
			try {
				$type = TemplateType::load($id);
				$type->reset_content_to_factory();
				$type->save();
				$themeObject->ParkNotice('success',_ld('layout','msg_template_reset',$type->get_langified_display_value()));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		} else {
			$themeObject->ParkNotice('error',_ld('layout','error_missingparam'));
		}
		// TODO appropriate '_activetab' value
		break;
	case 'import':
		if (!$pmod) exit;
		if ($template_multi) { $tpl_id = $template_multi; }
		if ($tpl_id) {
			try {
				$n = TemplateOperations::operation_import($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_template_imported',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
	case 'export':
		if (!$pmod) exit;
		if ($template_multi) { $tpl_id = $template_multi; }
		if ($tpl_id) {
			try {
				// TODO appropriate '_activetab' value
				$n = TemplateOperations::operation_export($tpl_id);
				$type = ($n > 0) ? 'success' : 'info';
				$themeObject->ParkNotice($type,_ld('layout','msg_template_exported',$n));
			} catch (Throwable $t) {
				$themeObject->ParkNotice('error',$t->getMessage());
			}
		}
		break;
}

$root = SingleItem::Config()['admin_url']; // relative URL's don't work here
$urlext = get_secure_param();
redirect($root.'/listtemplates.php'.$urlext); // TODO .'&_activetab=' relevant tab name 'templates'|'types'|'groups'
