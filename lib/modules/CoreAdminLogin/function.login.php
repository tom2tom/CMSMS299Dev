<?php
/*
admin-login module inclusion - does $_POST processing and provides related methods
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\Events;
use CMSMS\Mailer;
use CMSMS\User;
use CMSMS\UserParams;
use CMSMS\Utils;

/*
 * This expects some variables to be populated before inclusion:
 * $config
 * $usecsrf optional
 */

global $csrf_key;

$login_ops = AppSingle::LoginOperations();
$userops = AppSingle::UserOperations();

/**
 * Send lost-password recovery email to a specified admin user
 *
 * @param object $user user data
 * @param object $mod current module-object
 * @return results from the attempt to send a message.
 */
function send_recovery_email(User $user, $mod)
{
    global $config, $login_ops;

    $obj = new Mailer();
    $obj->IsHTML(true);
    $obj->AddAddress($user->email, cms_html_entity_decode($user->firstname . ' ' . $user->lastname));
    $name = html_entity_decode(AppParams::get('sitename', 'CMSMS Site')); // OR cms_ variant ?
    $obj->SetSubject($mod->Lang('lostpwemailsubject', $name));

    $salt = $login_ops->get_salt();
    $url = $config['admin_url'] . '/login.php?recoverme=' . hash('sha3-224', $user->username . $salt . $user->password);
    $body = $mod->Lang('lostpwemail', $name, $user->username, $url);

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
        if ($hash == hash('sha3-224', $user->username . $salt . $user->password)) {
            return $user;
        }
    }
}

/**
 * Check secure-key (aka CSRF-token) validity
 * $param string $id location-identifier used in exception message
 * @param object $mod current module-object
 * @throws RuntimeException upon invalid token
 */
function check_secure_param(string $id, $mod)
{
    global $csrf_key;

    $expected = $_SESSION[$csrf_key] ?? null;
    $provided = $_POST['csrf'] ?? null; //comparison value, no sanitize needed (unless $_SESSION hacked!)
    unset($_SESSION[$csrf_key], $_POST['csrf']);
    if (!$expected || !$provided || $expected != $provided) {
        throw new RuntimeException($mod->Lang('error_logintoken').' ('.$id.')');
    }
}

/**
 *
 */
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

// other parameters for use in the template
$tplvars = [];

