<?php
# delete template
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

cleanArray($_REQUEST);

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$userid = get_userid();
$themeObject = cms_utils::get_theme_object();

if( !isset($_REQUEST['tpl']) ) {
	$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
	redirect('listtemplates.php'.$urlext);
}
if( isset($_REQUEST['cancel']) ) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('listtemplates.php'.$urlext);
}

try {
	$tpl_ob = TemplateOperations::get_template($_REQUEST['tpl']);
	if( $tpl_ob->get_owner_id() != get_userid() && !check_permission($userid,'Modify Templates') ) {
		throw new CmsException(lang_by_realm('layout','error_permission'));
	}

	if( isset($_REQUEST['submit']) ) {
		if( !isset($_REQUEST['check1']) || !isset($_REQUEST['check2']) ) {
			$themeObject->RecordNotice('error',lang_by_realm('layout','error_notconfirmed'));
		}
		else {
			$tpl_ob->delete();
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_template_deleted'));
			redirect('listtemplates.php'.$urlext);
		}
	}

	$smarty = CmsApp::get_instance()->GetSmarty();

	// find the number of 'pages' that use this template.
	$db = cmsms()->GetDb();
	$query = 'SELECT COUNT(*) FROM '.CMS_DB_PREFIX.'content WHERE template_id = ?';
	$n = $db->GetOne($query,[$tpl_ob->get_id()]);
	$smarty->assign('page_usage',$n);

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
/*
	$designs = DesignManager\Design::get_all(); DISABLED
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

	$selfurl = basename(__FILE__);

	$smarty->assign('tpl',$tpl_ob)
	 ->assign('selfurl',$selfurl)
	 ->assign('urlext',$urlext);

	include_once 'header.php';
	$smarty->display('deletetemplate.tpl');
	include_once 'footer.php';
}
catch( CmsException $e ) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('listtemplates.php'.$urlext);
}
