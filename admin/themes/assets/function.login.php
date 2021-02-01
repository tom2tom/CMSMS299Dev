<?php
/*
admin login processing for inclusion by themes
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Events;
use CMSMailer\Mailer; //TODO if no CMSMailer present, revert to mail()
use CMSMS\NlsOperations;
use CMSMS\Url;
use CMSMS\User;
use CMSMS\UserParams;
use CMSMS\Utils;

// This expects optional variable $usecsrf to be populated before inclusion

global $csrf_key;

$config = AppSingle::Config();
$login_ops = AppSingle::LoginOperations();
$userops = AppSingle::UserOperations();

/**
 * Send lost-password recovery email to a specified admin user
 *
 * @param object $user user data
 * @return results from the attempt to send a message.
 */
function send_recovery_email(User $user)
{
    global $config, $login_ops;

    $obj = new Mailer(); // TODO use generic method in case mailer N/A
    $obj->IsHTML(true);
    $obj->AddAddress($user->email, $user->firstname . ' ' . $user->lastname);
    $name = AppParams::get('sitename', 'CMSMS Site');
    $obj->SetSubject(lang('lostpwemailsubject', $name));

    $salt = $login_ops->get_salt();
    $url = $config['admin_url'] . '/login.php?recoverme=' . hash('sha3-224', $user->username . $salt . $user->password);
    $body = lang('lostpwemail', $name, $user->username, $url);

    $obj->SetBody($body);
    return $obj->Send();
}

/**
 * Find the user corresponding to the given identity-token
 *
 * @param string $hash the token
 * @return mixed The matching User object if found, or null otherwise.
 */
function find_recovery_user(string $hash)
{
    global $login_ops, $userops;

    $salt = $login_ops->get_salt();
    foreach ($userops->LoadUsers() as $user) {
        if ($hash == hash('sha3-224', $user->username . $salt . $user->password )) {
            return $user;
        }
    }
}

