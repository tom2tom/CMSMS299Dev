<?php
# admin login processing for inclusion by themes
# Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Crypto;
use CMSMS\Events;
use CMSMS\internal\LoginOperations;
use CMSMS\Mailer;
use CMSMS\NlsOperations;
use CMSMS\User;
use CMSMS\UserOperations;
use CMSMS\UserParams;
use CMSMS\Utils;

// This expects optional variable $usecsrf to be populated before inclusion

global $csrf_key;

/**
 * Send lost-password recovery email to a specified admin user
 *
 * @param object $user user data
 * @return results from the attempt to send a message.
 */
function send_recovery_email(User $user)
{
    $obj = new Mailer();
    $obj->IsHTML(true);
    $obj->AddAddress($user->email, cms_html_entity_decode($user->firstname . ' ' . $user->lastname));
    $name = html_entity_decode(AppParams::get('sitename', 'CMSMS Site'));
    $obj->SetSubject(lang('lostpwemailsubject', $name));

    $config = AppSingle::Config();
    $url = $config['admin_url'] . '/login.php?recoverme=' . sha1($user->username . $user->password . CMS_ROOT_PATH);
    $body = lang('lostpwemail', $name, $user->username, $url, $url);

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
    $user_ops = UserOperations::get_instance();

    foreach ($user_ops->LoadUsers() as $user) {
        if ($hash == sha1($user->username . $user->password . CMS_ROOT_PATH)) {
            return $user;
        }
    }
    return null;
}

/**
 * Check secure-key (aka CSRF-token) validity
 * $param string location-identifier used in exception message
 * @throws RuntimeException upon invalid token
 */
function check_secure_param(string $id)
{
    global $csrf_key;

    $expected = $_SESSION[$csrf_key] ?? null;
    $provided = $_POST['csrf'] ?? null; //comparison value, no sanitize needed (unless $_SESSION hacked!)
    unset($_SESSION[$csrf_key], $_POST['csrf']);
    if (!$expected || !$provided || $expected != $provided) {
        throw new RuntimeException(lang('error_logintoken').' ('.$id.')');
    }
}

//Redirect to the normal login screen if we hit cancel on the forgot pw one
if ((isset($_REQUEST['forgotpwform']) || isset($_REQUEST['forgotpwchangeform'])) && isset($_REQUEST['logincancel'])) {
    redirect('login.php');
}

if (!empty($usecsrf)) {
    $csrf_key = hash('tiger192,3', AppSingle::App()->GetSiteUUID());
}

