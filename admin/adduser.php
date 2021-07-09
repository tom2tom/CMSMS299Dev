<?php
/*
Add a new admin user
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

use CMSMS\AdminTheme;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Error403Exception;
use CMSMS\Events;
use CMSMS\ScriptsMerger;
use CMSMS\User;
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

$userid = get_userid();
if (!check_permission($userid, 'Manage Users')) {
    //TODO some pushed popup c.f. javascript:cms_notify('error', lang('no_permission') OR lang('needpermissionto', lang('perm_Manage_Users')), ...);
    throw new Error403Exception(lang('permissiondenied')); // OR display error.tpl ?
}

//--------- Variables ---------

$superusr = ($userid == 1); //group 1 addition|removal allowed
$groupops = AppSingle::GroupOperations();
$admins = array_column($groupops->GetGroupMembers(1), 1);
$supergrp = $superusr || in_array($userid, $admins); //group 1 removal allowed
$manage_groups = check_permission($userid, 'Manage Groups');
$userops = AppSingle::UserOperations();
$errors = [];

//--------- Logic ---------

if (isset($_POST['submit'])) {
    de_specialize_array($_POST);

    $active = !empty($_POST['active']);
    $copyusersettings = (int)$_POST['copyusersettings'];
    // cleanup inputs, which might now include content that was entitized after an error
    $tmp = trim($_POST['user']);
    $username = sanitizeVal($tmp, CMSSAN_ACCOUNT);
    if ($username != $tmp) {
        $errors[] = lang('illegalcharacters', lang('username'));
    } elseif (!($username || is_numeric($username))) { // allow username '0' ??
        $errors[] = lang('nofieldgiven', lang('username'));
    }

    $firstname = sanitizeVal(trim($_POST['firstname']), CMSSAN_NONPRINT); // OR no-gibberish 2.99 breaking change
    $lastname = sanitizeVal(trim($_POST['lastname']), CMSSAN_NONPRINT); // OR no-gibberish 2.99 breaking change

    $tmp = $_POST['passwordagain'] ?? '';
    //per https://pages.nist.gov/800-63-3/sp800-63b.html : valid = printable ASCII | space | Unicode
    $passagain = sanitizeVal($tmp, CMSSAN_NONPRINT);
    $tmp = $_POST['password'] ?? '';
    $password = sanitizeVal($tmp, CMSSAN_NONPRINT);
    if ($password != $tmp) {
        $errors[] = lang('illegalcharacters', lang('password'));
    } elseif (!$password) {
        $errors[] = lang('nofieldgiven', lang('password'));
    } elseif ($password != $passagain) {
        $errors[] = lang('nopasswordmatch');
    }

    //PHP's FILTER_VALIDATE_EMAIL mechanism is not entirely reliable - see notes at https://www.php.net/manual/en/function.filter-var.php
    //$email = filter_var($tmp, FILTER_SANITIZE_EMAIL);
    $tmp = trim($_POST['email']);
    $email = sanitizeVal($tmp, CMSSAN_NONPRINT);
    if (($email != $tmp) || ($email && !is_email($email))) {
        $errors[] = lang('invalidemail') . ': ' . $email;
        $checkmail = null; // prevent use in P/W check
    } else {
        $checkmail = $email;
    }

    //record properties involved in creds check
    $userobj = new User();
    $userobj->id = 0;
    $userobj->username = $username;
    $userobj->firstname = $firstname;
    $userobj->lastname = $lastname;
    $userobj->email = $checkmail; // i.e. used if valid
    $tmp = $userops->CredentialsCheck($userobj, $username, $password);
    if ($tmp) {
        $errors[] = $tmp;
    }

    $sel_groups = [];
    if (isset($_POST['sel_groups'])) {
        foreach ($_POST['sel_groups'] as &$one) {
            $sel_groups[] = (int)$one;
        }
        unset($one);
    }

    if (!$errors) {
        if ($userobj->SetPassword($password)) {
            $userobj->active = $active;
            Events::SendEvent('Core', 'AddUserPre', ['user' => $userobj]);

            $result = $userobj->save();
            if ($result) {
                Events::SendEvent('Core', 'AddUserPost', ['user' => $userobj]);

                // set some default preferences, based on the user creating this user
                $user_id = $userobj->id;
                if ($copyusersettings > 0) {
                    $prefs = UserParams::get_all_for_user($copyusersettings);
                    if ($prefs) {
                        foreach ($prefs as $k => $v) {
                            UserParams::set_for_user($user_id, $k, $v);
                        }
                    }
                } else {
                    UserParams::set_for_user($user_id, 'default_cms_language', UserParams::get_for_user($userid, 'default_cms_language'));
//                    UserParams::set_for_user($user_id, 'wysiwyg', UserParams::get_for_user($userid, 'wysiwyg')); //rich-text-editor type
//                    UserParams::set_for_user($user_id, 'syntaxhighlighter', UserParams::get_for_user($userid, 'syntaxhighlighter')); //syntax-editor module
//                    UserParams::set_for_user($user_id, 'syntax_theme', UserParams::get_for_user($userid, 'syntax_theme'));
                    $val = AppParams::get('logintheme');
                    if (!$val) {
                        $val = AdminTheme::GetDefaultTheme();
                    }
                    UserParams::set_for_user($user_id, 'admintheme', $val);
//                    UserParams::set_for_user($user_id, 'bookmarks', UserParams::get_for_user($userid, 'bookmarks'));
//                    UserParams::set_for_user($user_id, 'recent', UserParams::get_for_user($userid, 'recent'));
                    $val = AppParams::get('wysiwyg');
                    if ($val) {
                        UserParams::set_for_user($user_id, 'wysiwyg', $val);
                    }
                }

                if ($manage_groups && $sel_groups) {
                    $db = AppSingle::Db();
                    $iquery = 'INSERT INTO ' . CMS_DB_PREFIX . 'user_groups (user_id,group_id) VALUES (?,?)';
                    foreach ($sel_groups as $gid) {
                        if ($gid > 0) {
                            $db->Execute($iquery, [$user_id, $gid]);
                        }
                    }
                }

                // put mention into the admin log
                audit($userobj->id, 'Admin Username: ' . $userobj->username, 'Added');
                redirect('listusers.php'.$urlext);
            } else {
                $errors[] = lang('errorinsertinguser');
            }
        } else {
            $errors[] = lang('error_passwordinvalid');
        }
    }
    $email = specialize($email);
    $firstname = specialize($firstname);
    $lastname = specialize($lastname);
    $password = specialize($password);
    $passagain = specialize($passagain);
} else {
    $active = 1;
    $copyusersettings = 0;
    $email = '';
    $firstname = '';
    $lastname = '';
    $password = '';
    $passagain = '';
    $sel_groups = [];
    $username = '';
}

//---------- Display view ----------

$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery-inputCloak.js', 1);

//$nonce = get_csp_token();
$js = <<<EOS
$(function() {
 $('#password,#passagain').inputCloak({
  type:'see1',
  symbol:'\u25CF'
 });
});
EOS;
$jsm->queue_string($js, 3);
$out = $jsm->page_content();
if ($out) {
    add_page_foottext($out);
}

if ($errors) {
	AppSingle::Theme()->RecordNotice('error', $errors);
}

//data for user-selector
$sel = [-1 => lang('none')];
$userlist = $userops->LoadUsers();
foreach ($userlist as $one) {
    $sel[$one->id] = $one->username; // no specialize() needed?
}

if ($manage_groups) {
    $groups = $groupops->LoadGroups();
    if ($groups) {
		foreach ($groups as $obj) {
            $obj->name = specialize($obj->name);
			$obj->description = specialize($obj->description);
		}
    }
} else {
    $groups = null;
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = AppSingle::Smarty();

$smarty->assign([
    'active' => $active,
    'copyusersettings' => $copyusersettings,
    'email' => $email,
    'firstname' => $firstname,
    'lastname' => $lastname,
    'password' => $password,
    'passwordagain' => $passagain,
    'sel_groups' => $sel_groups,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
    'user' => $username,
    'users' => $sel,
    'groups' => $groups,
    'perm1usr' => $superusr,  //group 1 addition|removal allowed
    'perm1grp' => $supergrp, //group 1 removal allowed
]);

$content = $smarty->fetch('adduser.tpl');
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
