<?php
/*
Procedure to modify an existing admin user's account data
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
use CMSMS\ScriptsMerger;
use CMSMS\UserParams;
use function CMSMS\de_specialize_array;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listusers.php'.$urlext);
}

//--------- Variables ---------

$userid = get_userid();
if (!empty($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id']; // OR filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
} elseif (!empty($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
} else {
    $user_id = $userid;
}
$selfedit = ($userid == $user_id);

if (!($selfedit || check_permission($userid, 'Manage Users'))) {
    //TODO some pushed popup c.f. javascript:cms_notify('error', lang('no_permission') OR lang('needpermissionto', lang('perm_Manage_Users')), ...);
    throw new Error403Exception(lang('permissiondenied')); // OR display error.tpl ?
}

$themeObject = AppSingle::Theme();
//$tplmaster = 0; //CHECKME
$copyusersettings = 0; // NOT 1! default superuser for properties-migration

$superusr = ($userid == 1); //group 1 addition|removal allowed
$groupops = AppSingle::GroupOperations();
$admins = array_column($groupops->GetGroupMembers(1), 1);
$supergrp = $superusr || in_array($userid, $admins); //group 1 removal allowed
$access_group = in_array($userid, $admins) || !in_array($user_id, $admins); // ??
$access = $selfedit && $access_group;
$userops = AppSingle::UserOperations();
$userobj = $userops->LoadUserByID($user_id);
$group_list = $groupops->LoadGroups();
$manage_groups = check_permission($userid, 'Manage Groups');
$manage_users = true; //checked above
$errors = [];

//-------- Logic --------

if (isset($_POST['submit'])) {
    if (!$selfedit) {
        $active = !empty($_POST['active']);
//        $adminaccess = !empty($_POST['adminaccess']); //whether the user may log in
    } elseif ($user_id != -1) {
        $active = $userobj->active;
//        $adminaccess = $userobj->adminaccess;
    } else { //should never happen when editing
        $active = 1;
//        $adminaccess = 1;
    }

    de_specialize_array($_POST);

    $tmp = trim($_POST['user']);
    $username = sanitizeVal($tmp, CMSSAN_ACCOUNT);
    if ($username != $tmp) {
        $errors[] = lang('illegalcharacters', lang('username'));
    } elseif (!($username || is_numeric($username))) { // allow username '0' ??
        $errors[] = lang('nofieldgiven', lang('username'));
    }

    $firstname = sanitizeVal(trim($_POST['firstname']), CMSSAN_NONPRINT); // OR no-gibberish 2.99 breaking change
    $lastname = sanitizeVal(trim($_POST['lastname']), CMSSAN_NONPRINT); // OR no-gibberish 2.99 breaking change

    $tpw = $_POST['password'] ?? '';
    $tpw2 = $_POST['passwordagain'] ?? '';
    if ($tpw) {
        //per https://pages.nist.gov/800-63-3/sp800-63b.html : valid = printable ASCII | space | Unicode
        $password = sanitizeVal($tpw, CMSSAN_NONPRINT);
        if (!$password || $password != $tpw) {
            $errors[] = lang('illegalcharacters', lang('password'));
        } else {
            $again = sanitizeVal($tpw2, CMSSAN_NONPRINT);
            if ($password != $again) {
                $errors[] = lang('nopasswordmatch');
            }
        }
    } else {
        $password = null;
        $passwordagain = null;
    }

    //PHP's FILTER_VALIDATE_EMAIL mechanism is not entirely reliable - see notes at https://www.php.net/manual/en/function.filter-var.php
    //$email = filter_var($tmp, FILTER_SANITIZE_EMAIL);
    $tmp = trim($_POST['email']);
    $email = sanitizeVal($tmp, CMSSAN_NONPRINT);
    if (($email != $tmp) || ($email && !is_email($email))) {
        $errors[] = lang('invalidemail') . ': ' . $email;
        $mailcheck = null;
    } else {
        $mailcheck = $email;
    }

    //record properties involved in creds check
    $userobj->username = $username;
    if ($firstname) { $userobj->firstname = $firstname; }
    if ($lastname) { $userobj->lastname = $lastname; }
    $userobj->email = $mailcheck; // relevant iff valid
    $tmp = $userops->CredentialsCheck($userobj, $username, $password, true);
    if ($tmp) { $errors[] = $tmp; }

    if (isset($_POST['copyusersettings']) && $_POST['copyusersettings'] > 0) {
        if (isset($_POST['clearusersettings'])) {
            // error: both can't be set
            $errors[] = lang('error_multiusersettings');
        }
    }

    if (!$errors) {
        // save data
        $result = false;
        if ($userobj) {
            if ($password && $userobj->SetPassword($password)) {
                $userobj->active = $active;
                Events::SendEvent('Core', 'EditUserPre', ['user' => &$userobj]);

                $result = $userobj->Save();
                if ($result) {
                    if ($manage_groups && isset($_POST['groups'])) {
                        $db = AppSingle::Db();
                        $stmt1 = $db->Prepare('DELETE FROM ' . CMS_DB_PREFIX . 'user_groups WHERE user_id=? AND group_id=?');
                        $stmt2 = $db->Prepare('SELECT 1 FROM ' . CMS_DB_PREFIX . 'user_groups WHERE user_id=? AND group_id=?');
                        $stmt3 = $db->Prepare('INSERT INTO ' . CMS_DB_PREFIX . 'user_groups (user_id,group_id) VALUES (?,?)');
                        foreach ($group_list as $thisGroup) {
                            $gid = $thisGroup->id;
                            if (isset($_POST['g' . $gid])) {
                                $uid = $userobj->id;
                                if ($_POST['g' . $gid] == 0) {
                                    $db->Execute($stmt1, [$uid, $gid]); // fails if already absent
                                } elseif ($_POST['g' . $gid] == 1) {
                                    $rst = $db->Execute($stmt2, [$uid, $gid]);
                                    if (!$rst || $rst->EOF) {
                                        $db->Execute($stmt2, [$uid, $gid]);
                                    }
                                    if ($rst) {
                                        $rst->Close();
                                    }
                                }
                            }
                        }
                        $stmt1->close();
                        $stmt2->close();
                        $stmt3->close();
                    }
                    // put mention into the admin log
                    audit($userid, 'Admin Username: ' . $userobj->username, ' Edited');
                    Events::SendEvent('Core', 'EditUserPost', ['user' => &$userobj]);
                    $themeObject->RecordNotice('success', lang('accountupdated'));
                } else {
                    $errors[] = lang('error_internal');
                }
            } elseif ($password) {
                $errors[] = lang('error_passwordinvalid');
            }
        }

        if ($result) {
            $message = [lang('edited_user')];
            if (isset($_POST['copyusersettings']) && $_POST['copyusersettings'] > 0) {
                // block supperuser replication unless current user is super
                if ($userid == 1 || $_POST['copyusersettings'] > 1) {
                    // copy user preferences from the template user to this user.
                    $prefs = UserParams::get_all_for_user((int)$_POST['copyusersettings']);
                    if ($prefs) {
                        UserParams::remove_for_user($user_id);
                        foreach ($prefs as $k => $v) {
                            UserParams::set_for_user($user_id, $k, $v);
                        }
                        audit($user_id, 'Admin Username: ' . $userobj->username, 'settings copied from template user');
                        $message[] = lang('msg_usersettingscopied');
                    }
                } else {
                    $errors[] = lang('errorupdatinguser'); // TODO better advice
                }
            } elseif (isset($_POST['clearusersettings'])) {
                if ($user_id > 1) {
                    // clear all preferences for this user.
                    audit($user_id, 'Admin Username: ' . $userobj->username, ' settings cleared');
                    UserParams::remove_for_user($user_id);
                    $message[] = lang('msg_usersettingscleared');
                } else {
                    $errors[] = lang('errorupdatinguser'); // TODO better advice
                }
            }

            Events::SendEvent('Core', 'EditUserPost', ['user' => &$userobj]);
//            AdminUtils::clear_cached_files();
//            AppSingle::SysDataCache()->release('IF ANY');
            if ($message) {
                $themeObject->ParkNotice('success', $message);
            }
            redirect('listusers.php?'.$urlext);
        } else {
            $errors[] = lang('errorupdatinguser');
        }
    }
    $email = specialize($userobj->email);
    $firstname = specialize($userobj->firstname);
    $lastname = specialize($userobj->lastname);
//    $password = $password ? specialize($password) : '';
//    $passagain = $passagain ? specialize($passagain) : '';
    $username = specialize($username);
} elseif ($user_id != -1) {
    $active = $userobj->active;
//    $adminaccess   = $userobj->adminaccess;
    $email = specialize($userobj->email);
    $firstname = specialize($userobj->firstname);
    $lastname = specialize($userobj->lastname);
//    $password      = '';
//    $passagain = '';
    $username = specialize($userobj->username);
} else { //should never happen when editing
    $active = 1;
//    $adminaccess   = 1;
    $email = '';
    $firstname = '';
    $lastname = '';
//    $password      = '';
//    $passagain = '';
    $username = '';
}

//------- Display view -------

if ($errors) {
    $themeObject->RecordNotice('error', $errors);
}

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery-inputCloak.js', 1);

//$nonce = get_csp_token();
$confirm = json_encode(lang('confirm_edituser'));
$js = <<<EOS
$(function() {
 $('#password,#passagain').inputCloak({
  type:'see1',
  symbol:'\u25CF'
 });
 $('#submit').on('click', function(ev) {
  ev.preventDefault();
  cms_confirm_btnclick(this, $confirm);
  return false;
 });
 $('#copyusersettings').change(function() {
  var v = $(this).val();
  if(v === -1) {
   $('#clearusersettings').prop('disabled', false);
  } else {
   $('#clearusersettings').prop('disabled', true);
  }
 });
 $('#clearusersettings').on('click', function() {
  $('#copyusersettings').val(-1);
  var v = $(this).prop('checked');
  if(v) {
   $('#copyusersettings').prop('disabled', true);
  } else {
   $('#copyusersettings').prop('disabled', false);
  }
 });
});
EOS;
$jsm->queue_string($js, 3);
$out = $jsm->page_content();
if ($out) {
    add_page_foottext($out);
}

//data for user-selector
$sel = [-1 => lang('none')];
$userlist = $userops->LoadUsers();
foreach ($userlist as $one) {
    if ($one->id != $user_id) {
        $sel[$one->id] = specialize($one->username);
    }
}

$smarty = AppSingle::Smarty();

if ($manage_groups) {
    $smarty->assign('groups', $group_list)
        ->assign('membergroups', $userops->GetMemberGroups($user_id));
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
    'active' => $active,
    'copyusersettings' => $copyusersettings,
    'email' => $email,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'manage_users' => $manage_users,
    'my_userid' => $userid,
//    'tplmaster' => $tplmaster, TODO
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'user_id' => $user_id,
    'users' => $sel,
    'user' => $username,
    'access_user' => $selfedit,
    'perm1usr' => $superusr,  //group 1 addition|removal allowed
    'perm1grp' => $supergrp, //group 1 removal allowed
]);

$content = $smarty->fetch('edituser.tpl');
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
