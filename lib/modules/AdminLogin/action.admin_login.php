<?php
/*
AdminLogin module action - admin_login
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

use CMSMS\AdminUtils;
use CMSMS\AppSingle;
use CMSMS\Crypto;
use CMSMS\HookOperations;
use CMSMS\internal\LoginOperations;
use CMSMS\UserParams;
use CMSMS\Utils;
use AdminLogin\LoginUserError;
use Exception;
use RuntimeException;
use function audit;
use function cms_warning;
use function debug_buffer;
use function lang;
use function redirect;

if( !isset($gCms) ) exit;

class LoginUserError extends RuntimeException {}

$theme_object = Utils::get_theme_object();
$csrf_key = hash('tiger192,3', AppSingle::App()->GetSiteUUID());
$login_ops = LoginOperations::get_instance();
$username = $password = $error = $warning = $pwhash = $message = null;

if( isset( $_GET['recoverme'] ) ) { //should be hexits hash
    $token = sanitizeVal($_GET['recoverme']);
    $user = $this->getLoginUtils()->find_recovery_user($token);
    if( $user ) {
        $pwhash = $token;
    }
    else {
        $error = $this->Lang('err_usernotfound');
    }
}
else if( isset( $params['forgotpwchangeform']) ) {
    try {
        $expected_csrf_val = ( isset($_SESSION[$csrf_key]) ) ? $_SESSION[$csrf_key] : null;
        $provided_csrf_val = ( isset($params['csrf']) ) ? $params['csrf'] : null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new RuntimeException( $this->Lang('err_csrfinvalid') );
        }

        $token = $params['changepwhash']; // no pre-process cleanup in this context
        $username = $params['username'];
        $password1 = $params['password'];
        $password2 = $params['passwordagain'];
        if( !$token || !$username || !$password1 || !$password2 ) throw new LoginUserError( $this->Lang('err_missingdata') );
        if( $password1 != $password2 ) throw new LoginUserError( $this->Lang('err_passwordmismatch') );

        $user = $this->getLoginUtils()->find_recovery_user( $token );
        if( !$user || $user->username != $username ) throw new LoginUserError( $this->Lang('err_usernotfound') );

//        Events::SendEvent('Core', 'PasswordStrengthTest', $password1);
        $TODO = HookOperations::do_hook('Core::PasswordStrengthTest', ['user' => $user, 'password' => $password1]);

        //TODO P/W policy / blacklist check
        $user->SetPassword( $password1 );
        $user->Save();
        $this->getLoginUtils()->remove_reset_code( $user );

        $ip_passw_recovery = Utils::get_real_ip();
        audit('','Core','Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
        HookOperations::do_hook('Core::LostPasswordReset', [ 'uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery ] );
        $message = $this->Lang('msg_passwordchanged');
    }
    catch( LoginUserError $e ) {
        $error = $e->GetMessage();
        HookOperations::do_hook('Core::LoginFailed', [ 'user'=>$username ] );
        $ip_login_failed = Utils::get_real_ip();
        $pwhash = $token;
        cms_warning('', '(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Password Reset Failed');
    }
    catch( Throwable $t ) {
        $error = $t->GetMessage();
    }
}
else if( isset( $params['forgotpwform']) ) {
    // got the forgot password form request
    try {
        $expected_csrf_val = ( isset($_SESSION[$csrf_key]) ) ? $_SESSION[$csrf_key] : null;
        $provided_csrf_val = ( isset($params['csrf']) ) ? $params['csrf'] : null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new RuntimeException( $this->Lang('err_csrfinvalid') );
        }

        $username = $params['username'] ?? null;
        unset( $params['username'] );
        if( !$username ) throw new LoginUserError($this->Lang('err_usernotfound'));
        $username = sanitizeVal($username, 4);
        HookOperations::do_hook('Core::LostPassword', [ 'username'=>$username]);
        $userops = $gCms->GetUserOperations();
        $oneuser = $userops->LoadUserByUsername($username, null, true, true);
        if( !$oneuser ) {
            HookOperations::do_hook('Core::LoginFailed', [ 'user'=>$username] );
            throw new LoginUserError( $this->Lang('err_usernotfound') );
        }

        $this->getLoginUtils()->send_recovery_email( $oneuser );
        $warning = $this->Lang('warn_recoveryemailsent');
    }
    catch( LoginUserError $e ) {
        $error = $e->GetMessage();
        HookOperations::do_hook('Core::LoginFailed', [ 'user'=>$username]);
        $ip_login_failed = Utils::get_real_ip();
        cms_warnng('(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Password Recovery Failed');
    }
    catch( Throwable $t ) {
        $error = $t->GetMessage();
    }
}
else if( isset( $params['submit'] ) ) {
    // validate CSRF key
    try {
        $expected_csrf_val = ( isset($_SESSION[$csrf_key]) ) ? $_SESSION[$csrf_key] : null;
        $provided_csrf_val = ( isset($params['csrf']) ) ? $params['csrf'] : null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new RuntimeException( lang('csrfinvalid') );
        }

        $username = $params['username'] ?? null;
        if( $username ) {
            $username = sanitizeVal($username, 4);
        }
        if( !$username ) {
            $username = lang('nofieldgiven', lang('username')); // maybe illegal chars
        }
        $password = $params['password'] ?? null;
        //per https://pages.nist.gov/800-63-3/sp800-63b.html : P/W chars = printable ASCII | space | Unicode
        if( $password ) $password = sanitizeVal($password, 0);
        if( !$username || !$password ) throw new LoginUserError($this->Lang('err_invalidusernamepassword'));

        $userops = $gCms->GetUserOperations();
        $oneuser = $userops->LoadUserByUsername( $username, null, true, true );
        if( !$oneuser ) throw new LoginUserError($this->Lang('err_invalidusernamepassword'));
        if( !$oneuser->Authenticate($password) )  throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );

        // now we could redirect somewhere for a second stage of authenticateion.
        // but for core... we don't need to.

        // user is authenticated. log him hin.
        $login_ops->save_authentication( $oneuser );
        audit($oneuser->id, "Admin Username: ".$oneuser->username, 'Logged In');
        HookOperations::do_hook('Core::LoginPost', [ 'user'=>&$oneuser ] );

        // now redirect someplace
        $homepage = UserParams::get_for_user($oneuser->id,'homepage');
        if( !$homepage ) $homepage = $config['admin_url'];
        $homepage = html_entity_decode( $homepage );
        $homepage = AdminUtils::get_session_url( $homepage );
        redirect( $homepage );
    }
    catch( LoginUserError $e ) {
        $error = $e->GetMessage();
        HookOperations::do_hook('Core::LoginFailed', [ 'user'=>$username ] );
        $ip_login_failed = Utils::get_real_ip();
        cms_warning('(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Login Failed');
    }
    catch( Exception $e ) {
        $error = $e->GetMessage();
    }
}
else if( isset( $params['cancel'] ) ) {
    debug_buffer("Login cancelled.  Returning to login.");
    $login_ops->deauthenticate(); // just in case
    redirect( $config['admin_url'].'/menu.php', true );
}

// display the login form
$tpl = $smarty->CreateTemplate( $this->GetTemplateResource( 'admin_login.tpl' )); //, null, null, $smarty );
$tpl->assign( 'error', $error );
$tpl->assign( 'warning', $warning );
$tpl->assign( 'message', $message );
$tpl->assign( 'changepwhash', $pwhash );
$tpl->assign( 'username', $username);
$tpl->assign( 'password', $password);
//$tpl->assign( 'theme', $theme_object);
//$tpl->assign( 'theme_root', $theme_object->root_url );
$csrf = $_SESSION[$csrf_key] = Crypto::random_string(16, true);
$tpl->assign( 'csrf', $csrf );
$content = $tpl->fetch();
return $content;
