<?php
/*
Change permission(s) of a users-group
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

use CMSMS\AppParams;
use CMSMS\Error403Exception;
use CMSMS\HookOperations;
use CMSMS\LangOperations;
use CMSMS\SingleItem;
use CMSMS\UserParams;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('menu.php'.$urlext.'&section=usersgroups'); //OR 'listgroups.php'.$urlext); ?
}

$userid = get_userid();

if (!check_permission($userid, 'Manage Groups')) {
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$superusr = $userid == 1;
if ($superusr) {
    $supergrp = true;
} else {
    $userops = SingleItem::UserOperations();
    $supergrp = $userops->UserInGroup($userid, 1);
}

//CMSMS\de_specialize_array($_POST); individually sanitized where needed
if (isset($_POST['filter'])) {
    $disp_group = filter_input(INPUT_POST, 'groupsel', FILTER_SANITIZE_NUMBER_INT); // OR just (int)
    UserParams::set_for_user($userid, 'changegroupassign_group', $disp_group);
}
$disp_group = UserParams::get_for_user($userid, 'changegroupassign_group', -1);

$db = SingleItem::Db();

$ultras = json_decode(AppParams::get('ultraroles'));
if ($ultras) {
    $query = 'SELECT id FROM '.CMS_DB_PREFIX.'permissions WHERE name IN (\''.implode("','", $ultras).'\')';
    $specials = $db->getCol($query); //data for perm's not automatically in group 1
} else {
    $specials = [];
}

if (isset($_POST['submit'])) {
    $stmt1 = $db->prepare('DELETE FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id=? AND permission_id=?');
    $stmt2 = $db->prepare('SELECT 1 FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id=? AND permission_id=?');
    //setting create_date should be redundant with DT setting
    $stmt3 = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.'group_perms (group_id, permission_id, create_date) VALUES (?,?,NOW())');

    foreach ($_POST as $key => $value) {
        if (strncmp($key, 'pg_', 3) == 0) {
            $keyparts = explode('_', $key);
            if ($keyparts[1] > 0) { //valid permission-id
                $i1 = (int)$keyparts[1];
                $i2 = (int)$keyparts[2]; //group id
                if ($i2 != 1 || in_array($i1, $specials)) { // not group 1 || is ultrarole
                    $value = (int)$value; //sanitize
                    if ($value === 0) {
                        $db->execute($stmt1,[$i2,$i1]); //may fail if already absent
                    } elseif ($value === 1) {
                        $rst = $db->execute($stmt2,[$i2,$i1]);
                        if (!$rst || $rst->EOF) {
                            $db->execute($stmt3,[$i2,$i1]);
                        }
                        if ($rst) $rst->close();
                    }
                }
            }
        }
    }

    $stmt1->close();
    $stmt2->close();
    $stmt3->close();
    // put mention into the admin log
    audit($userid, 'Permission Group ID: '.$userid, 'Changed');
    $message = _la('permissionschanged');
//    AdminUtils::clear_cached_files();
//    SingleItem::LoadedData()->refresh('IF ANY');
}

if (!empty($message)) {
    SingleItem::Theme()->RecordNotice('success', $message);
}

// setup to get default values for localized permission-strings from admin realm
HookOperations::add_hook('localizeperm', function ($perm_source, $perm_name) {
    $key = 'perm_'.str_replace(' ', '_', $perm_name);
    if (LangOperations::lang_key_exists(LangOperations::CMSMS_ADMIN_REALM, $key)) {
        return LangOperations::admin_string($key);
    }
    return $perm_name;
}, HookOperations::PRIORITY_LOW);

HookOperations::add_hook('getperminfo', function ($perm_source, $perm_name) {
    $key = 'permdesc_'.str_replace(' ', '_', $perm_name);
    if (LangOperations::lang_key_exists(LangOperations::CMSMS_ADMIN_REALM, $key)) {
        return LangOperations::admin_string($key);
    }
}, HookOperations::PRIORITY_LOW);

//populate displayed-group(s) selector
$groupops = SingleItem::GroupOperations();
$group_list = $groupops->LoadGroups();
$allgroups = []; //ditto
$sel_groups = [];
foreach ($group_list as $onegroup) {
    if ($onegroup->id === 1 && !$supergrp) {
        continue; //skip (i.e. prevent display/change of) grp 1 permissions
    }
    $allgroups[] = $onegroup;
    if ($disp_group === -1 || $disp_group == $onegroup->id) {
        $sel_groups[] = $onegroup;
    }
}

$perm_struct = [];

$pref = CMS_DB_PREFIX;
$query = <<<EOS
SELECT P.id, P.name, P.description, P.originator, GP.group_id
FROM {$pref}permissions P LEFT JOIN {$pref}group_perms GP
ON P.id = GP.permission_id
ORDER BY P.description
EOS;

$rst = $db->execute($query);
if ($rst) {
    while (($row = $rst->FetchRow())) {
        if (isset($perm_struct[$row['id']])) {
            $str = &$perm_struct[$row['id']];
            $str->group[$row['group_id']] = 1;
        } else {
            $thisPerm = new stdClass();
            $thisPerm->group = [];
            if (!empty($row['group_id'])) {
                $thisPerm->group[$row['group_id']] = 1;
            }
            $thisPerm->id = $row['id'];
            $thisPerm->name = $row['name'];
            $thisPerm->label = $row['description'];
            $thisPerm->source = $row['originator'];
            $thisPerm->label = HookOperations::do_hook_first_result('localizeperm', $thisPerm->source, $thisPerm->name);
            $thisPerm->description = HookOperations::do_hook_first_result('getperminfo', $thisPerm->source, $thisPerm->name);
            $perm_struct[$row['id']] = $thisPerm;
        }
    }
    $rst->Close();

    // sort by description TODO UTF8 sort
    usort($perm_struct, function ($a, $b) {
        return strcasecmp($a->name, $b->name);
    });
}

$out = [];
foreach ($perm_struct as $one) {
    $source = $one->source;
    if ($source == '__CORE__') { $source = 'Core'; } // public-name TODO _la() for this?
    if (!isset($out[$source])) {
        $out[$source] = [];
    }
    $out[$source][] = $one;
}
$perm_struct = $out;

// sort by originator (assumed ASCII)
uksort($perm_struct, function ($a, $b) {
    if (!$a || strcasecmp($a, 'Core') == 0) {
        return -1;
    }
    if (!$b || strcasecmp($b, 'Core') == 0) {
        return 1;
    }
    return strcasecmp($a, $b);
/*  if (($n = strcasecmp($a, $b)) != 0) {
        return $n;
    }
    return strcasecmp($a->name, $b->name); TODO
*/
});
if (count($perm_struct) > 1) {
    $tmp = new stdClass();
    $tmp->id = -1;
    $tmp->name = _la('all_groups');
    array_unshift($allgroups, $tmp);
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = SingleItem::Smarty();
$smarty->assign([
    'group_list' => $sel_groups,
    'allgroups' => $allgroups,
    'disp_group' => $disp_group,
    'perms' => $perm_struct,
    'ultras' => $specials,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'usr1perm' => $superusr,
    'grp1perm' => $supergrp,
    'pmod' => !$supergrp, //i.e. current user may 'Manage Groups' but not in Group 1
]);

$content = $smarty->fetch('changegroupperm.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
