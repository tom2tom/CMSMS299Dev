<?php
/*
Procedure to add a users-group
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
use function CMSMS\de_specialize;
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
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

//CMSMS\de_specialize_array($_POST);

if (isset($_POST['addgroup'])) {
    $errors = [];
    $tmp = de_specialize(trim($_POST['group']));
    $group = sanitizeVal($tmp, CMSSAN_NAME);
    if ($group !== $tmp) {
        $errors[] = _la('illegalcharacters', _la('groupname'));
    } elseif (!$group) {
        $errors[] = _la('nofieldgiven', _la('groupname'));
    } elseif (!AdminUtils::is_valid_itemname($group)) {
        $errors[] = _la('errorbadname');
    }
    // not compulsory
//    $tmp =
    $tmp = de_specialize(trim($_POST['description']));
	$description = sanitizeVal($tmp, CMSSAN_NONPRINT); // AND nl2br() ? striptags() ?
    $active = !empty($_POST['active']);

    if (!$errors) {
        try {
            $groupobj = new Group();
            $groupobj->name = $group;
            $groupobj->description = $description;
            $groupobj->active = $active;
            Events::SendEvent('Core', 'AddGroupPre', [ 'group'=>&$groupobj ]);

            if ($groupobj->save()) {
                Events::SendEvent('Core', 'AddGroupPost', [ 'group'=>&$groupobj ]);
                // put mention into the admin log
                audit($groupobj->id, 'Admin Users Group '.$groupobj->name, 'Added');
                redirect('listgroups.php'.$urlext);
            } else {
                $errors[] = _la('errorinsertinggroup');
            }
        } catch (Throwable $t) {
            $errors[] = $t->GetMessage();
        }
    }

    SingleItem::Theme()->RecordNotice('error', $errors);

    $group = specialize($group);
    $description = specialize($description);
} else {
    $group = '';
    $description = '';
    $active = 1;
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = SingleItem::Smarty();
$smarty->assign([
//    'access' => $access,
    'active' => $active,
    'description' => $description,
    'group' => $group,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
]);

$content = $smarty->fetch('addgroup.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
