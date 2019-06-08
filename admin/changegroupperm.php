<?php
#Procedure to change permissions of users in a group
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
use CMSMS\GroupOperations;
use CMSMS\HookManager;
use CMSMS\LangOperations;
use CMSMS\UserOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listgroups.php'.$urlext); //TODO go to relevant menu section
    return;
}

$userid = get_userid();
$access = check_permission($userid, 'Manage Groups');

$themeObject = cms_utils::get_theme_object();

if (!$access) {
//TODO some immediate popup  lang('needpermissionto', '"Manage Groups"'));
    return;
}

$userops = UserOperations::get_instance();
$adminuser = ($userops->UserInGroup($userid, 1) || $userid == 1);
$group_name = '';
$message = '';

$gCms = CmsApp::get_instance();
$db = $gCms->GetDb();
$smarty = $gCms->GetSmarty();

$load_perms = function () use ($db) {
    $query = 'SELECT p.permission_id, p.permission_source, p.permission_text, up.group_id FROM '.
    CMS_DB_PREFIX.'permissions p LEFT JOIN '.CMS_DB_PREFIX.
    'group_perms up ON p.permission_id = up.permission_id ORDER BY p.permission_text';

    $result = $db->Execute($query);

    // setup to get default values for localized permission-strings from admin realm.
    HookManager::add_hook('localizeperm', function ($perm_source, $perm_name) {
        $key = 'perm_'.str_replace(' ', '_', $perm_name);
        if (LangOperations::lang_key_exists('admin', $key)) {
            return LangOperations::lang_from_realm('admin', $key);
        }
        return $perm_name;
    }, HookManager::PRIORITY_LOW);

    HookManager::add_hook('getperminfo', function ($perm_source, $perm_name) {
        $key = 'permdesc_'.str_replace(' ', '_', $perm_name);
        if (LangOperations::lang_key_exists('admin', $key)) {
            return LangOperations::lang_from_realm('admin', $key);
        }
    }, HookManager::PRIORITY_LOW);

    $perm_struct = [];
    while ($result && $row = $result->FetchRow()) {
        if (isset($perm_struct[$row['permission_id']])) {
            $str = &$perm_struct[$row['permission_id']];
            $str->group[$row['group_id']]=1;
        } else {
            $thisPerm = new stdClass();
            $thisPerm->group = [];
            if (!empty($row['group_id'])) {
                $thisPerm->group[$row['group_id']] = 1;
            }
            $thisPerm->id = $row['permission_id'];
            $thisPerm->name = $thisPerm->label = $row['permission_text'];
            $thisPerm->source = $row['permission_source'];
            $thisPerm->label = HookManager::do_hook_first_result('localizeperm', $thisPerm->source, $thisPerm->name);
            $thisPerm->description = HookManager::do_hook_first_result('getperminfo', $thisPerm->source, $thisPerm->name);
            $perm_struct[$row['permission_id']] = $thisPerm;
        }
    }
    return $perm_struct;
};

$group_perms = function ($in_struct) {
    usort($in_struct, function ($a, $b) {
        // sort by name
        return strcasecmp($a->name, $b->name);
    });

    $out = [];
    foreach ($in_struct as $one) {
        $source = $one->source;
        if (!isset($out[$source])) {
            $out[$source] = [];
        }
        $out[$source][] = $one;
    }

    uksort($out, function ($a, $b) {
        $a = strtolower($a);
        $b = strtolower($b);
        if ($a == 'core') {
            return -1;
        }
        if ($b == 'core') {
            return 1;
        }
        if (empty($a)) {
            return -1;
        }
        if (empty($b)) {
            return 1;
        }
        return strcmp($a, $b);
    });
    return $out;
};

if (isset($_POST['filter'])) {
    $disp_group = filter_var($_POST['groupsel'], FILTER_SANITIZE_NUMBER_INT);
    cms_userprefs::set_for_user($userid, 'changegroupassign_group', $disp_group);
}
$disp_group = cms_userprefs::get_for_user($userid, 'changegroupassign_group', -1);

// always display the group pull down
$groupops = GroupOperations::get_instance();
$tmp = new stdClass();
$tmp->name = lang('all_groups');
$tmp->id=-1;
$allgroups = [$tmp];
$sel_groups = [$tmp];
$group_list = $groupops->LoadGroups();
$sel_group_ids = [];
foreach ($group_list as $onegroup) {
    if ($onegroup->id == 1 && !$adminuser) {
        continue;
    }
    $allgroups[] = $onegroup;
    if ($disp_group == -1 || $disp_group == $onegroup->id) {
        $sel_groups[] = $onegroup;
        $sel_group_ids[] = $onegroup->id;
    }
}

$smarty->assign('group_list', $sel_groups);
$smarty->assign('allgroups', $allgroups);

if (isset($_POST['submit'])) {
    // we have group permissions
    $parts = explode('::', $_POST['sel_groups']);
    if (count($parts) == 2) {
        if (cms_utils::hash_string(__FILE__.$parts[1]) == $parts[0]) {
            $selected_groups = (array) unserialize(base64_decode($parts[1]), ['allowed_classes'=>false]);
            if ($selected_groups) {
                // clean this array
                $tmp = [];
                foreach ($selected_groups as &$one) {
                    $one = (int)$one;
                    if ($one > 0) {
                        $tmp[] = $one;
                    }
                }
                $query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id IN ('.implode(',', $tmp).')';
                $db->Execute($query);
            }
            unset($selected_groups);
        }
    }
    unset($parts);

    $now = $db->DbTimeStamp(time());
    $stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.
        "group_perms (group_perm_id, group_id, permission_id, create_date, modified_date)
VALUES (?,?,?,$now,$now)");

    cleanArray($_POST);
    foreach ($_POST as $key=>$value) {
        if (strncmp($key, 'pg', 2) == 0) {
            $keyparts = explode('_', $key);
            $keyparts[1] = (int)$keyparts[1];
            if ($keyparts[1] > 0 && $keyparts[2] != '1' && $value == '1') {
                $new_id = $db->GenID(CMS_DB_PREFIX.'group_perms_seq');
                $result = $db->Execute($stmt, [$new_id,$keyparts[2],$keyparts[1]]);
                if (!$result) {
                    echo 'FATAL: '.$db->ErrorMsg().'<br />'.$db->sql;
                    exit;
                }
            }
        }
    }

    $stmt->close();
    // put mention into the admin log
    audit($userid, 'Permission Group ID: '.$userid, 'Changed');
    $message = lang('permissionschanged');
//    AdminUtils::clear_cached_files();
//    global_cache::release('IF ANY');
}

if (!empty($message)) {
    $themeObject->RecordNotice('success', $message);
}
$pagesubtitle = lang('groupperms', $group_name);
$perm_struct = $load_perms();
$perm_struct = $group_perms($perm_struct);
$tmp = base64_encode(serialize($sel_group_ids));
$sig = cms_utils::hash_string(__FILE__.$tmp);
$hidden = '<input type="hidden" name="sel_groups" value="'.$sig.'::'.base64_encode(serialize($sel_group_ids)).'" />';
$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
    'disp_group' => $disp_group,
    'hidden2' => $hidden,
    'pagesubtitle' => $pagesubtitle,
    'perms' => $perm_struct,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
]);

include_once 'header.php';
$smarty->display('changegroupperm.tpl');
include_once 'footer.php';
