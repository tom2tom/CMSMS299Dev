<?php
#Add a new admin user
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

use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Events;
use CMSMS\User;
use CMSMS\UserParams;
use CMSMS\Utils;
use Throwable;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listusers.php' . $urlext);
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

$superusr      = ($userid == 1); //group 1 addition|removal allowed
$groupops      = AppSingle::GroupOperations();
$admins        = array_column($groupops->GetGroupMembers(1), 1);
$supergrp      = $superusr || in_array($userid, $admins); //group 1 removal allowed
$manage_groups = check_permission($userid, 'Manage Groups');
$errors        = [];

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

    $active           = !empty($_POST['active']);
//    $adminaccess      = !empty($_POST['adminaccess']); //whether the user may log in
    $copyusersettings = (int)$_POST['copyusersettings'];
    $email            = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $firstname        = $cleaner(trim($_POST['firstname']));
    $lastname         = $cleaner(trim($_POST['lastname']));
    $user             = $cleaner(trim($_POST['user']));
    $password         = $_POST['password']; //no cleanup: any char is valid, & hashed before storage
    $passwordagain    = $_POST['passwordagain'];
    $sel_groups       = cleanArray($_POST['sel_groups']);

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
        //TODO some intra-core policy & test
        $errors[] = lang('nofieldgiven', lang('password'));
    }

    if ($password && $password != $passwordagain) {
        // We don't want to see this if no password was given
        $errors[] = lang('nopasswordmatch');
    }

    if ($email && !is_email($email)) {
        $errors[] = lang('invalidemail') . ': ' . $email;
    }

    if (!$errors) {
        $newuser = new User();

        $newuser->active      = $active;
//        $newuser->adminaccess = $adminaccess;
        $newuser->firstname   = $firstname;
        $newuser->lastname    = $lastname;
        $newuser->email       = $email;
        $newuser->SetPassword($password);
        $newuser->username    = $user;

        Events::SendEvent( 'Core', 'AddUserPre', [ 'user'=>&$newuser ] );

        $result = $newuser->save();

        if ($result) {
            Events::SendEvent( 'Core', 'AddUserPost', [ 'user'=>&$newuser ] );

            // set some default preferences, based on the user creating this user
            $user_id = $newuser->id;
            if ($copyusersettings > 0) {
                $prefs = UserParams::get_all_for_user($copyusersettings);
                if ($prefs) {
                    foreach ($prefs as $k => $v) {
                        UserParams::set_for_user($user_id, $k, $v);
                    }
                }
            } else {
                UserParams::set_for_user($user_id, 'default_cms_language', UserParams::get_for_user($userid, 'default_cms_language'));
//                UserParams::set_for_user($user_id, 'wysiwyg', UserParams::get_for_user($userid, 'wysiwyg')); //rich-text-editor type
//                UserParams::set_for_user($user_id, 'syntax_editor', UserParams::get_for_user($userid, 'syntax_editor')); //syntax-editor type
//                UserParams::set_for_user($user_id, 'syntax_theme', UserParams::get_for_user($userid, 'syntax_theme'));
                $val = AppParams::get('logintheme');
                if( !$val ) $val = AdminTheme::GetDefaultTheme();
                UserParams::set_for_user($user_id, 'admintheme', $val);
//                UserParams::set_for_user($user_id, 'bookmarks', UserParams::get_for_user($userid, 'bookmarks'));
//                UserParams::set_for_user($user_id, 'recent', UserParams::get_for_user($userid, 'recent'));
                $val = AppParams::get('wysiwyg');
                if ($val) UserParams::set_for_user($uid, 'wysiwyg', $val);
            }

            if ($manage_groups && $sel_groups) {
                $db = AppSingle::Db();
                $iquery = 'INSERT INTO ' . CMS_DB_PREFIX . 'user_groups (user_id,group_id) VALUES (?,?)';
                foreach ($sel_groups as $gid) {
                    $gid = (int)$gid;
                    if ($gid > 0) {
                        $db->Execute($iquery, [$user_id, $gid]);
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
    $active           = 1;
//    $adminaccess      = 1;
    $copyusersettings = null;
    $email            = '';
    $firstname        = '';
    $lastname         = '';
    $password         = '';
    $passwordagain    = '';
    $sel_groups       = [];
    $user             = '';
}

/*--------------------
 * Display view
 ---------------------*/

if ($errors) {
    $themeObject->RecordNotice('error', $errors);
}

$smarty = AppSingle::Smarty();

if ($manage_groups) {
    $groups = $groupops->LoadGroups();
    $smarty->assign('groups', $groups);
} else {
    $smarty->assign('groups', null);
}

$out      = [-1 => lang('none')];
$userlist = AppSingle::UserOperations()->LoadUsers();

foreach ($userlist as $one) {
    $out[$one->id] = $one->username;
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty->assign([
    'active' => $active,
//    'adminaccess' => $adminaccess,
    'copyusersettings' => $copyusersettings,
    'email' => $email,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'password' => $password,
    'passwordagain' => $passwordagain,
    'sel_groups' => $sel_groups,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'user' => $user,
    'users' => $out, //for filter/selector
    'perm1usr' => $superusr,  //group 1 addition|removal allowed
    'perm1grp' => $supergrp, //group 1 removal allowed
]);

$content = $smarty->fetch('adduser.tpl');
require './header.php';
echo $content;
require './footer.php';
