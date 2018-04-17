<?php
#procedure to delete an admin group
#Copyright (C) 2004-2016 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2017-2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib/include.php';

check_login();

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if (isset($_GET["group_id"])) {

    $userid = get_userid();
	if( !check_permission($userid, 'Manage Groups') ) {
        cms_utils::get_theme_object()->ParkString('error', lang('needpermissionto', 'Manage Groups'));
	    redirect("listgroups.php".$urlext);
	}

	$group_id = (int) $_GET["group_id"];
	if( $group_id == 1 ) {
	    // can't delete this group
        cms_utils::get_theme_object()->ParkString('error', lang('invalid'));
	    redirect("listgroups.php".$urlext);
    }

	$result = false;

	$gCms = cmsms();
	$groupops = $gCms->GetGroupOperations();
	$userops = $gCms->GetUserOperations();
	$groupobj = $groupops->LoadGroupByID($group_id);
	$group_name = $groupobj->name;

	if( $userops->UserInGroup($userid,$group_id) ) {
        // check to make sure we're not a member of this group
        // can't delete a group we're a member of.
        redirect("listgroups.php".$urlext);
    }

	// now do the work.
    \CMSMS\HookManager::do_hook('Core::DeleteGroupPre', [ 'group'=>&$groupobj ] );

	if ($groupobj) $result = $groupobj->Delete();

    \CMSMS\HookManager::do_hook('Core::DeleteGroupPost', [ 'group'=>&$groupobj ] );

	if ($result == true) {
        // put mention into the admin log
        audit($group_id, 'Admin User Group: '.$group_name, 'Deleted');
    }
}

redirect("listgroups.php".$urlext);