//Check for a forgot-pw job
if (isset($_REQUEST['forgotpwform']) && isset($_REQUEST['forgottenusername'])) {
    $forgot_username = $_REQUEST['forgottenusername']; //might be empty
    unset($_REQUEST['forgottenusername'], $_POST['forgottenusername']);
    if ($forgot_username) {
        $tmp = cleanString($forgot_username, 2); // sanitize for internal use
        Events::SendEvent('Core', 'LostPassword', ['username' => $tmp]);
        $user = $userops->GetRecoveryData($forgot_username);
        unset($_REQUEST['loginsubmit'], $_POST['loginsubmit']);

        if ($user != null) {
            if (!$user->email) {
                $errmessage = $this->Lang('nopasswordforrecovery');
            } elseif (send_recovery_email($user, $this)) {
                audit('', 'Core', 'Sent lost-password email for '.$user->username);
                $infomessage = $this->Lang('recoveryemailsent');
            } else {
                $errmessage = $this->Lang('error_sendemail');
            }
        } else {
            unset($_POST['username'],$_POST['password'],$_REQUEST['username'],$_REQUEST['password']);
            Events::SendEvent('Core', 'LoginFailed', ['user'=>$tmp]);
            $errmessage = $this->Lang('error_nouser');
        }
    }
    return;
} elseif (!empty($_REQUEST['recoverme'])) { //should be a hexits hash
    $user = find_recovery_user($_REQUEST['recoverme']);
    if ($user != null) {
        $changepwtoken = true;
        $changepwhash = cleanString($_REQUEST['recoverme'], 2);
    } else {
        $errmessage = $this->Lang('error_nouser');
    }
    return;
} elseif (!empty($_REQUEST['forgotpwchangeform'])) {
    if (!empty($usecsrf)) {
        try {
            check_secure_param('003', $this);
            $usecsrf = false; //another check not necessary or possible
        } catch (Throwable $t) {
            die('Invalid recovery request - 003');
        }
    }
    $user = find_recovery_user($_REQUEST['changepwhash']); //should be a hexits hash
    if ($user == null) {
        $errmessage = $this->Lang('error_nouser');
    } elseif (isset($_REQUEST['password'])) {
        //TODO migrate to check_passwords()
        $tmp = cms_html_entity_decode($_REQUEST['password']);
        $password = cleanString($tmp, 0);
        if ($password != $tmp) {
            $errmsg = $this->Lang('illegalcharacters', $this->Lang('password')); // OR lang('badfield', lang('password'));
            $changepwhash = cleanString($_REQUEST['changepwhash'], 2);
            return;
        } elseif (!$password) {
            $errmessage = $this->Lang('error_badfield', $this->Lang('password'));
            $changepwhash = cleanString($_REQUEST['changepwhash'], 2);
            return;
        }
        $tmp = cms_html_entity_decode($_REQUEST['passwordagain']);
        $again = cleanString($tmp, 0);
        if ($password == $again) {
            if ($userops->PasswordCheck($user->id, $password)) {
                $user->Save();
                // put mention into the admin log
                $ip_passw_recovery = Utils::get_real_ip();
                audit('', 'Core', 'Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
                Events::SendEvent('Core', 'LostPasswordReset', ['uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery]);
                $infomessage = $this->Lang('passwordchangedlogin');
                $changepwhash = '';
            } else {
               //TODO some feedback from checker
                $errmessage = $this->Lang('error_passwordinvalid');
                $changepwhash = cleanString($_REQUEST['changepwhash'], 2);
                return;
            }
        } else {
            $errmessage = $this->Lang('error_nomatch');
            $changepwhash = cleanString($_REQUEST['changepwhash'], 2);
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
    $username = $_POST['username'] ?? null; //no pre-evaluation cleanup - if wrong, too bad
    $password = $_POST['password'] ?? null; //ditto

    try {
        if (!empty($usecsrf)) {
            check_secure_param('004', $this);
        }
        if (!$password) {
            throw new Exception($this->Lang('error_invalid'));
        }
        $user = $userops->LoadUserByUsername($username, $password, true, true);
        if (!$user) {
            throw new Exception($this->Lang('error_invalid'));
        }
        if (!$user->Authenticate($password)) {
            throw new Exception($this->Lang('error_invalid'));
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
            Events::SendEvent('Core', 'LoginPost', ['user'=>&$user]);

            // redirect outa here somewhere
            if (!empty($_SESSION['login_redirect_to'])) {
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
                $homepage = $tmp[0].'?'.implode('&', $tmp3);

                // and redirect
                $homepage = cms_html_entity_decode($homepage);
                //TODO generally support the websocket protocol 'wss' : 'ws'
                if (!startswith($homepage, 'http') && !startswith($homepage, '//') && startswith($homepage, '/')) {
                    $homepage = CMS_ROOT_URL.$homepage;
                }
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
        debug_buffer('Login failed.  Error is: ' . $errmessage);
        unset($_POST['password'],$_REQUEST['password']);
        $username = $_REQUEST['username'] ?? $_REQUEST['forgottenusername'] ?? '';
        if ($username) {
            $username = cleanString($username, 2); // sanitize for internal use
        } else {
            $username = '<Missing username>';
        }
        Events::SendEvent('Core', 'LoginFailed', ['user'=>$username]);
        // put mention into the admin log
        $ip_login_failed = Utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . 'Admin Username: ' . $username, 'Login Failed');
    }
    unset($_REQUEST['forgottenusername'],$_POST['forgottenusername']);
}
