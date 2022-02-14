<?php
/*
admin-login module inclusion - does $_POST processing and provides related methods
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Events;
use CMSMS\SingleItem;
use CMSMS\User;
use CMSMS\UserParams;
use CMSMS\Utils;
use function CMSMS\log_info;
use function CMSMS\log_notice;
use function CMSMS\sanitizeVal;

/*
 * This method may be included in the related light-module action file,
 * or indirectly by a theme-related mechanism, via method.fetchpanel.php.
 * It expects some variables to be populated before inclusion:
 * $config
 * $id (possibly '')
 * $login_url e.g. $config['admin_url'].'/login.php' OR module-action url
 */

$login_ops = SingleItem::LoginOperations();
$salt = $login_ops->get_salt();
$usecsrf = true;
$csrf_key = hash('tiger128,3', $salt); // 32 hexits
$userops = SingleItem::UserOperations();
$infomessage = $warnmessage = $errmessage = $changepwhash = '';

/**
 * Find the user corresponding to the given identity-token
 *
 * @param string $hash the token
 * @return mixed The matching User object if found, or null otherwise.
 */
$find_recovery_user = function(string $hash) use ($salt, $userops)
{
    foreach ($userops->LoadUsers() as $user) {
        if ($hash == hash_hmac('tiger128,3', $user->password, $salt ^ $user->username)) { // no compare-timing risk
            return $user;
        }
    }
};

/**
 * Check secure-key (aka CSRF-token) validity
 *
 * $param string $id location-identifier used in exception message
 * @param object $mod current module-object
 * @throws RuntimeException upon invalid token
 */
$check_secure_param = function(string $clue, AdminLogin $mod) use ($id, $csrf_key)
{
    $expected = $_SESSION[$csrf_key] ?? null;
    $provided = $_POST[$id.'csrf'] ?? null; //comparison value, no sanitize needed (unless $_SESSION hacked!)
    unset($_SESSION[$csrf_key], $_POST[$id.'csrf']);
    if (!$expected || !$provided || $expected != $provided) {
        throw new RuntimeException($mod->Lang('error_csrf').' ('.$clue.')');
    }
};

/**
 * Evaluate passwords' probity (validity, quality, ....)
 *
 * @param object $user user object
 * @param object $mod current module-object
 * $return bool indicating all ok
 */
$check_passwords = function(User $user, AdminLogin $mod) use ($id, $errmessage, $userops) : bool
{
    $tmp = $_REQUEST[$id.'password'];
    $password = sanitizeVal($tmp, CMSSAN_NONPRINT);
    if ($password != $tmp) {
        $errmessage = $mod->Lang('error_badchars', $mod->Lang('password')); // OR Lang('error_badfield'), Lang('password'));
        return false;
    } elseif (!$password) {
        $errmessage = $mod->Lang('error_nofield', $mod->Lang('password'));
        return false;
    }
    $tmp = $_REQUEST[$id.'passwordagain'];
    $again = sanitizeVal($tmp, CMSSAN_NONPRINT);
    if ($password == $again) {
        if ($user->pwreset) {
            if (password_verify($password, $user->password)) {
                $errmessage = $mod->Lang('error_passwordsame');
                return false;
            }
        }
        if (!$userops->PasswordCheck($user->id, $password)) {
            $errmessage = $mod->Lang('error_passwordinvalid');
            return false;
        }
    } else {
        $errmessage = $mod->Lang('error_nomatch');
        return false;
    }
    return true;
};

// redirect to the normal login form if the user cancels on the forgot p/w form
if ((isset($_REQUEST[$id.'lostpwform']) || isset($_REQUEST[$id.'renewpwform'])) && isset($_REQUEST[$id.'cancel'])) {
    redirect($login_url.get_secure_param($login_url));
}

// other parameters for use in the template
$tplvars = [];

