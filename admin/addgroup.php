<?php
#procedure to add a users-group
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

check_login();

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
if (isset($_POST['cancel'])) {
	redirect('listgroups.php'.$urlext);
//	return;
}

$group= '';
$description= '';
$active = 1;

$userid = get_userid();
$access = check_permission($userid, 'Manage Groups');

$themeObject = cms_utils::get_theme_object();

if (!$access) {
//TODO some immediate popup	lang('needpermissionto', '"Manage Groups"'));
	return;
}

if (!empty($_POST['addgroup'])) {
	$group = cleanValue($_POST['group']);
	$description = cleanValue($_POST['description']);
	$active = !empty($_POST['active']);
	try {
		if ($group == '') {
			 throw new \CmsInvalidDataException(lang('nofieldgiven', lang('groupname')));
		}

		$groupobj = new Group();
		$groupobj->name = $group;
		$groupobj->description = $description;
		$groupobj->active = $active;
		\CMSMS\HookManager::do_hook('Core::AddGroupPre', [ 'group'=>&$groupobj ] );

		if($groupobj->save()) {
			\CMSMS\HookManager::do_hook('Core::AddGroupPost', [ 'group'=>&$groupobj ] );
			// put mention into the admin log
			audit($groupobj->id, 'Admin User Group: '.$groupobj->name, 'Added');
			redirect('listgroups.php'.$urlext);
			return;
		} else {
			throw new \RuntimeException(lang('errorinsertinggroup'));
		}
	} catch( \Exception $e ) {
		$themeObject->RecordMessage('error', $e->GetMessage());
	}
}

$selfurl = basename(__FILE__);

$smarty = CMSMS\internal\Smarty::get_instance();
$smarty->assign([
	'access' => $access,
	'active' => $active,
	'description' => $description,
	'group' => $group,
	'selfurl' => $selfurl,
	'urlext' => $urlext,
]);

include_once 'header.php';
$smarty->display('addgroup.tpl');
include_once 'footer.php';

