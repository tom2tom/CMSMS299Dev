<?php
# copy template
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\TemplateOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$userid = get_userid();
$themeObject = cms_utils::get_theme_object();

if( !check_permission($userid,'Modify Templates') ) {
	// no manage-templates permission
	if( !check_permission($userid,'Add Templates') ) {
		// no add-templates permission
		if( !isset($_REQUEST['tpl']) || !TemplateOperations::user_can_edit_template($_REQUEST['tpl']) ) {
			// no parameter, or no ownership/addt_editors.
			return;
		}
	}
}

if( isset($_REQUEST['cancel']) ) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('listtemplates.php'.$urlext);
}
if( !isset($_REQUEST['tpl']) ) {
	$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
	redirect('listtemplates.php'.$urlext);
}

cleanArray($_REQUEST);

try {
	$orig_tpl = TemplateOperations::get_template($_REQUEST['tpl']);

	if( isset($_REQUEST['submit']) || isset($_REQUEST['apply']) ) {

		try {
			$new_tpl = clone($orig_tpl);
			$new_tpl->set_owner(get_userid());
			$new_tpl->set_name(trim($_REQUEST['new_name']));
			$new_tpl->set_additional_editors([]);
/*
			// only if have manage themes right.
			if( check_permission($userid,'Modify Designs') ) {
				$new_tpl->set_designs($orig_tpl->get_designs()); DISABLED
			}
			else {
				$new_tpl->set_designs([]);
			}
*/
			$new_tpl->save();

			if( isset($_REQUEST['apply']) ) {
				$themeObject->ParkNotice('info',lang_by_realm('layout','msg_template_copied_edit'));
				redirect('edittemplate,php'.$urlext.'&tpl='.$new_tpl->get_id());
			}
			else {
				$themeObject->ParkNotice('info',lang_by_realm('layout','msg_template_copied'));
				redirect('listtemplates.php'.$urlext);
			}
		}
		catch( CmsException $e ) {
			$themeObject->RecordNotice('error',$e->GetMessage());
		}
	}

	// build a display.
	$smarty = CmsApp::get_instance()->GetSmarty();

	$cats = CmsLayoutTemplateCategory::get_all();
	$out = [];
	$out[0] = lang_by_realm('layout','prompt_none');
	if( $cats ) {
		foreach( $cats as $one ) {
			$out[$one->get_id()] = $one->get_name();
		}
	}
	$smarty->assign('category_list',$out);

	$types = CmsLayoutTemplateType::get_all();
	if( $types ) {
		$out = [];
		foreach( $types as $one ) {
			$out[$one->get_id()] = $one->get_langified_display_value();
		}
		$smarty->assign('type_list',$out);
	}

/*    $designs = DesignManager\Design::get_all();
	if( $designs ) {
		$out = [];
		foreach( $designs as $one ) {
			$out[$one->get_id()] = $one->get_name();
		}
		$smarty->assign('design_list',$out);
	}
*/
	$userops = cmsms()->GetUserOperations();
	$allusers = $userops->LoadUsers();
	$tmp = [];
	foreach( $allusers as $one ) {
		$tmp[$one->id] = $one->username;
	}
	if( $tmp ) {
		$smarty->assign('user_list',$tmp);
	}

	$new_name = $orig_tpl->get_name();
	$p = strrpos($new_name,' -- ');
	$n = 2;
	if( $p !== FALSE ) {
		$n = (int)substr($new_name,$p+4)+1;
		$new_name = substr($new_name,0,$p);
	}

	$selfurl = basename(__FILE__);
	$new_name .= ' -- '.$n;
	$smarty->assign('new_name',$new_name)
	  ->assign('selfurl',$selfurl)
	  ->assign('urlext',$urlext)
	  ->assign('tpl',$orig_tpl);

	include_once 'header.php';
	$smarty->display('copytemplate.tpl');
	include_once 'footer.php';
}
catch( CmsException $e ) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('listtemplates.php'.$urlext);
}
