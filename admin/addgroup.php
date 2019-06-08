<?php
#procedure to add a users-group
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

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listgroups.php'.$urlext);
//  return;
}

$group= '';
$description= '';
$active = 1;

$userid = get_userid();
$access = check_permission($userid, 'Manage Groups');

$themeObject = cms_utils::get_theme_object();

if (!$access) {
//TODO some immediate popup lang('needpermissionto', '"Manage Groups"'));
    return;
}

if (isset($_POST['addgroup'])) {
    $group = cleanValue($_POST['group']);
    $description = cleanValue($_POST['description']);
    $active = isset($_POST['active']);
    try {
        if ($group == '') {
             throw new CmsInvalidDataException(lang('nofieldgiven', lang('groupname')));
        }

        $groupobj = new Group();
        $groupobj->name = $group;
        $groupobj->description = $description;
        $groupobj->active = $active;
        Events::SendEvent( 'Core', 'AddGroupPre', [ 'group'=>&$groupobj ] );

        if($groupobj->save()) {
            Events::SendEvent( 'Core', 'AddGroupPost', [ 'group'=>&$groupobj ] );
            // put mention into the admin log
            audit($groupobj->id, 'Admin User Group: '.$groupobj->name, 'Added');
            redirect('listgroups.php'.$urlext);
            return;
        } else {
            throw new RuntimeException(lang('errorinsertinggroup'));
        }
    } catch( Exception $e ) {
        $themeObject->RecordNotice('error', $e->GetMessage());
    }
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
    'access' => $access,
    'active' => $active,
    'description' => $description,
    'group' => $group,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
]);

include_once 'header.php';
$smarty->display('addgroup.tpl');
include_once 'footer.php';