/**
 * Check secure-key (aka CSRF-token) validity
 * $param string $id location-identifier used in exception message
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

function check_passwords()
{
}

//Redirect to the normal login form if the user cancels on the forgot p/w form
if ((isset($_REQUEST['forgotpwform']) || isset($_REQUEST['forgotpwchangeform'])) && isset($_REQUEST['logincancel'])) {
    //TODO prevent 'unknown user error
    redirect('login.php');
}

if (!empty($usecsrf)) {
    $salt = $login_ops->get_salt();
    $csrf_key = hash('tiger128,3', $salt); // 32 hexits
}

// other parameters for includer's smarty/template
$tplvars = [];

//Check for a forgot-pw job
if (isset($_REQUEST['forgotpwform']) && isset($_REQUEST['forgottenusername'])) {
    $forgot_username = $_REQUEST['forgottenusername']; //might be empty
    unset($_REQUEST['forgottenusername'], $_POST['forgottenusername']);
    if ($forgot_username) {
        $tmp = sanitizeVal($forgot_username, 4);
        Events::SendEvent('Core', 'LostPassword', ['username' => $tmp]);
        $user = $userops->GetRecoveryData($forgot_username);
        unset($_REQUEST['loginsubmit'], $_POST['loginsubmit']);

        if ($user != null) {
            if (!$user->email) {
                $errmessage = lang('nopasswordforrecovery');
            } elseif (send_recovery_email($user)) {
                audit('', 'Core', 'Sent lost-password email for '.$user->username);
                $infomessage = lang('recoveryemailsent');
            } else {
                $errmessage = lang('errorsendingemail');
            }
        } else {
            unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
            Events::SendEvent('Core', 'LoginFailed', ['user' => $tmp]);
            $errmessage = lang('usernotfound');
        }
    }
    return;
} elseif (!empty($_REQUEST['recoverme'])) { //should be a hexits hash
    $changepwhash = sanitizeVal($_REQUEST['recoverme']);
    $user = find_recovery_user($changepwhash);
    if ($user != null) {
        $changepwtoken = true;
    } else {
        $errmessage = lang('usernotfound');
    }
    return;
} elseif (!empty($_REQUEST['forgotpwchangeform'])) {
    if (!empty($usecsrf)) {
        try {
            check_secure_param('003');
            $usecsrf = false; //another check not necessary or possible
        } catch (Throwable $t) {
            die('Invalid recovery request - 003');
        }
    }
    $changepwhash = sanitizeVal($_REQUEST['changepwhash']); //should be a hexits hash
    $user = find_recovery_user($changepwhash);
    if ($user == null) {
        $errmessage = lang('usernotfound');
    } elseif (isset($_REQUEST['password'])) {
//        $tmp = cms_specialchars_decode($_REQUEST['password']);
        $tmp = $_REQUEST['password'];
        $password = sanitizeVal($tmp, 0);
        if ($password != $tmp) {
            $errmessage = lang('illegalcharacters', lang('password'));
            return;
        } elseif (!$password) {
            $errmessage = lang('nofieldgiven', lang('password'));
            return;
        }
//        $tmp = cms_specialchars_decode($_REQUEST['passwordagain']);
        $tmp = $_REQUEST['passwordagain'];
        $again = sanitizeVal($tmp, 0);
        if ($password == $again) {
            if ($userops->PasswordCheck($user->id, $password)) {
                $user->Save();
                // put mention into the admin log
                $ip_passw_recovery = Utils::get_real_ip();
                audit('', 'Core', 'Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
                Events::SendEvent('Core', 'LostPasswordReset', ['uid' => $user->id, 'username' => $user->username, 'ip' => $ip_passw_recovery]);
                $infomessage = lang('passwordchangedlogin');
                $changepwhash = '';
            } else {
               //TODO some feedback from checker
                $errmessage = lang('error_passwordinvalid');
                return;
            }
        } else {
            $errmessage = lang('nopasswordmatch');
            return;
        }
    }
} elseif (!empty($_REQUEST['renewpwform'])) {
    if (!isset($_POST['cancel'])) {
        $username = $_POST['username'];
        //TODO sanitize as per isset($_REQUEST['password']), above
        //TODO prevent repeated expiry
        if (isset($_REQUEST['password'])) {
        }
        if (isset($_REQUEST['passwordagain'])) {
        }
        if ($password == $again) {
            if ($userops->PasswordCheck($user->id, $password)) {
                //TODO
            } else {
                //TODO
            }
        }
    }
    //slide through to 'submit|cancel' processing
}

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
    $username = $_POST['username'] ?? null; //no pre-evaluation cleanup - if wrong, too bad
    $password = $_POST['password'] ?? null; //ditto

    try {
        if (!empty($usecsrf)) {
            check_secure_param('004');
        }
        if (!$password) {
            throw new Exception(lang('usernameincorrect'));
        }
        $user = $userops->LoadUserByUsername($username, $password, true, true);
        if (!$user) {
            throw new Exception(lang('usernameincorrect'));
        }
        if (!$user->Authenticate($password)) {
            throw new Exception(lang('usernameincorrect'));
        }
        if (!$userops->PasswordExpired($user)) {
            $login_ops->save_authentication($user);
            $_SESSION[CMS_USER_KEY] = $login_ops->create_csrf_token();

            // put mention into the admin log
            if (isset($_REQUEST['renewpwform'])) {
                audit($user->id, 'Admin Username: '.$user->username, 'Password Renewed');
                unset($_POST['renewpwform']);
            } else {
                audit($user->id, 'Admin Username: '.$user->username, 'Logged In');
            }

            // send the post-login event
            unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
            Events::SendEvent('Core', 'LoginPost', ['user' => &$user]);

            // redirect outa here somewhere
            if (!empty($_SESSION['login_redirect_to'])) {
                // we previously attempted a URL but didn't have the user key in the request.
                $url = $_SESSION['login_redirect_to'];
                unset($_SESSION['login_redirect_to']);
                $url .= get_secure_param();
                redirect($url);
            } else {
                // find the user's homepage, if any, and redirect there.
                $homepage = UserParams::get_for_user($user->id, 'homepage');
                if (!$homepage) {
                    $homepage = $config['admin_url'].'/menu.php';
                } elseif (!startswith($homepage, 'http') && !startswith($homepage, '//') && startswith($homepage, '/')) {
                   //TODO generally support the websocket protocol 'wss' : 'ws'
                    $homepage = CMS_ROOT_URL.$homepage;
                }
                $homepage .= get_secure_param();
                redirect($homepage);
            }
        } else { // expired P/W
            // initiate renewal
            $tplvars += [
            'renewpw' => true,
            'username' => $username,
            ];
        }
    } catch (Throwable $t) {
        $errmessage = $t->GetMessage();
        debug_buffer('Login failed.  Error is: ' . $errmessage );
        unset($_POST['password'],$_REQUEST['password']);
        $username = $_REQUEST['username'] ?? $_REQUEST['forgottenusername'] ?? '';
        if ($username) {
            $username = sanitizeVal($username,4);
        } else {
            $username = lang('nofieldgiven',lang('username')); // invalid chars?
        }
        Events::SendEvent('Core', 'LoginFailed', ['user' => $username]);
        // put mention into the admin log
        $ip_login_failed = Utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . 'Admin Username: ' . $username, 'Login Failed');
    }
    unset($_REQUEST['forgottenusername'],$_POST['forgottenusername']);
}

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
        $sitelogo = $config['image_uploads_url'].'/'.trim($sitelogo, ' /');
    }
}
$tplvars['sitelogo'] = $sitelogo;

if (!empty($usecsrf)) {
    $_SESSION[$csrf_key] = $tplvars['csrf'] = $login_ops->create_csrf_token();
}

if (isset($errmessage)) {
    $tplvars['error'] = $errmessage ;
}
if (isset($warnmessage)) {
    $tplvars['warning'] = $warnmessage ;
}
if (isset($infomessage)) {
    $tplvars['message'] = $infomessage ;
}
if (isset($changepwhash)) {
    $tplvars['changepwhash'] = $changepwhash;
}
// DEBUG $tplvars['renewpw'] = true;
//$tplvars['username'] = 'blue elephant';
