<?php
/*
Procedure to modify a users-group
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

use CMSMS\AdminUtils;
use CMSMS\Error403Exception;
use CMSMS\Events;
use CMSMS\Group;
use CMSMS\SingleItem;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listgroups.php'.$urlext);
}

$userid = get_userid();
if (!check_permission($userid, 'Manage Groups')) {
    throw new Error403Exception(lang('permissiondenied')); // OR display error.tpl ?
}

$group = '';
$description = '';
$active = 1;
if (isset($_GET['group_id'])) {
    $group_id = (int)$_GET['group_id'];
} else {
    $group_id = -1;
}
if ($group_id > 0) {
    $groupobj = Group::load($group_id);
} else {
    $groupobj = new Group();
}

if (isset($_POST['editgroup'])) {
    $errors = [];
    de_specialize_array($_POST);

    $group_id = (int)$_POST['group_id'];
    if ($group_id != 1) {
        $active = (!empty($_POST['active'])) ? 1 : 0;
    } else {
        $active = 1;
    }

    $tmp = trim($_POST['group']);
    $group = sanitizeVal($tmp, CMSSAN_NAME);
    if ($group !== $tmp) {
        $errors[] = lang('illegalcharacters', lang('groupname'));
    } elseif (!$group) {
        $errors[] = lang('nofieldgiven', lang('groupname'));
    } elseif (!AdminUtils::is_valid_itemname($group)) {
        $errors[] = lang('errorbadname');
    }

    // not compulsory
    $tmp = trim($_POST['description']);
    $description = sanitizeVal($tmp, CMSSAN_NONPRINT); // AND nl2br() ? striptags() ?

    if (!$errors) {
        $groupobj->name = $group;
        $groupobj->description = $description;
        $groupobj->active = $active;
        Events::SendEvent( 'Core', 'EditGroupPre', [ 'group'=>&$groupobj ] );
        if ($groupobj->save()) {
            Events::SendEvent( 'Core', 'EditGroupPost', [ 'group'=>&$groupobj ] );
            // put mention into the admin log
            audit($groupobj->id, 'Admin Users Group '.$groupobj->name, 'Edited');
            redirect('listgroups.php'.$urlext);
        } else {
            $errors[] = lang('errorupdatinggroup');
        }
    }
    SingleItem::Theme()->RecordNotice('error', $errors);

    $group = specialize($group);
    if ($description) { $description = specialize($description); }
} elseif ($group_id != -1) {
    $group = specialize($groupobj->name);
    $description = specialize($groupobj->description);
    $active = $groupobj->active;
} else { // id == -1 should never happen when editing ?
    $group = '';
    $description = '';
    $active = 1;
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
$userops = SingleItem::UserOperations();
$useringroup = $userops->UserInGroup($userid, $group_id);

$smarty = SingleItem::Smarty();
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

$content = $smarty->fetch('editgroup.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
