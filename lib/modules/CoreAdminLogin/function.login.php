<?php
/*
admin-login module inclusion - does $_POST processing and provides related methods
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\HookManager;
use CMSMS\internal\LoginOperations;
use CMSMS\User;

/*
 * This expects some variables to be populated before inclusion:
 * $config
 * $usecsrf optional
 */

global $csrf_key;

/**
 * Send lost-password recovery email to a specified admin user
 *
 * @param object $user user data
 * @param object $mod current module-object
 * @return results from the attempt to send a message.
 */
function send_recovery_email(User $user, &$mod)
{
    global $config;
    
    $obj = new cms_mailer();
    $obj->IsHTML(true);
    $obj->AddAddress($user->email, cms_html_entity_decode($user->firstname . ' ' . $user->lastname));
    $obj->SetSubject($mod->Lang('lostpwemailsubject', html_entity_decode(cms_siteprefs::get('sitename', 'CMSMS Site'))));

    $url = $config['admin_url'] . '/login.php?recoverme=' . sha1($user->username . $user->password . CMS_ROOT_PATH);
    $body = $mod->Lang('lostpwemail', cms_html_entity_decode(cms_siteprefs::get('sitename', 'CMSMS Site')), $user->username, $url, $url);

    $obj->SetBody($body);
    return $obj->Send();
}

/**
 * Find the user id corresponding to the given identity-token
 *
 * @param string the token
 * @return object The matching user object if found, or null otherwise.
 */
function find_recovery_user(string $hash)
{
    $userops = CmsApp::get_instance()->GetUserOperations();

    foreach ($userops->LoadUsers() as $user) {
        if ($hash == sha1($user->username . $user->password . CMS_ROOT_PATH)) {
            return $user;
        }
    }
    return null;
}

/**
 * Check csrf-token validity
 * $param string $id location-identifier used in exception message
 * @param object $mod current module-object
 * @throws RuntimeException upon invalid token
 */
function check_secure_param(string $id, &$mod)
{
    global $csrf_key;

    $expected = $_SESSION[$csrf_key] ?? null;
    $provided = $_POST['csrf'] ?? null; //comparison value, no sanitize needed (unless $_SESSION hacked!)
    unset($_SESSION[$csrf_key], $_POST['csrf']);
    if (!$expected || !$provided || $expected != $provided) {
        throw new RuntimeException($mod->Lang('error_logintoken').' ('.$id.')');
    }
}

if (!empty($usecsrf)) {
    $csrf_key = md5(__FILE__);
}

$gCms = CmsApp::get_instance();
$login_ops = LoginOperations::get_instance();

