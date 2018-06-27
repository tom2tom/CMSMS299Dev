<?php
# admin login processing for inclusion by themes
# Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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

use CMSMS\HookManager;
use CMSMS\internal\LoginOperations;
use CMSMS\User;

/**
 * Send lost-password recovery email to a specified admin user
 *
 * @param object $user user data
 * @return results from the attempt to send a message.
 */
function send_recovery_email(User $user)
{
    $obj = new cms_mailer();
    $obj->IsHTML(true);
    $obj->AddAddress($user->email, cms_html_entity_decode($user->firstname . ' ' . $user->lastname));
    $obj->SetSubject(lang('lostpwemailsubject',html_entity_decode(cms_siteprefs::get('sitename','CMSMS Site'))));

    $config = cms_config::get_instance();
    $url = $config['admin_url'] . '/login.php?recoverme=' . sha1($user->username . $user->password . CMS_ROOT_PATH);
    $body = lang('lostpwemail', cms_html_entity_decode(cms_siteprefs::get('sitename','CMSMS Site')), $user->username, $url, $url);

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
        if ($hash == sha1($user->username . $user->password . CMS_ROOT_PATH)) return $user;
    }
    return null;
}

/**
 * Check csrf-token validity
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
        throw new RuntimeException (lang('error_logintoken').' ('.$id.')');
    }
}

global $csrf_key;
if (!empty($usecsrf)) {
    $csrf_key = md5(__FILE__);
}

$gCms = CmsApp::get_instance();
$login_ops = LoginOperations::get_instance();

//Redirect to the normal login screen if we hit cancel on the forgot pw one
//Otherwise, see if we have a forgotpw hit
if ((isset($_REQUEST['forgotpwform']) || isset($_REQUEST['forgotpwchangeform'])) && isset($_REQUEST['logincancel'])) {
    redirect('login.php');
} elseif (isset($_REQUEST['forgotpwform']) && isset($_REQUEST['forgottenusername'])) {
    $userops = $gCms->GetUserOperations();
    $forgot_username = filter_var($_REQUEST['forgottenusername'], FILTER_SANITIZE_STRING);
    unset($_REQUEST['forgottenusername'],$_POST['forgottenusername']);
    HookManager::do_hook('Core::LostPassword', [ 'username'=>$forgot_username]);
    $oneuser = $userops->LoadUserByUsername($forgot_username);
    unset($_REQUEST['loginsubmit'],$_POST['loginsubmit']);

    if ($oneuser != null) {
        if ($oneuser->email == '') {
            $error = lang('nopasswordforrecovery');
        } elseif (send_recovery_email($oneuser)) {
            audit('','Core','Sent lost-password email for '.$user->username);
            $warning = lang('recoveryemailsent');
        } else {
            $error = lang('errorsendingemail');
        }
    } else {
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        HookManager::do_hook('Core::LoginFailed', [ 'user'=>$forgot_username ] );
        $error = lang('usernotfound');
    }
} elseif (!empty($_REQUEST['recoverme'])) {
    if (!empty($usecsrf)) {
        try {
            check_secure_param('002');
        } catch (Exception $e) {
            die('Invalid recovery request - 002');
        }
    }
    $user = find_recovery_user(cleanVariable($_REQUEST['recoverme']));
    if ($user == null) {
        $error = lang('usernotfound');
    } else {
        $changepwhash = true;
    }
} elseif (!empty($_REQUEST['forgotpwchangeform'])) {
    if (!empty($usecsrf)) {
        try {
            check_secure_param('003');
        } catch (Exception $e) {
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
             $ip_passw_recovery = cms_utils::get_real_ip();
             audit('','Core','Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
             HookManager::do_hook('Core::LostPasswordReset', [ 'uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery ]);
             $message = lang('passwordchangedlogin');
             $changepwhash = '';
         } else {
             $error = lang('nopasswordmatch');
             $changepwhash = $_REQUEST['changepwhash'];
         }
    } else {
        $error = lang('nofieldgiven', lang('password'));
            $changepwhash = $_REQUEST['changepwhash'];
    }
}

$config = cms_config::get_instance();

if (isset($_SESSION['logout_user_now'])) {
    // this does the actual logout stuff.
    unset($_SESSION['logout_user_now']);
    debug_buffer("Logging out.  Cleaning cookies and session variables.");
    $userid = $login_ops->get_loggedin_uid();
    $username = $login_ops->get_loggedin_username();
    HookManager::do_hook('Core::LogoutPre', [ 'uid'=>$userid, 'username'=>$username ] );
    $login_ops->deauthenticate(); // unset all the cruft needed to make sure we're logged in.
    HookManager::do_hook('Core::LogoutPost', [ 'uid'=>$userid, 'username'=>$username ] );
    audit($userid, "Admin Username: ".$username, 'Logged Out');
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
            check_secure_param('004');
        }
        if( !$password ) throw new CmsLoginError(lang('usernameincorrect'));
        $oneuser = $userops->LoadUserByUsername($username, $password, TRUE, TRUE);
        if( !$oneuser ) throw new CmsLoginError(lang('usernameincorrect'));
        if( ! $oneuser->Authenticate( $password ) ) {
            throw new CmsLoginError( lang('usernameincorrect') );
        }
        $login_ops->save_authentication($oneuser);

        // put mention into the admin log
        audit($oneuser->id, "Admin Username: ".$oneuser->username, 'Logged In');

        // send the post login event
        unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
        HookManager::do_hook('Core::LoginPost', [ 'user'=>&$oneuser ]);

        // redirect outa hre somewhere
        if( isset($_SESSION['login_redirect_to']) ) {
            // we previously attempted a URL but didn't have the user key in the request.
            $url_ob = new cms_url($_SESSION['login_redirect_to']);
            unset($_SESSION['login_redirect_to']);
            $url_ob->erase_queryvar('_s_');
            $url_ob->erase_queryvar('sp_');
            $url_ob->set_queryvar(CMS_SECURE_PARAM_NAME,$_SESSION[CMS_USER_KEY]);
            $url = (string) $url_ob;
            redirect($url);
        } else {
            // find the users homepage, if any, and redirect there.
            $homepage = cms_userprefs::get_for_user($oneuser->id,'homepage');
            if( !$homepage ) {
                $homepage = $config['admin_url'];
            }
            // quick hacks to remove old secure param name from homepage url
            // and replace with the correct one.
            $homepage = str_replace('&amp;','&',$homepage);
            $tmp = explode('?',$homepage);
            @parse_str($tmp[1],$tmp2);
            if( in_array('_s_',array_keys($tmp2)) ) unset($tmp2['_s_']);
            if( in_array('sp_',array_keys($tmp2)) ) unset($tmp2['sp_']);
            $tmp2[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
            foreach( $tmp2 as $k => $v ) {
                $tmp3[] = $k.'='.$v;
            }
            $homepage = $tmp[0].'?'.implode('&amp;',$tmp3);

            // and redirect.
            $homepage = cms_html_entity_decode($homepage);
            if( !startswith($homepage,'http') && !startswith($homepage,'//') && startswith($homepage,'/') ) $homepage = CMS_ROOT_URL.$homepage;
            redirect($homepage);
        }
    } catch (Exception $e) {
        $error = $e->GetMessage();
        debug_buffer("Login failed.  Error is: " . $error);
        unset($_POST['password'],$_REQUEST['password']);
        HookManager::do_hook('Core::LoginFailed', [ 'user'=>$_POST['username'] ] );
        // put mention into the admin log
        $ip_login_failed = cms_utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Login Failed');
    }
}

// vars for includer's smarty

$params = [];
$params['actionid'] = ''; //maybe altered upstream
$params['admin_url'] = $config['admin_url'];

$params['encoding'] = CmsNlsOperations::get_encoding();
$lang = CmsNlsOperations::get_current_language();
if (($p = strpos($lang,'_')) !== false) {
    $lang = substr($lang,0,$p);
}
$params['lang_code'] = $lang;
$params['lang_dir'] = CmsNlsOperations::get_language_direction();
$sitelogo = cms_siteprefs::get('sitelogo');
if ($sitelogo) {
    if (!preg_match('~^\w*:?//~',$sitelogo)) {
        $sitelogo = CMS_ROOT_URL.'/'.trim($sitelogo, ' /');
    }
}
$params['sitelogo'] = $sitelogo;

if (!empty($usecsrf)) {
    $_SESSION[$csrf_key] = $params['csrf'] = bin2hex(random_bytes(16));
}

if (isset($error)) $params['error'] = $error;
if (isset($warning)) $params['warning'] = $warning;
if (isset($message)) $params['message'] = $message;
if (isset($changepwhash)) $params['changepwhash'] = $changepwhash;

