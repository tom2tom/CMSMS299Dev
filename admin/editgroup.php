<?php
#procedure to modify a users-group
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

use CMSMS\AppState;
use CMSMS\Events;
use CMSMS\Group;
use CMSMS\UserOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listgroups.php'.$urlext);
    return;
}

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

$themeObject = cms_utils::get_theme_object();

if (!$access) {
//TODO some immediate popup lang('needpermissionto', '"Manage Groups"')
    return;
}

$groupobj = new Group();
if( $group_id > 0 ) {
    $groupobj = Group::load($group_id);
}

if (isset($_POST['editgroup'])) {
    $group_id = (int)$_POST['group_id'];
    $description = trim(cleanValue($_POST['description']));
    if ($group_id != 1) {
        $active = (isset($_POST['active'])) ? 1 : 0;
    }

    $validinfo = true;
    $group = trim(cleanValue($_POST['group']));
    if ($group == '') {
        $validinfo = false;
        $themeObject->RecordNotice('error', lang('nofieldgiven', lang('groupname')));
    }

    if ($validinfo) {
        $groupobj->name = $group;
        $groupobj->description = $description;
        $groupobj->active = $active;
        Events::SendEvent( 'Core', 'EditGroupPre', [ 'group'=>&$groupobj ] );
        if ($groupobj->save()) {
            Events::SendEvent( 'Core', 'EditGroupPost', [ 'group'=>&$groupobj ] );
            // put mention into the admin log
            audit($groupobj->id, 'Admin User Group: '.$groupobj->name, 'Edited');
            redirect('listgroups.php'.$urlext);
            return;
        } else {
            $themeObject->RecordNotice('error', lang('errorupdatinggroup'));
        }
    }
} elseif ($group_id != -1) {
    $group = $groupobj->name;
    $description = $groupobj->description;
    $active = $groupobj->active;
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
$userops = UserOperations::get_instance();
$useringroup = $userops->UserInGroup($userid, $group_id);

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
    'active' => $active,
    'description' => $description,
    'group' => $group,
    'group_id' => $group_id,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'useringroup' => $useringroup,
]);

include_once 'header.php';
$smarty->display('editgroup.tpl');
include_once 'footer.php';
