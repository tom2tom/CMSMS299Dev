<?php
#...
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
use CMSMS\GroupOperations;
use CMSMS\UserOperations;
use Exception;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listusers.php'.$urlext);
    return;
}

$userid = get_userid();

$themeObject = cms_utils::get_theme_object();

if (!check_permission($userid, 'Manage Users')) {
//TODO some immediate popup    $themeObject->RecordNotice('error', lang('TODO', 'Manage Users'));
    return;
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

$themeObject->add_footertext($out);

/*--------------------
 * Variables
 ---------------------*/
$gCms              = cmsms();
$db                = $gCms->GetDb();
$error             = '';
$dropdown          = '';
$adminaccess       = 1;
$active            = 1;
$tplmaster         = 0;
$copyfromtemplate  = 1;
$message           = '';
$user_id           = $userid;
// Post data TODO WHERE
//$user              = cleanValue($_POST['user']);
//$password          = $_POST['password']; //no cleanup: any char is valid, & hashed before storage
//$passwordagain     = $_POST['passwordagain'];
//$firstname         = cleanValue($_POST['firstname']);
//$lastname          = cleanValue($_POST['lastname']);
//$email             = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

if (isset($_POST['user_id'])) {
    $user_id = (int)$_POST['user_id'];
} elseif (isset($_GET['user_id'])) {
    $user_id = (int)$_GET['user_id'];
}

// this is now always true... but we may want to change how things work, so I'll leave it
$userops           = UserOperations::get_instance();
$groupops          = GroupOperations::get_instance();
$group_list        = $groupops->LoadGroups();
$access_user       = ($userid == $user_id);
$access_group      = $userops->UserInGroup($userid, 1) || (!$userops->UserInGroup($user_id, 1));
$access            = $access_user && $access_group;
$assign_group_perm = check_permission($userid, 'Manage Groups');
$manage_users      = check_permission($userid, 'Manage Users');
$thisuser          = $userops->LoadUserByID($user_id);

/*--------------------
 * Logic
 ---------------------*/

if (isset($_POST['submit'])) {

    if( !$access_user ) $active = isset($_POST['active']);

    $adminaccess = isset($_POST['adminaccess']) ? 1 : 0;
    $error = false;

    // check for errors
    if ($user == '') {
        $error = true;
        $themeObject->RecordNotice('error', lang('nofieldgiven', lang('username')));
    }

    if (!preg_match("/^[a-zA-Z0-9\._ ]+$/", $user)) {
        $error = true;
        $themeObject->RecordNotice('error', lang('illegalcharacters', lang('username')));
    }

    if ($password != $passwordagain) {
        $error = true;
        $themeObject->RecordNotice('error', lang('nopasswordmatch'));
    }

    if (!empty($email) && !is_email($email)) {
        $error = true;
        $themeObject->RecordNotice('error', lang('invalidemail') . ': ' . $email);
    }

    if( !empty($password) ) {
        try {
            Events::SendEvent('Core', 'PasswordStrengthTest', $password );
        }
        catch( Exception $e ) {
            $validinfo = false;
            $error .= '<li>'.$e->GetMessage().'</li>';
        }
    }

    if (isset($_POST['copyusersettings']) && $_POST['copyusersettings'] > 0) {
        if (isset($_POST['clearusersettings'])) {
            // error: both can't be set
            $error = true;
            $themeObject->RecordNotice('error', lang('error_multiusersettings'));
        }
    }

    // save data
    if (!$error) {
        $result = false;
        if ($thisuser) {
            $thisuser->username    = $user;
            $thisuser->firstname   = $firstname;
            $thisuser->lastname    = $lastname;
            $thisuser->email       = $email;
            $thisuser->adminaccess = $adminaccess;
            $thisuser->active      = $active;
            if ($password != '') {
                $thisuser->SetPassword($password);
            }

            Events::SendEvent('Core', 'EditUserPre', [ 'user'=>&$thisuser ] );

            $result = $thisuser->save();
            if ($assign_group_perm && isset($_POST['groups'])) {
                $dquery = 'DELETE FROM ' . CMS_DB_PREFIX . 'user_groups WHERE user_id=?';
                $stmt = $db->Prepare('INSERT INTO ' . CMS_DB_PREFIX . 'user_groups (user_id,group_id) VALUES (?,?)');
                $result = $db->Execute($dquery, [$thisuser->id]);
                foreach ($group_list as $thisGroup) {
                    if (isset($_POST['g' . $thisGroup->id]) && $_POST['g' . $thisGroup->id] == 1) {
                        $result = $db->Execute($stmt, [
                            $thisuser->id,
                            $thisGroup->id
                        ]);
                    }
                }
                $stmt->close();
            }
        }

        // put mention into the admin log
        audit($userid, 'Admin Username: ' . $thisuser->username, ' Edited');
        $message = lang('edited_user');

        if ($result) {
            if (isset($_POST['copyusersettings']) && $_POST['copyusersettings'] > 0) {
                // copy user preferences from the template user to this user.
                $prefs = cms_userprefs::get_all_for_user((int)$_POST['copyusersettings']);
                if ($prefs) {
                    cms_userprefs::remove_for_user($user_id);
                    foreach ($prefs as $k => $v) {
                        cms_userprefs::set_for_user($user_id, $k, $v);
                    }
                    audit($user_id, 'Admin Username: ' . $thisuser->username, 'settings copied from template user');
                    $message = lang('msg_usersettingscopied');
                }
            } else if (isset($_POST['clearusersettings'])) {
                // clear all preferences for this user.
                audit($user_id, 'Admin Username: ' . $thisuser->username, ' settings cleared');
                cms_userprefs::remove_for_user($user_id);
                $message = lang('msg_usersettingscleared');
            }

            Events::SendEvent('Core', 'EditUserPost', [ 'user'=>&$thisuser ] );
//            AdminUtils::clear_cached_files();
//            global_cache::release('IF ANY');
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

} elseif ($user_id != -1) {
    $user        = $thisuser->username;
    $firstname   = $thisuser->firstname;
    $lastname    = $thisuser->lastname;
    $email       = $thisuser->email;
    $adminaccess = $thisuser->adminaccess;
    $active      = $thisuser->active;
}

/*--------------------
 * Display view
 ---------------------*/

if (!empty($error)) {
    $themeObject->RecordNotice('error', $error);
}

$out      = [-1 => lang('none')];

$userlist = $userops->LoadUsers();
foreach ($userlist as $one) {
    if ($one->id == $user_id) continue;
    $out[$one->id] = $one->username;
}

$smarty = CmsApp::get_instance()->GetSmarty();

if ($assign_group_perm && !$access_user) {
    $smarty->assign('groups', $groupops->LoadGroups())
      ->assign('membergroups', $userops->GetMemberGroups($user_id));
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
    'access_user' => $access_user,
    'active' => $active,
    'adminaccess' => $adminaccess,
    'copyfromtemplate' => $copyfromtemplate,
    'email' => $email,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'manage_users' => $manage_users,
    'tplmaster' => $tplmaster,
    'selfurl' => $selfurl,
	'extraparms' => $extras,
    'urlext' => $urlext,
    'user_id' => $user_id,
    'users' => $out,
    'user' => $user,
]);

include_once 'header.php';
$smarty->display('edituser.tpl');
include_once 'footer.php';
