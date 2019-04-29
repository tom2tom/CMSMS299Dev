<?php
# Delete templates group
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

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

$userid = get_userid();
if( !check_permission($userid,'Modify Templates') ) {
	return;
}
$themeObject = cms_utils::get_theme_object();
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if( !isset($_REQUEST['grp']) ) {
	$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
	redirect('listtemplates.php'.$urlext.'&_activetab=groups');
}

cleanArray($_REQUEST);

try {
	$group = CmsLayoutTemplateCategory::load($_REQUEST['grp']);
	$group->delete();
	$themeObject->ParkNotice('info',lang_by_realm('layout','msg_group_deleted'));
	redirect('listtemplates.php'.$urlext.'&_activetab=groups');
}
catch( CmsException $e ) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('listtemplates.php'.$urlext.'&_activetab=groups');
}