if (isset($_SESSION[$id.'logout_user_now'])) {
    unset($_SESSION[$id.'logout_user_now']);
    // do generic logout stuff
    debug_buffer('Logging out.  Cleaning cookies and session variables.');
    $userid = $login_ops->get_loggedin_uid();
    $username = $login_ops->get_loggedin_username();
    Events::SendEvent('Core', 'LogoutPre', ['uid'=>$userid, 'username'=>$username]);
    $login_ops->deauthenticate(); // unset all the cruft needed to make sure we're logged in
    Events::SendEvent('Core', 'LogoutPost', ['uid'=>$userid, 'username'=>$username]);
    log_info($userid, 'Admin User '.$username, 'Logged Out');
    // do any module-specific logout stuff here
    // slide through to 'submit' processing
} elseif (isset($_GET[$id.'repass'])) {
    $hash = sanitizeVal($_GET[$id.'repass']); //should be a hexits hash
    $user = $find_recovery_user($hash);
    if ($user) {
        $changepwhash = $hash;
//      $changepwtoken = true;
    } else {
        $errmessage = $this->Lang('error_nouser');
    }
    return;
} elseif (isset($_GET[$id.'onepass'])) {
    $hash = sanitizeVal($_REQUEST[$id.'onepass']); //should be a hexits hash
    $user = $find_recovery_user($hash);
    if ($user) {
        $changepwhash = $hash;
//      $changepwtoken = true;
        // initiate renewal
        $tplvars += [
            'renewpw' => true,
            'username' => $user->username,
        ];
    } else {
        $errmessage = $this->Lang('error_nouser');
    }
    return;
} elseif (isset($_REQUEST[$id.'lostpwform'])) {
    // submitted a form having smarty.get.forgotpw i.e. after processing repass
    $forgot_username = $_REQUEST[$id.'forgottenusername']; //might be empty
    unset($_REQUEST[$id.'forgottenusername'], $_POST[$id.'forgottenusername']);
    if ($forgot_username) {
        $tmp = sanitizeVal($forgot_username, CMSSAN_PURESPC); // OR ,CMSSAN_ACCOUNT OR ,CMSSAN_PURE if no spaces allowed (2.99 breaking change)
        Events::SendEvent('Core', 'LostPassword', ['username' => $tmp]);
        $user = $userops->GetRecoveryData($forgot_username);
        unset($_REQUEST[$id.'loginsubmit'], $_POST[$id.'loginsubmit']);

        if ($user != null) {
            if (!$user->email) {
                $errmessage = $this->Lang('norecoveryaddress');
            } elseif ($userops->Send_recovery_email($user)) {
                log_notice('', 'Sent lost-password email for '.$user->username);
                $infomessage = $this->Lang('recoveryemailsent');
            } else {
                $errmessage = $this->Lang('error_sendemail');
            }
        } else {
            unset($_POST[$id.'username'],$_POST[$id.'password'],$_REQUEST[$id.'username'],$_REQUEST[$id.'password']);
            Events::SendEvent('Core', 'LoginFailed', ['user'=>$tmp]);
            $errmessage = $this->Lang('error_nouser');
        }
    }
    return;
} elseif (!empty($_REQUEST[$id.'renewpwform'])) {
    // submitted a form having $renewpw i.e. after processing onepass
    if ($usecsrf) {
        try {
            $check_secure_param('003', $this); // careful about $this
            $usecsrf = false; //another check not necessary or possible
        } catch (Throwable $t) {
            exit('Invalid reset request - 003');
        }
    }
    $username = sanitizeVal($_POST[$id.'username'], CMSSAN_ACCOUNT);
    $user = $userops->GetRecoveryData($username);
    if ($user) {
        if ($check_passwords($user, $this)) {
            $tmp = $_REQUEST[$id.'password'];
            $password = sanitizeVal($tmp, CMSSAN_NONPRINT);
            $user->password = $userops->PreparePassword($password);
            $user->newpass = true;
            $user->pwreset = 0;
            $user->Save();
            //TODO clear any other table data which triggered reset
/*              // put mention into the admin log
            $ip_passw_recovery = Utils::get_real_ip();
            log_notice('', 'Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
            Events::SendEvent('Core', 'LostPasswordReset', ['uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery]);
            $infomessage = $mod->Lang('passwordchangedlogin');
*/
            $login_ops->save_authentication($user);
            // find the user's homepage, if any
            $url = UserParams::get_for_user($user->id, 'homepage');
            if (!$url) {
                $url = $config['admin_url'].'/menu.php';
            } elseif (!startswith($url, 'http') && !startswith($url, '//') && startswith($url, '/')) {
                //TODO generally support the websocket protocol 'wss' : 'ws'
                $url = CMS_ROOT_URL.$url;
            }
            $url .= get_secure_param($url);
            redirect($url);
        } else {
            // rerun as for isset($_GET[$id.'onepass'])
            $changepwhash = sanitizeVal($_REQUEST[$id.'changepwhash']); //should be a hexits hash
            $tplvars += [
                'renewpw' => true,
                'username' => $user->username,
            ];
            $errmessage = $this->Lang('error_passwordinvalid');
            return;
        }
    } else {
        $errmessage = $this->Lang('error_nouser');
    }
   // slide into normal login processing
}
/* TODO reconcile with above
      elseif (!empty($_REQUEST[$id.'renewpwform'])) {
    // submitted the form without $renewpw i.e. after processing repass
    if ($usecsrf) {
        try {
            $check_secure_param('004', $this); // careful about $this
            $usecsrf = false; //another check not necessary or possible
        } catch (Throwable $t) {
            exit('Invalid recovery request - 004');
        }
    }
    $hash = sanitizeVal($_REQUEST[$id.'changepwhash']); //should be a hexits hash
    $user = $find_recovery_user($hash);
    if ($user) {
        if (isset($_REQUEST[$id.'password'])) {
            if (!$check_passwords($user, $this)) {
                return; // abort
            }
            $tmp = $_REQUEST[$id.'password'];
            $password = sanitizeVal($tmp, CMSSAN_NONPRINT);
            $user->password = $userops->PreparePassword($password);
            $user->newpass = true;
            $user->pwreset = 0;
            $user->Save();
/*          // put mention into the admin log
            $ip_passw_recovery = Utils::get_real_ip();
            log_notice('', 'Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
            Events::SendEvent('Core', 'LostPasswordReset', ['uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery]);
            $infomessage = $mod->Lang('passwordchangedlogin');
* /
            // slide into normal login processing
        }
    } else {
        $errmessage = $this->Lang('error_nouser');
    }
}
*/