//Redirect to the normal login screen if the user cancels on the forgot pw one
//Otherwise, check for a forgot-pw job
if ((isset($_REQUEST['forgotpwform']) || isset($_REQUEST['forgotpwchangeform'])) && isset($_REQUEST['logincancel'])) {
    redirect('login.php');
} elseif (isset($_REQUEST['forgotpwform']) && isset($_REQUEST['forgottenusername'])) {
    $userops = $gCms->GetUserOperations();
    $forgot_username = filter_var($_REQUEST['forgottenusername'], FILTER_SANITIZE_STRING);
    unset($_REQUEST['forgottenusername'],$_POST['forgottenusername']);
    HookManager::do_hook('Core::LostPassword', ['username'=>$forgot_username]);
    $oneuser = $userops->LoadUserByUsername($forgot_username);
    unset($_REQUEST['loginsubmit'],$_POST['loginsubmit']);

    if ($oneuser != null) {
        if ($oneuser->email == '') {
            $errmessage = $this->Lang('nopasswordforrecovery');
        } elseif (send_recovery_email($oneuser, $this)) {
            audit('', 'Core', 'Sent lost-password email for '.$user->username);
            $warnmessage = $this->Lang('recoveryemailsent');
        } else {
            $errmessage = $this->Lang('error_sendemail');
        }
    } else {
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        HookManager::do_hook('Core::LoginFailed', ['user'=>$forgot_username]);
        $errmessage = $this->Lang('error_nouser');
    }
} elseif (!empty($_REQUEST['recoverme'])) {
    if (!empty($usecsrf)) {
        try {
            check_secure_param('002', $this);
        } catch (Exception $e) {
            die('Invalid recovery request - 002');
        }
    }
    $user = find_recovery_user(cleanVariable($_REQUEST['recoverme']));
    if ($user == null) {
        $errmessage = $this->Lang('error_nouser');
    } else {
        $changepwtoken = true;
    }
} elseif (!empty($_REQUEST['forgotpwchangeform'])) {
    if (!empty($usecsrf)) {
        try {
            check_secure_param('003', $this);
        } catch (Exception $e) {
            die('Invalid recovery request - 003');
        }
    }
    $user = find_recovery_user($_REQUEST['changepwtoken']);
    if ($user == null) {
        $errmessage = $this->Lang('error_nouser');
    } elseif ($_REQUEST['password'] != '') {
        if ($_REQUEST['password'] == $_REQUEST['passwordagain']) {
            $user->SetPassword($_REQUEST['password']);
            $user->Save();
            // put mention into the admin log
            $ip_passw_recovery = cms_utils::get_real_ip();
            audit('', 'Core', 'Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
            HookManager::do_hook('Core::LostPasswordReset', ['uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery]);
            $infomessage = $this->Lang('passwordchangedlogin');
            $changepwtoken = '';
        } else {
            $errmessage = $this->Lang('error_nomatch');
            $changepwtoken = $_REQUEST['changepwtoken'];
        }
    } else {
        $errmessage = $this->Lang('error_nofield', $this->Lang('password'));
        $changepwtoken = $_REQUEST['changepwtoken'];
    }
}

if (isset($_SESSION['logout_user_now'])) {
    // this does the actual logout stuff.
    unset($_SESSION['logout_user_now']);
    debug_buffer('Logging out.  Cleaning cookies and session variables.');
    $userid = $login_ops->get_loggedin_uid();
    $username = $login_ops->get_loggedin_username();
    HookManager::do_hook('Core::LogoutPre', ['uid'=>$userid, 'username'=>$username]);
    $login_ops->deauthenticate(); // unset all the cruft needed to make sure we're logged in.
    HookManager::do_hook('Core::LogoutPost', ['uid'=>$userid, 'username'=>$username]);
    audit($userid, 'Admin Username: '.$username, 'Logged Out');
}

if (isset($_POST['cancel'])) {
//    $login_ops->deauthenticate(); // just in case
    redirect('login.php');
} elseif (isset($_POST['submit'])) {
    // login form submitted
    $login_ops->deauthenticate();
    $username = (isset($_POST['username'])) ? cleanValue($_POST['username']) : null;
    $password = $_POST['password'] ?? null; //no cleanup: any char is valid, & hashed before storage
    $userops = $gCms->GetUserOperations();

    class CmsLoginError extends CmsException {}

    try {
        if (!empty($usecsrf)) {
            check_secure_param('004', $this);
        }
        if (!$password) {
            throw new CmsLoginError($this->Lang('error_invalid'));
        }
        $oneuser = $userops->LoadUserByUsername($username, $password, true, true);
        if (!$oneuser) {
            throw new CmsLoginError($this->Lang('error_invalid'));
        }
        if (! $oneuser->Authenticate($password)) {
            throw new CmsLoginError($this->Lang('error_invalid'));
        }
        $login_ops->save_authentication($oneuser);

        // put mention into the admin log
        audit($oneuser->id, 'Admin Username: '.$oneuser->username, 'Logged In');

        // send the post login event
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        HookManager::do_hook('Core::LoginPost', ['user'=>&$oneuser]);

        // redirect outa hre somewhere
        if (isset($_SESSION['login_redirect_to'])) {
            // we previously attempted a URL but didn't have the user key in the request.
            $url_ob = new cms_url($_SESSION['login_redirect_to']);
            unset($_SESSION['login_redirect_to']);
            $url_ob->erase_queryvar('_s_');
            $url_ob->erase_queryvar('sp_');
            $url_ob->set_queryvar(CMS_SECURE_PARAM_NAME, $_SESSION[CMS_USER_KEY]);
            $url = (string) $url_ob;
            redirect($url);
        } else {
            // find the users homepage, if any, and redirect there.
            $homepage = cms_userprefs::get_for_user($oneuser->id, 'homepage');
            if (!$homepage) {
                $homepage = $config['admin_url'];
            }
            // quick hacks to remove old secure param name from homepage url
            // and replace with the correct one.
            $homepage = str_replace('&amp;', '&', $homepage);
            $tmp = explode('?', $homepage);
            @parse_str($tmp[1], $tmp2);
            if (in_array('_s_', array_keys($tmp2))) {
                unset($tmp2['_s_']);
            }
            if (in_array('sp_', array_keys($tmp2))) {
                unset($tmp2['sp_']);
            }
            $tmp2[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
            foreach ($tmp2 as $k => $v) {
                $tmp3[] = $k.'='.$v;
            }
            $homepage = $tmp[0].'?'.implode('&amp;', $tmp3);

            // and redirect.
            $homepage = cms_html_entity_decode($homepage);
            if (!startswith($homepage, 'http') && !startswith($homepage, '//') && startswith($homepage, '/')) {
                $homepage = CMS_ROOT_URL.$homepage;
            }
            redirect($homepage);
        }
    } catch (Exception $e) {
        $errmessage = $e->GetMessage();
        debug_buffer('Login failed.  Error is: ' . $errmessage);
        unset($_POST['password'],$_REQUEST['password']);
        HookManager::do_hook('Core::LoginFailed', ['user'=>$_POST['username']]);
        // put mention into the admin log
        $ip_login_failed = cms_utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . 'Admin Username: ' . $username, 'Login Failed');
    }
}
