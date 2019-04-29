<?php
# Copy stylesheet
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
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
$themeObject = cms_utils::get_theme_object();
if( isset($_REQUEST['cancel']) ) {
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_cancelled'));
	redirect('liststyles.php'.$urlext);
}
if( !isset($_REQUEST['css']) ) {
	$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
	redirect('liststyles.php'.$urlext);
}

cleanArray($_REQUEST);

try {
	$orig_css = StylesheetOperations::get_stylesheet($_REQUEST['css']);
	if( isset($_REQUEST['submit']) || isset($_REQUEST['apply']) ) {
		try {
			$new_css = clone($orig_css);
			$new_css->set_name(trim($_REQUEST['new_name']));
			$new_css->set_designs([]);
			$new_css->save();

			if( isset($_REQUEST['apply']) ) {
				$themeObject->ParkNotice('info',lang_by_realm('layout','msg_stylesheet_copied_edit'));
				redirect('editstylesheet.php'.$urlext.'&css='.$new_css->get_id());
			}
			else {
				$themeObject->ParkNotice('info',lang_by_realm('layout','msg_stylesheet_copied'));
				redirect('liststyles.php'.$urlext);
			}
		}
		catch( Exception $e ) {
			$themeObject->RecordNotice('error',$e->GetMessage());
		}
	}

	$selfurl = basename(__FILE__);

	// build a display
	$smarty = CmsApp::get_instance()->GetSmarty();
	$smarty->assign('css',$orig_css)
	 ->assign('selfurl',$selfurl)
	 ->assign('urlext',$urlext);

	include_once 'header.php';
	$smarty->display('copystylesheet.tpl');
	include_once 'footer.php';
}
catch( CmsException $e ) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('liststyles.php'.$urlext);
}
