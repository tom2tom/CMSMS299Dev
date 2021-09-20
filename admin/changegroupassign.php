<?php
/*
Change user-group-membership(s) of user(s)
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Error403Exception;
use CMSMS\Events;
use CMSMS\SingleItem;
use CMSMS\UserParams;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listusers.php'.$urlext);
}

$userid = get_userid();

$themeObject = SingleItem::Theme();

if (!check_permission($userid, 'Manage Groups')) {
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$groupops = SingleItem::GroupOperations();
$superusr = $userid == 1; //group 1 addition|removal allowed
if ($superusr) {
    $supergrp = true; //group 1 removal allowed
} else {
    $admins = array_column($groupops->GetGroupMembers(1), 1);
    $supergrp = in_array($userid, $admins);
}

$smarty = SingleItem::Smarty();
$smarty->assign([
    'user_id' => $userid, // current user
    'usr1perm' => $superusr,
    'grp1perm' => $supergrp,
    'pmod' => !$supergrp, // current user may 'Manage Groups' but not in Group 1
]);

$group_list = $groupops->LoadGroups();
$groups = []; // displayable Group-object(s)

if (isset($_POST['filter'])) {
    $disp_group = (int)$_POST['groupsel'];
    UserParams::set_for_user($userid, 'changegroupassign_group', $disp_group);
} else {
    $disp_group = UserParams::get_for_user($userid, 'changegroupassign_group', -1);
}

foreach ($group_list as $onegroup) {
    if ($disp_group == -1 || $disp_group == $onegroup->id) {
        $groups[] = $onegroup;
    }
}

if (count($group_list) > 1) {
    $tmp = new stdClass();
    $tmp->id = -1;
    $tmp->name = _la('all_groups');
    array_unshift($group_list, $tmp);
}

$smarty->assign([
    'group_list' => $group_list,
    'displaygroups' => $groups,
    'disp_group'=>$disp_group,
]);

$db = SingleItem::Db();

if (isset($_POST['submit'])) {
    $userops = SingleItem::UserOperations();

    $stmt1 = $db->prepare('DELETE FROM '.CMS_DB_PREFIX.'user_groups WHERE group_id=? AND user_id=?');
    $stmt2 = $db->prepare('SELECT 1 FROM '.CMS_DB_PREFIX.'user_groups WHERE group_id=? AND user_id=?');
    //setting create_date should be redundant with DT field properties
    $stmt3 = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'user_groups
(group_id, user_id, create_date)
VALUES (?,?,NOW())');

	//CMSMS\de_specialize_array($_POST); individually sanitized where needed
    foreach ($groups as $onegroup) {
        if ($onegroup->id <= 0) {
            continue; // invalid | 'all groups' ?
        }

        foreach ($_POST as $key => $value) {
            if (strncmp($key, 'ug_', 3) == 0) {
                $keyparts = explode('_', $key);
                if ($keyparts[2] == $onegroup->id) {  //group id
                    // Send the ChangeGroupAssignPre event
                    Events::SendEvent('Core', 'ChangeGroupAssignPre',
                         ['group' => $onegroup, 'users' => $userops->LoadUsersInGroup($onegroup->id)]
                    );
                    $value = (int)$value; //sanitize
                    if ($value === 0) {
                        $db->execute($stmt1, [$onegroup->id,$userid]);
                    } elseif ($value === 1) {
                        $rst = $db->execute($stmt2, [$onegroup->id,$keyparts[1]]); // permission id
                        if (!$rst || $rst->EOF) {
                            $db->execute($stmt3, [$onegroup->id,$keyparts[1]]);
                            Events::SendEvent('Core', 'ChangeGroupAssignPost',
                                ['group' => $onegroup, 'users' => $userops->LoadUsersInGroup($onegroup->id)]
                            );
                        }
                        if ($rst) $rst->Close();
                    }
                    // put mention into the admin log
                    $group_id = (isset($_GET['group_id'])) ? (int)$_GET['group_id'] : -1;
                    audit($group_id, 'Assignment Group ID: '.$group_id, 'Changed');
                }
            }
        }
    }

    $stmt1->close();
    $stmt2->close();
    $stmt3->close();

    $message = _la('assignmentchanged');
//    AdminUtils::clear_cached_files();
//    SingleItem::LoadedData()->refresh('IF ANY');
}

$query = 'SELECT u.user_id, u.username, ug.group_id FROM '.
    CMS_DB_PREFIX.'users u LEFT JOIN '.CMS_DB_PREFIX.
    'user_groups ug ON u.user_id = ug.user_id ORDER BY u.username';
$rst = $db->execute($query);

$user_struct = [];
while ($rst && ($row = $rst->FetchRow())) {
    if (isset($user_struct[$row['user_id']])) {
        $str = &$user_struct[$row['user_id']];
        $str->group[$row['group_id']] = 1;
    } else {
        $thisUser = new stdClass();
        $thisUser->group = [];
        if (!empty($row['group_id'])) {
            $thisUser->group[$row['group_id']] = 1;
        }
        $thisUser->id = $row['user_id'];
        $thisUser->name = $row['username'];
        $user_struct[$row['user_id']] = $thisUser;
    }
}
if ($rst) $rst->Close();

if (!empty($message)) {
    $themeObject->RecordNotice('success', $message);
}

//$icontrue = $themeObject->DisplayImage('icons/system/true.gif', '', '', '', 'systemicon');
$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
    'selfurl'=>$selfurl,
    'extraparms'=>$extras,
    'urlext'=>$urlext,
//    'icontrue' => $icontrue,
    'users' => $user_struct,
]);

$content = $smarty->fetch('changegroupassign.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
