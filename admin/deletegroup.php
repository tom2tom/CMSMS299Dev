<?php
/*
Procedure to delete an admin users-group
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Error403Exception;
use CMSMS\Events;
use CMSMS\SingleItem;
use function CMSMS\log_info;

if (!isset($_GET['group_id'])) {
    return;
}

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();

if (!check_permission($userid, 'Manage Groups')) {
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$themeObject = SingleItem::Theme();
$urlext = get_secure_param();

$group_id = (int) $_GET['group_id'];
if ($group_id == 1) {
    // can't delete superadmins group
    $themeObject->ParkNotice('error', _la('error_deletespecialgroup'));
    redirect('listgroups.php'.$urlext);
}

$userops = SingleItem::UserOperations();
if ($userops->UserInGroup($userid,$group_id)) {
    // can't delete a group to which the current user belongs
    $themeObject->ParkNotice('error', _la('cantremove')); //TODO
    redirect('listgroups.php'.$urlext);
}

$groupops = SingleItem::GroupOperations();
$groupobj = $groupops->LoadGroupByID($group_id);

if ($groupobj) {
    $group_name = $groupobj->name;

    // now do the work
    Events::SendEvent('Core', 'DeleteGroupPre', [ 'group'=>&$groupobj ] );

    if ($groupobj->Delete()) {
        Events::SendEvent('Core', 'DeleteGroupPost', [ 'group'=>&$groupobj ] );
        // put mention into the admin log
        log_info($group_id, 'Admin Users Group '.$group_name, 'Deleted');
    } else {
        $themeObject->ParkNotice('error', _la('failure'));
    }
} else {
    $themeObject->ParkNotice('error', _la('invalid'));
}

redirect('listgroups.php'.$urlext);
