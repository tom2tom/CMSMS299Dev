<?php
/*
admin-login module inclusion - does $_POST processing and provides related methods
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Events;
use CMSMS\internal\LoginOperations;
use CMSMS\Mailer;
use CMSMS\User;
use CMSMS\UserOperations;

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

    $obj = new Mailer();
    $obj->IsHTML(true);
    $obj->AddAddress($user->email, cms_html_entity_decode($user->firstname . ' ' . $user->lastname));
	$name = html_entity_decode(cms_siteprefs::get('sitename', 'CMSMS Site'));
    $obj->SetSubject($mod->Lang('lostpwemailsubject', $name));

    $url = $config['admin_url'] . '/login.php?recoverme=' . sha1($user->username . $user->password . CMS_ROOT_PATH);
    $body = $mod->Lang('lostpwemail', $name, $user->username, $url, $url);

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
    $userops = UserOperations::get_instance();

    foreach ($userops->LoadUsers() as $user) {
        if ($hash == sha1($user->username . $user->password . CMS_ROOT_PATH)) {
            return $user;
        }
    }
    return null;
}

/**
 * Check secure-key (aka CSRF-token) validity
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

//Redirect to the normal login form if the user cancels on the forgot p/w form
if ((isset($_REQUEST['forgotpwform']) || isset($_REQUEST['forgotpwchangeform'])) && isset($_REQUEST['logincancel'])) {
    redirect('login.php');
}

if (!empty($usecsrf)) {
    $csrf_key = md5(__FILE__);
}

$userops = UserOperations::get_instance();
$login_ops = LoginOperations::get_instance();

//Check for a forgot-pw job
if (isset($_REQUEST['forgotpwform']) && isset($_REQUEST['forgottenusername'])) {
    $forgot_username = filter_var($_REQUEST['forgottenusername'], FILTER_SANITIZE_STRING);
    Events::SendEvent('Core', 'LostPassword', ['username'=>$forgot_username]);
    $user = $userops->GetRecoveryData($forgot_username);
    unset($_REQUEST['loginsubmit'], $_POST['loginsubmit']);

    if ($user != null) {
        if ($user->email == '') {
            $errmessage = $this->Lang('nopasswordforrecovery');
        } elseif (send_recovery_email($user, $this)) {
            audit('', 'Core', 'Sent lost-password email for '.$forgot_username);
            $infomessage = $this->Lang('recoveryemailsent');
        } else {
            $errmessage = $this->Lang('error_sendemail');
        }
    } else {
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        Events::SendEvent('Core', 'LoginFailed', ['user'=>$forgot_username]);
        $errmessage = $this->Lang('error_nouser');
    }
    return;
} elseif (!empty($_REQUEST['recoverme'])) {
    $user = find_recovery_user(cleanValue($_REQUEST['recoverme']));
    if ($user != null) {
        $changepwtoken = true;
		$changepwhash = $_REQUEST['recoverme'];
    } else {
        $errmessage = $this->Lang('error_nouser');
    }
    return;
} elseif (!empty($_REQUEST['forgotpwchangeform'])) {
    if (!empty($usecsrf)) {
        try {
            check_secure_param('003', $this);
			$usecsrf = false; //another check not necessary or possible
        } catch (Exception $e) {
            die('Invalid recovery request - 003');
        }
    }
    $user = find_recovery_user($_REQUEST['changepwhash']);
    if ($user == null) {
        $errmessage = $this->Lang('error_nouser');
    } elseif ($_REQUEST['password'] != '') {
        if ($_REQUEST['password'] == $_REQUEST['passwordagain']) {
            $user->SetPassword($_REQUEST['password']);
            $user->Save();
            // put mention into the admin log
            $ip_passw_recovery = cms_utils::get_real_ip();
            audit('', 'Core', 'Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
            Events::SendEvent('Core', 'LostPasswordReset', ['uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery]);
//            $infomessage = $this->Lang('passwordchangedlogin');
//            $changepwhash = '';
        } else {
            $errmessage = $this->Lang('error_nomatch');
            $changepwhash = $_REQUEST['changepwhash'];
		    return;
        }
    } else {
        $errmessage = $this->Lang('error_nofield', $this->Lang('password'));
        $changepwhash = $_REQUEST['changepwhash'];
	    return;
    }
}

if (isset($_SESSION['logout_user_now'])) {
    // this does the actual logout stuff.
    unset($_SESSION['logout_user_now']);
    debug_buffer('Logging out.  Cleaning cookies and session variables.');
    $userid = $login_ops->get_loggedin_uid();
    $username = $login_ops->get_loggedin_username();
    Events::SendEvent('Core', 'LogoutPre', ['uid'=>$userid, 'username'=>$username]);
    $login_ops->deauthenticate(); // unset all the cruft needed to make sure we're logged in.
    Events::SendEvent('Core', 'LogoutPost', ['uid'=>$userid, 'username'=>$username]);
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

    class CmsLoginError extends CmsException {}

    try {
        if (!empty($usecsrf)) {
            check_secure_param('004', $this);
        }
        if (!$password) {
            throw new CmsLoginError($this->Lang('error_invalid'));
        }
        $user = $userops->LoadUserByUsername($username, $password, true, true);
        if (!$user) {
            throw new CmsLoginError($this->Lang('error_invalid'));
        }
        if (! $user->Authenticate($password)) {
            throw new CmsLoginError($this->Lang('error_invalid'));
        }
        $login_ops->save_authentication($user);
        $_SESSION[CMS_USER_KEY] = $login_ops->create_csrf_token();

        // put mention into the admin log
        audit($user->id, 'Admin Username: '.$user->username, 'Logged In');

        // send the post login event
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        Events::SendEvent('Core', 'LoginPost', ['user'=>&$user]);

        // redirect outa here somewhere
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
            // find the user's homepage, if any, and redirect there.
            $url = cms_userprefs::get_for_user($user->id, 'homepage');
            if (!$url) {
                $url = $config['admin_url'];
            }
            // quick hacks to remove old secure param name from homepage url
            // and replace with the correct one.
            $url = str_replace('&amp;', '&', $url);
            $tmp = explode('?', $url);
            $tmp2 = [];
            @parse_str($tmp[1], $tmp2);
            if (isset($tmp2['_s_'])) {
                unset($tmp2['_s_']);
            }
            if (isset($tmp2['sp_'])) {
                unset($tmp2['sp_']);
            }
            $tmp2[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
            foreach ($tmp2 as $k => $v) {
                $tmp3[] = $k.'='.$v;
            }
            $url = $tmp[0].'?'.implode('&amp;', $tmp3);

            // and redirect.
            $url = cms_html_entity_decode($url); //???
            //TODO generally support the websocket protocol
            if (!startswith($url, 'http') && !startswith($url, '//') && startswith($url, '/')) {
                $url = CMS_ROOT_URL.$url;
            }
            redirect($url);
        }
    } catch (Exception $e) {
        $errmessage = $e->GetMessage();
        debug_buffer('Login failed.  Error is: ' . $errmessage);
		$username = $_REQUEST['username'] ?? $_REQUEST['forgottenusername'] ?? 'Missing';
        Events::SendEvent('Core', 'LoginFailed', ['user'=>$username]);
        // put mention into the admin log
        $ip_login_failed = cms_utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . 'Admin Username: ' . $username, 'Login Failed');
    }
    unset($_REQUEST['forgottenusername'],$_POST['forgottenusername']);
}
