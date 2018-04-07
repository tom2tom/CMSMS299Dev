<?php
#action: admin login
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#BUT WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CoreAdminLogin;

if( !isset($gCms) ) exit;

class LoginUserError extends \RuntimeException {}

$csrf_key = md5(__FILE__);
$username = $password = $pwhash = $error = $warning = $message = null;

// all $params[] members have been appropriately cleaned upstream

if( isset( $params['recoverme'] ) ) {
    $usercode = trim($params['recoverme']);
    $user = $this->getLoginUtils()->find_recovery_user( $usercode );
    if( $user ) {
        $pwhash = $usercode;
    } else {
        $error = $this->Lang('err_usernotfound');
    }
}
elseif( isset( $params['forgotpwchangeform']) ) {
    try {
        $expected_csrf_val = $_SESSION[$csrf_key] ?? null;
        $provided_csrf_val = $params['csrf'] ?? null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new \RuntimeException( $this->Lang('err_csrfinvalid') );
        }
        $usercode = trim($params['changepwhash']);
        $username = html_entity_decode( trim($params['username'] ));
        $password1 = $params['password'];
        $password2 = $params['passwordagain'];
        if( !$usercode || !$username || !$password1 || !$password2 ) throw new LoginUserError( $this->Lang('err_missingdata') );
        if( $password1 != $password2 ) throw new LoginUserError( $this->Lang('err_passwordmismatch') );
        \CMSMS\HookManager::do_hook('Core::PasswordStrengthTest', $password1 );

        $user = $this->getLoginUtils()->find_recovery_user( $usercode );
        if( !$user || $user->username != $username ) throw new LoginUserError( $this->Lang('err_usernotfound') );

        $user->SetPassword( $password1 );
        $user->Save();
        $this->getLoginUtils()->remove_reset_code( $user );

        $ip_passw_recovery = \cms_utils::get_real_ip();
        audit('','Core','Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
        \CMSMS\HookManager::do_hook('Core::LostPasswordReset', [ 'uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery ] );
        $message = $this->Lang('msg_passwordchanged');
    }
    catch( LoginUserError $e ) {
        $error = $e->GetMessage();
        \CMSMS\HookManager::do_hook('Core::LoginFailed', [ 'user'=>$username ] );
        $ip_login_failed = \cms_utils::get_real_ip();
        $pwhash = $usercode;
        audit('', '(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Password reset failed');
    }
    catch( \Exception $e ) {
        $error = $e->GetMessage();
    }
}
elseif( isset( $params['forgotpwform']) ) {
    // got the forgot password form request
    try {
        $expected_csrf_val = $_SESSION[$csrf_key] ?? null;
        $provided_csrf_val = $params['csrf'] ?? null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new \RuntimeException( $this->Lang('err_csrfinvalid') );
        }

        $username = (isset($params['username'])) ? html_entity_decode(trim($params['username'])) : null;
        if( !$username ) throw new LoginUserError( $this->Lang('err_usernotfound') );
        unset( $params['username'] );

        \CMSMS\HookManager::do_hook('Core::LostPassword', [ 'username'=>$username] );
        $userops = $gCms->GetUserOperations();
        $oneuser = $userops->LoadUserByUsername($username, null, true, true );
        if( !$oneuser ) {
            \CMSMS\HookManager::do_hook('Core::LoginFailed', [ 'user'=>$username ] );
            throw new LoginUserError( $this->Lang('err_usernotfound') );
        }

        $this->getLoginUtils()->send_recovery_email( $oneuser );
        $warning = $this->Lang('warn_recoveryemailsent');
    }
    catch( LoginUserError $e ) {
        $error = $e->GetMessage();
        \CMSMS\HookManager::do_hook('Core::LoginFailed', [ 'user'=>$username ] );
        $ip_login_failed = \cms_utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Login Failed');
    }
    catch( \Exception $e ) {
        $error = $e->GetMessage();
    }
}
elseif( isset( $params['submit'] ) ) {
    try {
        // validate CSRF key
        $expected_csrf_val = $_SESSION[$csrf_key] ?? null;
        $provided_csrf_val = $params['csrf'] ?? null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new \RuntimeException( lang('csrfinvalid') );
        }
        $username = (isset($params['username'])) ? html_entity_decode(trim($params['username'])) : null;
        $password = $params['password'] ?? null;
        if( !$username || !$password ) throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );

        $userops = $gCms->GetUserOperations();
        $oneuser = $userops->LoadUserByUsername( $username, null, true, true );
        if( !$oneuser ) throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );
        if( !$oneuser->Authenticate( $password ) )  throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );

        // now we could redirect somewhere for a second stage of authentication.
        // but for core... we don't need to.

        // user is authenticated
        \CMSMS\LoginOperations::get_instance()->save_authentication( $oneuser );
        audit($oneuser->id, "Admin Username: ".$oneuser->username, 'Logged In');
        \CMSMS\HookManager::do_hook('Core::LoginPost', [ 'user'=>&$oneuser ] );

        // now redirect someplace
        $homepage = \cms_userprefs::get_for_user($oneuser->id,'homepage');
        if( !$homepage ) $homepage = $config['admin_url'];
        $homepage = html_entity_decode( $homepage );
        $homepage = \CmsAdminUtils::get_session_url( $homepage );
        redirect( $homepage );
    }
    catch( LoginUserError $e ) {
        $error = $e->GetMessage();
        \CMSMS\HookManager::do_hook('Core::LoginFailed', [ 'user'=>$username ] );
        $ip_login_failed = \cms_utils::get_real_ip();
        audit('', '(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Login Failed');
    }
    catch( \Exception $e ) {
        $error = $e->GetMessage();
    }
}
elseif( isset( $params['cancel'] ) ) {
    debug_buffer("Login cancelled.  Returning to login.");
    \CMSMS\LoginOperations::get_instance()->deauthenticate(); // just in case
    redirect( $config['root_url'].'/index.php', true );
}

$csrf = $_SESSION[$csrf_key] = md5(__FILE__.time().rand());

// display the login form

$theme_object = \cms_utils::get_theme_object();
if ($theme_object && method_exists($theme_object, 'display_login')) {
    $params += [
        'actionid' => $id,
        'error' => $error,
        'warning' => $warning,
        'message' => $message,
        'csrf' => $csrf,
    ];
    return $theme_object->display_login($params);
}

// default format
$tpl = $smarty->CreateTemplate( $this->GetTemplateResource( 'admin_login.tpl' ), null, null, $smarty );
$tpl->assign([
    'error' => $error,
    'warning' => $warning,
    'message' => $message,
    'changepwhash' => $pwhash,
    'username' => $username,
    'password' => $password,
    'csrf' => $csrf,
]);
//$tpl->assign( 'theme', $theme_object);
//$tpl->assign( 'theme_root', $theme_object->root_url );
return $tpl->fetch();
