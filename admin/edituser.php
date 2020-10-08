<?php
#Modify an existing admin user
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//use CMSMS\SysDataCache;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Events;
use CMSMS\UserParams;
use CMSMS\Utils;
use Throwable;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listusers.php'.$urlext);
}

$userid = get_userid();

$themeObject = Utils::get_theme_object();

if (!check_permission($userid, 'Manage Users')) {
//TODO some pushed popup c.f. javascript:cms_notify('error', lang('no_permission') OR lang('needpermissionto', lang('perm_Manage_Users')), ...);
    return;
}

/*--------------------
 * Variables
 ---------------------*/
//$tplmaster        = 0; //CHECKME
$copyfromtemplate = 1; //CHECKME
$errors           = [];

if (!empty($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id']; // OR filter_input(INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT);
} elseif (!empty($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
} else {
    $user_id = $userid;
}

$superusr      = ($userid == 1); //group 1 addition|removal allowed
$groupops      = AppSingle::GroupOperations();
$admins        = array_column($groupops->GetGroupMembers(1), 1);
$supergrp      = $superusr || in_array($userid, $admins); //group 1 removal allowed
$access_group  = in_array($userid, $admins) || !in_array($user_id, $admins); // ??
$selfedit      = ($userid == $user_id);
$access        = $selfedit && $access_group;
$userops       = AppSingle::UserOperations();
$thisuser      = $userops->LoadUserByID($user_id);
$group_list    = $groupops->LoadGroups();
$manage_groups = check_permission($userid, 'Manage Groups');
$manage_users  = check_permission($userid, 'Manage Users');

/*--------------------
 * Logic
 ---------------------*/

if (isset($_POST['submit'])) {

    $cleaner = function($val) {
        if ($val) {
            $val = filter_var($val.'', FILTER_SANITIZE_STRING); //strip HTML,XML,PHP tags, NUL bytes
            $val = preg_replace_callback('/\W/', function($matches) {
                $n = ord($matches[0]);
                if (in_array($n, [32,33,35,36,37,38,44,46,124,125,126]) || $n > 127) {
                    return $matches[0];
                }
                return '';
            }, $val);
        }
        return $val;
    };

    if (!$selfedit) {
        $active      = !empty($_POST['active']);
//        $adminaccess = !empty($_POST['adminaccess']); //whether the user may log in
    } elseif ($user_id != -1) {
        $active      = $thisuser->active;
//        $adminaccess = $thisuser->adminaccess;
    } else { //should never happen when editing
        $active      = 1;
//        $adminaccess = 1;
    }
    $email         = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $firstname     = $cleaner(trim($_POST['firstname']));
    $lastname      = $cleaner(trim($_POST['lastname']));
    $user          = $cleaner(trim($_POST['user']));
    $password      = $_POST['password']; //no cleanup: any char is valid, & hashed before storage
    $passwordagain = $_POST['passwordagain'];

    $error = false;
    // validate
    if ($user == '') {
        $errors[] = lang('nofieldgiven', lang('username'));
    } elseif ($user != trim($_POST['user'])) {
        $errors[] = lang('illegalcharacters', lang('username'));
    }

    if ($password) {
        //TODO some intra-core policy & test
        try {
            Events::SendEvent('Core', 'PasswordStrengthTest', $password);
        }
        catch( Throwable $t ) {
            $errors[] = $t->GetMessage();
        }
    } else {
        // no password-change
    }

    if ($password && $password != $passwordagain) {
        // We don't want to see this if no password was given
        $errors[] = lang('nopasswordmatch');
    }

    if ($email && !is_email($email)) {
        $errors[] = lang('invalidemail') . ': ' . $email;
    }

    if (isset($_POST['copyusersettings']) && $_POST['copyusersettings'] > 0) {
        if (isset($_POST['clearusersettings'])) {
            // error: both can't be set
            $errors[] = lang('error_multiusersettings');
        }
    }

    if (!$errors) {
        // save data
        $result = false;
        if ($thisuser) {
            $thisuser->active      = $active;
//            $thisuser->adminaccess = $adminaccess;
            $thisuser->email       = $email;
            $thisuser->firstname   = $firstname;
            $thisuser->lastname    = $lastname;
            if ($password) {
                $thisuser->SetPassword($password);
            }
            $thisuser->username    = $user;

            Events::SendEvent('Core', 'EditUserPre', [ 'user' => &$thisuser ]);

            $result = $thisuser->save();
            if ($manage_groups && isset($_POST['groups'])) {
                $db = AppSingle::Db();
                $stmt1 = $db->Prepare('DELETE FROM ' . CMS_DB_PREFIX . 'user_groups WHERE user_id=? AND group_id=?');
                $stmt2 = $db->Prepare('SELECT 1 FROM ' . CMS_DB_PREFIX . 'user_groups WHERE user_id=? AND group_id=?');
                $stmt3 = $db->Prepare('INSERT INTO ' . CMS_DB_PREFIX . 'user_groups (user_id,group_id) VALUES (?,?)');
                foreach ($group_list as $thisGroup) {
                    $gid = $thisGroup->id;
                    if (isset($_POST['g' . $gid])) {
                        $uid = $thisuser->id;
                        if ($_POST['g' . $gid] == 0) {
                            $db->Execute($stmt1, [$uid, $gid]); // fails if already absent
                        } elseif ($_POST['g' . $gid] == 1) {
                            $rst = $db->Execute($stmt2, [$uid, $gid]);
                            if (!$rst || $rst->EOF) {
                                $db->Execute($stmt2, [$uid, $gid]);
                            }
                            if ($rst) $rst->Close();
                        }
                    }
                }
                $stmt1->close();
                $stmt2->close();
                $stmt3->close();
            }
        }

        // put mention into the admin log
        audit($userid, 'Admin Username: ' . $thisuser->username, ' Edited');
        $message = lang('edited_user');

        if ($result) {
            if (isset($_POST['copyusersettings']) && $_POST['copyusersettings'] > 0) {
                // copy user preferences from the template user to this user.
                $prefs = UserParams::get_all_for_user((int)$_POST['copyusersettings']);
                if ($prefs) {
                    UserParams::remove_for_user($user_id);
                    foreach ($prefs as $k => $v) {
                        UserParams::set_for_user($user_id, $k, $v);
                    }
                    audit($user_id, 'Admin Username: ' . $thisuser->username, 'settings copied from template user');
                    $message = lang('msg_usersettingscopied');
                }
            } elseif (isset($_POST['clearusersettings'])) {
                // clear all preferences for this user.
                audit($user_id, 'Admin Username: ' . $thisuser->username, ' settings cleared');
                UserParams::remove_for_user($user_id);
                $message = lang('msg_usersettingscleared');
            }

            Events::SendEvent('Core', 'EditUserPost', [ 'user'=>&$thisuser ] );
//            AdminUtils::clear_cached_files();
//            SysDataCache::get_instance()->release('IF ANY');
            $url = 'listusers.php?' . $urlext;
            if ($message) {
                $message = urlencode($message);
                $url .= '&message=' . $message;
            }
            redirect($url);
        } else {
            $themeObject->RecordNotice('error', lang('errorupdatinguser'));
        }
    }
}
elseif ($user_id != -1) {
    $active        = $thisuser->active;
//    $adminaccess   = $thisuser->adminaccess;
    $email         = $thisuser->email;
    $firstname     = $thisuser->firstname;
    $lastname      = $thisuser->lastname;
    $password      = '';
    $passwordagain = '';
    $user          = $thisuser->username;
}
else { //should never happen when editing
    $active        = 1;
//    $adminaccess   = 1;
    $email         = '';
    $firstname     = '';
    $lastname      = '';
    $password      = '';
    $passwordagain = '';
    $user          = '';
}

/*--------------------
 * Display view
 ---------------------*/

if ($errors) {
    $themeObject->RecordNotice('error', $errors);
}

$confirm = json_encode(lang('confirm_edituser'));
$out = <<<EOS
<script type="text/javascript">
 //<![CDATA[
 $(function() {
  $('#submit').on('click', function(ev) {
   ev.preventDefault();
   cms_confirm_btnclick(this, $confirm);
   return false;
  });
  $('#copyusersettings').change(function() {
   var v = $(this).val();
   if(v === -1) {
    $('#clearusersettings').removeAttr('disabled');
   } else {
    $('#clearusersettings').attr('disabled', 'disabled');
   }
  });
  $('#clearusersettings').on('click', function() {
   $('#copyusersettings').val(-1);
   var v = $(this).attr('checked');
   if(v === 'checked') {
    $('#copyusersettings').attr('disabled', 'disabled');
   } else {
    $('#copyusersettings').removeAttr('disabled');
   }
  });
 });
 //]]>
</script>
EOS;
add_page_foottext($out);

$out = [-1 => lang('none')];
$userlist = $userops->LoadUsers();
foreach ($userlist as $one) {
    if ($one->id != $user_id) {
        $out[$one->id] = $one->username;
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
//    'adminaccess' => $adminaccess,
    'copyfromtemplate' => $copyfromtemplate,
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
    'users' => $out, //for user-selector
    'user' => $user,
    'access_user' => $selfedit,
    'perm1usr' => $superusr,  //group 1 addition|removal allowed
    'perm1grp' => $supergrp, //group 1 removal allowed
]);

$content = $smarty->fetch('edituser.tpl');
require './header.php';
echo $content;
require './footer.php';
