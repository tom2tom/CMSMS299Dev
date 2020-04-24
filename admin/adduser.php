<?php
#procedure to add an admin user
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

use CMSMS\AppState;
use CMSMS\Events;
use CMSMS\GroupOperations;
use CMSMS\AdminTheme;
use CMSMS\User;
use CMSMS\UserOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listusers.php' . $urlext);
//    return;
}

$userid = get_userid();

$themeObject = cms_utils::get_theme_object();

if (!check_permission($userid, 'Manage Users')) {
//TODO some immediate popup   lang('needpermissionto', '"Manage Users"'));
    return;
}

/*--------------------
 * Variables
 ---------------------*/

$gCms              = cmsms();
$assign_group_perm = check_permission($userid, 'Manage Groups');
$groupops          = GroupOperations::get_instance();
$errors            = [];

if (isset($_POST['submit'])) {

    $user             = cleanValue($_POST['user']);
    $password         = $_POST['password']; //no cleanup: any char is valid, & hashed before storage
    $passwordagain    = $_POST['passwordagain'];
    $firstname        = cleanValue($_POST['firstname']);
    $lastname         = cleanValue($_POST['lastname']);
    $email            = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $copyusersettings = (int)$_POST['copyusersettings'];
    $sel_groups       = cleanArray($_POST['sel_groups']);
    $active           = (isset($_POST['active'])) ? 1 : 0;
    $adminaccess      = (isset($_POST['adminaccess'])) ? 1 : 0;

    if ($user == '') {
        $error = true;
        (lang('nofieldgiven', lang('username')));
    } elseif (!preg_match('/^[a-zA-Z0-9\._ ]+$/', $user)) {
        $errors[] = lang('illegalcharacters', lang('username'));
    }

    if ($password == '') {
        $errors[] = lang('nofieldgiven', lang('password'));
    } elseif ($password != $passwordagain) {
        // We don't want to see this if no password was given
        $errors[] = lang('nopasswordmatch');
    }

    if (!empty($email) && !is_email($email)) {
        $errors[] = lang('invalidemail');
    }

    if (!$errors) {
        $newuser = new User();

        $newuser->username    = $user;
        $newuser->active      = $active;
        $newuser->firstname   = $firstname;
        $newuser->lastname    = $lastname;
        $newuser->email       = $email;
        $newuser->adminaccess = $adminaccess;
        $newuser->SetPassword($password);

        Events::SendEvent( 'Core', 'AddUserPre', [ 'user'=>&$newuser ] );

        $result = $newuser->save();

        if ($result) {
            Events::SendEvent( 'Core', 'AddUserPost', [ 'user'=>&$newuser ] );

            // set some default preferences, based on the user creating this user
            $adminid = get_userid();
            $userid = $newuser->id;
            if ($copyusersettings > 0) {
                $prefs = cms_userprefs::get_all_for_user($copyusersettings);
                if ($prefs) {
                    foreach ($prefs as $k => $v) {
                        cms_userprefs::set_for_user($userid, $k, $v);
                    }
                }
            } else {
                cms_userprefs::set_for_user($userid, 'default_cms_language', cms_userprefs::get_for_user($adminid, 'default_cms_language'));
//                cms_userprefs::set_for_user($userid, 'wysiwyg', cms_userprefs::get_for_user($adminid, 'wysiwyg')); //rich-text-editor type
//                cms_userprefs::set_for_user($userid, 'syntax_editor', cms_userprefs::get_for_user($adminid, 'syntax_editor')); //syntax-editor type
//                cms_userprefs::set_for_user($userid, 'syntax_theme', cms_userprefs::get_for_user($adminid, 'syntax_theme'));
                $val = cms_siteprefs::get('logintheme');
                if( !$val ) $val = AdminTheme::GetDefaultTheme();
                cms_userprefs::set_for_user($userid, 'admintheme', $val);
//                cms_userprefs::set_for_user($userid, 'bookmarks', cms_userprefs::get_for_user($adminid, 'bookmarks'));
//                cms_userprefs::set_for_user($userid, 'recent', cms_userprefs::get_for_user($adminid, 'recent'));
                $val = cms_siteprefs::get('wysiwyg');
                if( $val ) cms_userprefs::set_for_user($uid, 'wysiwyg', $val);
            }

            if ($assign_group_perm && $sel_groups) {
                $db = $gCms->GetDb();
                $iquery = 'INSERT INTO ' . CMS_DB_PREFIX . 'user_groups (user_id,group_id) VALUES (?,?)';
                foreach ($sel_groups as $gid) {
                    $gid = (int)$gid;
                    if ($gid > 0) {
                        $db->Execute($iquery, [$userid, $gid]);
                    }
                }
            }

            // put mention into the admin log
            audit($newuser->id, 'Admin Username: ' . $newuser->username, 'Added');
            redirect('listusers.php' . $urlext);
        } else {
            $errors[] = lang('errorinsertinguser');
        }
    }
} else {
    $user              = '';
    $firstname         = '';
    $lastname          = '';
    $userid            = -1;
    $password          = '';
    $passwordagain     = '';
    $email             = '';
    $sel_groups        = [];
    $adminaccess       = 1;
    $active            = 1;
    $copyusersettings  = null;
}

$smarty = CmsApp::get_instance()->GetSmarty();

/*--------------------
 * Display view
 ---------------------*/
if ($errors) {
    $themeObject->RecordNotice('error', $errors);
}

$out      = [-1 => lang('none')];
$userlist = UserOperations::get_instance()->LoadUsers();

foreach ($userlist as $one) {
    $out[$one->id] = $one->username;
}

if ($assign_group_perm) {
    $groups = $groupops->LoadGroups();
    $smarty->assign('groups', $groups);
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
    'active' => $active,
    'adminaccess' => $adminaccess,
    'copyusersettings' => $copyusersettings,
    'email' => $email,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'my_userid' => $userid,
    'password' => $password,
    'passwordagain' => $passwordagain,
    'sel_groups' => $sel_groups,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'user' => $user,
    'users' => $out,
]);

include_once 'header.php';
$smarty->display('adduser.tpl');
include_once 'footer.php';
