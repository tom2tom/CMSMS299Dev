<?php
# Delete stylesheet
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

use CMSMS\StylesheetOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();

$userid = get_userid();
if( !check_permission($userid,'Manage Stylesheets') ) {
	return;
}
$themeObject = cms_utils::get_theme_object();
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if( isset($_REQUEST['cancel']) ) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('liststyles.php'.$urlext);
}

cleanArray($_REQUEST);

try {
	if( !isset($_REQUEST['css']) ) throw new CmsException(lang_by_realm('layout','error_missingparam'));

	$css_ob = StylesheetOperations::get_stylesheet($_REQUEST['css']);

	if( isset($_REQUEST['submit']) ) {
		if( !isset($_REQUEST['check1']) || !isset($_REQUEST['check2']) ) {
			$themeObject->RecordNotice('error',lang_by_realm('layout','error_notconfirmed'));
		}
		else {
			$css_ob->delete();
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_stylesheet_deleted'));
			redirect('liststyles.php'.$urlext);
		}
	}

	$selfurl = basename(__FILE__);

	$smarty = CmsApp::get_instance()->GetSmarty();
	$smarty->assign('css',$css_ob)
	 ->assign('selfurl',$selfurl)
	 ->assign('urlext',$urlext);

	include_once 'header.php';
	$smarty->display('deletestylesheet.tpl');
	include_once 'footer.php';
}
catch( CmsException $e ) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('liststyles.php'.$urlext);
}
