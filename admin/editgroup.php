<?php
#procedure to modify a users-group
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

if (isset($_POST['cancel'])) {
	redirect('listgroups.php'.$urlext);
	return;
}

$error = '';
$group = '';
$description = '';
$active = 1;
if (isset($_GET['group_id'])) {
	$group_id = (int) $_GET['group_id'];
} else {
	$group_id = -1;
}

$userid = get_userid();
$access = check_permission($userid, 'Manage Groups');

$userops = $gCms->GetUserOperations();
$useringroup = $userops->UserInGroup($userid, $group_id);

if ($access) {
	$groupobj = new Group;
	if( $group_id > 0 ) {
		$groupobj = Group::load($group_id);
	}

	if (isset($_POST['editgroup'])) {
		$group_id = (int)$_POST['group_id'];
		$description = trim(cleanValue($_POST['description']));
		if ($group_id != 1) {
			$active = (int)$_POST['active'];
		}

		$validinfo = true;
		$group = trim(cleanValue($_POST['group']));
		if ($group == '') {
			$validinfo = false;
			$error .= '<li>'.lang('nofieldgiven', lang('groupname')).'</li>';
		}

		if ($validinfo) {
			$groupobj->name = $group;
			$groupobj->description = $description;
			$groupobj->active = $active;
			\CMSMS\HookManager::do_hook('Core::EditGroupPre', [ 'group'=>&$groupobj ] );
			if ($groupobj->save()) {
				\CMSMS\HookManager::do_hook('Core::EditGroupPost', [ 'group'=>&$groupobj ] );
				// put mention into the admin log
				audit($groupobj->id, 'Admin User Group: '.$groupobj->name, 'Edited');
				redirect('listgroups.php'.$urlext);
				return;
			} else {
				$error .= '<li>'.lang('errorupdatinggroup').'</li>';
			}
		}
	} elseif ($group_id != -1) {
		$group = $groupobj->name;
		$description = $groupobj->description;
		$active = $groupobj->active;
	}
}

include_once 'header.php';

$maintitle = $themeObject->ShowHeader('editgroup');
$selfurl = basename(__FILE__);

$smarty->assign([
//	'access' => $access,
	'active' => $active,
	'description' => $description,
	'error' => $error,
	'group' => $group,
	'group_id' => $group_id,
	'maintitle' => $maintitle,
	'urlext' => $urlext,
	'selfurl' => $selfurl,
	'useringroup' => $useringroup,
]);

$smarty->display('editgroup.tpl');

include_once 'footer.php';