if (isset($_POST[$id.'cancel'])) {
    redirect($login_url.get_secure_param($login_url));
} elseif (isset($_POST[$id.'submit'])) {
    // 'initial' login form submitted
    $login_ops->deauthenticate();
    $username = $_POST[$id.'username'] ?? null; //no pre-evaluation cleanup - if wrong, too bad
    $password = $_POST[$id.'password'] ?? null; //ditto TODO wrong after reset !!!

    try {
        if ($usecsrf) {
            $check_secure_param('004', $this);
        }
        if (!$password) {
            throw new Exception($this->Lang('error_invalid')); // generic feedback only!
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
            if (isset($_REQUEST[$id.'renewpwform'])) { // after a P/W recovery or refresh
                log_info($user->id, 'Admin User '.$user->username, 'Password Renewed');
                unset($_POST[$id.'renewpwform']);
            } else {
                log_info($user->id, 'Admin User '.$user->username, 'Logged In');
            }

            // send the post-login event
            unset($_POST[$id.'username'],$_POST[$id.'password'],$_REQUEST[$id.'username'],$_REQUEST[$id.'password']);
            Events::SendEvent('Core', 'LoginPost', ['user'=>&$user]);

            // redirect outa here somewhere
            if (!empty($_SESSION['login_redirect_to'])) {
                // we previously attempted a URL but didn't have the user key in the request.
                $url = $_SESSION['login_redirect_to'];
                unset($_SESSION['login_redirect_to']);
            } else {
                // find the user's homepage, if any
                $url = UserParams::get_for_user($user->id, 'homepage');
                if (!$url) {
                    $url = $config['admin_url'].'/menu.php';
                } elseif (startswith($url, 'lib/moduleinterface.php')) {
                    $url = CMS_ROOT_URL.'/'.$url;
                } elseif (startswith($url, '/') && !startswith($url, '//')) {
                    $url = CMS_ROOT_URL.$url;
                }
            }
            $url .= get_secure_param($url);
            redirect($url);
        } else { // P/W no-longer valid
            // initiate renewal
            $tplvars += [
            'renewpw' => true,
            'username' => $username,
            ];
        }
    } catch (Throwable $t) {
        $errmessage = $t->GetMessage();
        debug_buffer('Login failed.  Error is: ' . $errmessage);
        unset($_POST[$id.'password'],$_REQUEST[$id.'password']);
        $username = $_REQUEST[$id.'username'] ?? $_REQUEST[$id.'forgottenusername'] ?? '';
        if ($username) {
            $username = sanitizeVal($username, CMSSAN_ACCOUNT);
        }
        if (!$username) {
            $username = $this->Lang('error_nofield', $this->Lang('username')); // unlikely all illegal chars
        }
        Events::SendEvent('Core', 'LoginFailed', ['user'=>$username]);
        // put mention into the admin log
        $ip_login_failed = Utils::get_real_ip();
        log_notice('(IP: ' . $ip_login_failed . ') ' . 'Admin User ' . $username, 'Login failed');
    }
    unset($_REQUEST[$id.'forgottenusername'],$_POST[$id.'forgottenusername']);
}
