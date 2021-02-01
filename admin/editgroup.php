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

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Error403Exception;
use CMSMS\Events;
use CMSMS\Group;
use CMSMS\UserOperations;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listgroups.php'.$urlext);
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

$themeObject = Utils::get_theme_object();

if (!check_permission($userid, 'Manage Groups')) {
    throw new Error403Exception(lang('permissiondenied')); // OR display error.tpl ?
}

$groupobj = new Group();
if( $group_id > 0 ) {
    $groupobj = Group::load($group_id);
}

if (isset($_POST['editgroup'])) {
    $errors = [];
    cms_specialchars_decode_array($_POST);
    $group_id = (int)$_POST['group_id'];

    $tmp = trim($_POST['group']);
    $group = sanitizeVal($tmp, 1); // OR 21 ?
    if ($group !== $tmp) {
        $errors[] = lang('illegalcharacters', lang('groupname'));
    } elseif (!$group) {
        $errors[] = lang('nofieldgiven', lang('groupname'));
    }
    // not compulsory
    $description = trim($_POST['description']); // AND sanitizeVal(, 0) ? nl2br() ? striptags() ?

    if ($group_id != 1) {
        $active = (!empty($_POST['active'])) ? 1 : 0;
    } else {
        $active = 1;
    }

    if (!$errors) {
        $groupobj->name = $group;
        $groupobj->description = $description;
        $groupobj->active = $active;
        Events::SendEvent( 'Core', 'EditGroupPre', [ 'group'=>&$groupobj ] );
        if ($groupobj->save()) {
            Events::SendEvent( 'Core', 'EditGroupPost', [ 'group'=>&$groupobj ] );
            // put mention into the admin log
            audit($groupobj->id, 'Admin User Group: '.$groupobj->name, 'Edited');
            redirect('listgroups.php'.$urlext);
        } else {
            $errors[] = lang('errorupdatinggroup');
        }
    }
    $themeObject->RecordNotice('error', $errors);

    $group = cms_specialchars($group);
    if ($description) $description = cms_specialchars($description);
} elseif ($group_id != -1) {
    $group = cms_specialchars($groupobj->name);
    $description = cms_specialchars($groupobj->description);
    $active = $groupobj->active;
} else { // id == -1 should never happen when editing ?
    $group = '';
    $description = '';
    $active = 1;
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();
$userops = UserOperations::get_instance();
$useringroup = $userops->UserInGroup($userid, $group_id);

$smarty = AppSingle::Smarty();
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
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