//Check for a forgot-pw job
if (isset($_REQUEST['forgotpwform']) && isset($_REQUEST['forgottenusername'])) {
    $user_ops = UserOperations::get_instance();
    $forgot_username = filter_var($_REQUEST['forgottenusername'], FILTER_SANITIZE_STRING); // OR utils::clean_string() ?
    unset($_REQUEST['forgottenusername'], $_POST['forgottenusername']);
    Events::SendEvent('Core', 'LostPassword', ['username' => $forgot_username]);
    $user = $user_ops->GetRecoveryData($forgot_username);
    unset($_REQUEST['loginsubmit'], $_POST['loginsubmit']);

    if ($user != null) {
        if ($user->email == '') {
            $error = lang('nopasswordforrecovery');
        } elseif (send_recovery_email($user)) {
            audit('', 'Core', 'Sent lost-password email for '.$user->username);
            $message = lang('recoveryemailsent');
        } else {
            $error = lang('errorsendingemail');
        }
    } else {
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        Events::SendEvent('Core', 'LoginFailed', ['user' => $forgot_username]);
        $error = lang('usernotfound');
    }
    return;
} elseif (!empty($_REQUEST['recoverme'])) {
    $user = find_recovery_user(cleanValue($_REQUEST['recoverme']));
    if ($user != null) {
        $changepwtoken = true;
        $changepwhash = $_REQUEST['recoverme'];
    } else {
        $error = lang('usernotfound');
    }
    return;
} elseif (!empty($_REQUEST['forgotpwchangeform'])) {
    if (!empty($usecsrf)) {
        try {
            check_secure_param('003');
            $usecsrf = false; //another check not necessary or possible
        } catch (Throwable $e) {
            die('Invalid recovery request - 003');
        }
    }
    $user = find_recovery_user($_REQUEST['changepwhash']);
    if ($user == null) {
        $error = lang('usernotfound');
    } elseif ($_REQUEST['password'] != '') {
        if ($_REQUEST['password'] == $_REQUEST['passwordagain']) {
            $user->SetPassword($_REQUEST['password']);
            $user->Save();
            // put mention into the admin log
            $ip_passw_recovery = Utils::get_real_ip();
            audit('', 'Core', 'Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
            Events::SendEvent('Core', 'LostPasswordReset', ['uid' => $user->id, 'username' => $user->username, 'ip' => $ip_passw_recovery]);
            $message = lang('passwordchangedlogin');
            $changepwhash = '';
        } else {
            $error = lang('nopasswordmatch');
            $changepwhash = $_REQUEST['changepwhash'];
            return;
        }
    } else {
        $error = lang('nofieldgiven', lang('password'));
        $changepwhash = $_REQUEST['changepwhash'];
        return;
    }
}

$login_ops = LoginOperations::get_instance();
$config = AppSingle::Config();

if (isset($_SESSION['logout_user_now'])) {
    // this does the actual logout stuff.
    unset($_SESSION['logout_user_now']);
    debug_buffer('Logging out.  Cleaning cookies and session variables.');
    $userid = $login_ops->get_loggedin_uid();
    $username = $login_ops->get_loggedin_username();
    Events::SendEvent('Core', 'LogoutPre', ['uid' => $userid, 'username' => $username]);
    $login_ops->deauthenticate(); // unset all the cruft needed to make sure we're logged in.
    Events::SendEvent('Core', 'LogoutPost', ['uid' => $userid, 'username' => $username]);
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
    $user_ops = UserOperations::get_instance();

    class CmsLoginError extends CmsException {}

    try {
        if (!empty($usecsrf)) {
            check_secure_param('004');
        }
        if (!$password) {
            throw new CmsLoginError(lang('usernameincorrect'));
        }
        $user = $user_ops->LoadUserByUsername($username, $password, true, true);
        if (!$user) {
            throw new CmsLoginError(lang('usernameincorrect'));
        }
        if (!$user->Authenticate($password)) {
            throw new CmsLoginError(lang('usernameincorrect'));
        }
        $login_ops->save_authentication($user);
        $_SESSION[CMS_USER_KEY] = $login_ops->create_csrf_token();

        // put mention into the admin log
        audit($user->id, 'Admin Username: '.$user->username, 'Logged In');

        // send the post login event
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        Events::SendEvent('Core', 'LoginPost', ['user' => &$user]);

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
            $homepage = UserParams::get_for_user($user->id, 'homepage');
            if (!$homepage) {
                $homepage = $config['admin_url'].'/menu.php';
            }
            // quick hacks to remove old secure param name from homepage url
            // and replace with the correct one.
            $homepage = str_replace('&amp;', '&', $homepage);
            $tmp = explode('?', $homepage);
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
            $homepage = $tmp[0].'?'.implode('&amp;', $tmp3);

            // and redirect.
            $homepage = cms_html_entity_decode($homepage);
            //TODO generally support the websocket protocol 'wss' : 'ws'
            if (!startswith($homepage, 'http') && !startswith($homepage, '//') && startswith($homepage, '/')) {
                $homepage = CMS_ROOT_URL.$homepage;
            }
            redirect($homepage);
        }
    } catch (Throwable $e) {
        $error = $e->GetMessage();
        debug_buffer('Login failed.  Error is: ' . $error);
        unset($_POST['password'],$_REQUEST['password']);
        Events::SendEvent('Core', 'LoginFailed', ['user' => $_POST['username']]);
        // put mention into the admin log
        $ip_login_failed = Utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . 'Admin Username: ' . $username, 'Login Failed');
    }
}

// vars for includer's smarty

$tplvars = [];
$tplvars['actionid'] = ''; //maybe altered upstream
$tplvars['admin_url'] = $config['admin_url'];

$tplvars['encoding'] = NlsOperations::get_encoding();
$lang = NlsOperations::get_current_language();
if (($p = strpos($lang, '_')) !== false) {
    $lang = substr($lang, 0, $p);
}
$tplvars['lang_code'] = $lang;
$tplvars['lang_dir'] = NlsOperations::get_language_direction();
$sitelogo = AppParams::get('site_logo');
if ($sitelogo) {
    if (!preg_match('~^\w*:?//~', $sitelogo)) {
        $config = AppSingle::Config();
        $sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
    }
}
$tplvars['sitelogo'] = $sitelogo;

if (!empty($usecsrf)) {
    $_SESSION[$csrf_key] = $tplvars['csrf'] = Crypto::random_string(16, true);
}

if (isset($error)) {
    $tplvars['error'] = $error;
}
if (isset($warning)) {
    $tplvars['warning'] = $warning;
}
if (isset($message)) {
    $tplvars['message'] = $message;
}
if (isset($changepwhash)) {
    $tplvars['changepwhash'] = $changepwhash;
}
