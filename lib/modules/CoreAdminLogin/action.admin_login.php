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

if( isset( $_GET['recoverme'] ) ) {
    $usercode = cleanValue(trim($_GET['recoverme']));
    $user = $this->getLoginUtils()->find_recovery_user( $usercode );
    if( $user ) {
        $pwhash = $usercode;
    } else {
        $error = $this->Lang('err_usernotfound');
    }
}
elseif( isset( $_POST['forgotpwchangeform']) ) {
    try {
        $expected_csrf_val = $_SESSION[$csrf_key] ?? null;
        $provided_csrf_val = $_POST['csrf'] ?? null; //comparison value, no sanitize needed (unless $_SESSION hacked!)
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new \RuntimeException( $this->Lang('err_csrfinvalid').' (001)' );
        }
        $usercode = cleanValue(trim($_POST['changepwhash']));
        $username = html_entity_decode( cleanValue(trim($_POST['username'] )));
        $password = trim($_POST['password']); //passwords are entitled to include anything
        $password2 = trim($_POST['passwordagain']);
        if( !$usercode || !$username || !$password || !$password2 ) throw new LoginUserError( $this->Lang('err_missingdata') );
        if( $password != $password2 ) throw new LoginUserError( $this->Lang('err_passwordmismatch') );
        \CMSMS\HookManager::do_hook('Core::PasswordStrengthTest', $password );

        $user = $this->getLoginUtils()->find_recovery_user( $usercode );
        if( !$user || $user->username != $username ) throw new LoginUserError( $this->Lang('err_usernotfound') );

        $user->SetPassword( $password );
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
        cms_warning('(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Password reset failed');
    }
    catch( \Exception $e ) {
        $error = $e->GetMessage();
    }
}
elseif( isset( $_POST['forgotpwform']) ) {
    // got the forgot password form request
    try {
        $expected_csrf_val = $_SESSION[$csrf_key] ?? null;
        $provided_csrf_val = $_POST['csrf'] ?? null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new \RuntimeException( $this->Lang('err_csrfinvalid').' (002)' );
        }

        $username = (isset($_POST['username'])) ? html_entity_decode(cleanValue(trim($_POST['username']))) : null;
        if( !$username ) throw new LoginUserError( $this->Lang('err_usernotfound') );
        unset( $_POST['username'] );

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
        cms_warning('(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Login Failed');
    }
    catch( \Exception $e ) {
        $error = $e->GetMessage();
    }
}
elseif( isset( $_POST['submit'] ) ) {
    try {
        // validate CSRF key
        $expected_csrf_val = $_SESSION[$csrf_key] ?? null;
        $provided_csrf_val = $_POST['csrf'] ?? null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new \RuntimeException( $this->Lang('err_csrfinvalid').' (003)' );
        }
        $username = (isset($_POST['username'])) ? html_entity_decode(cleanValue(trim($_POST['username']))) : null;
        $password = $_POST['password'] ?? null;
        if( !$username || !$password ) throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );

        $userops = $gCms->GetUserOperations();
        $oneuser = $userops->LoadUserByUsername( $username, null, true, true );
        if( !$oneuser ) throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );
        $password = trim($password); //no other filtering
        if( !$oneuser->Authenticate( $password ) )  throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );

        // now we could redirect somewhere for a second stage of authentication.
        // but for core... we don't need to.

        // user is authenticated
        \CMSMS\internal\LoginOperations::get_instance()->save_authentication( $oneuser );
        audit($oneuser->id, "Admin Username: ".$oneuser->username, 'Logged In');
        \CMSMS\HookManager::do_hook('Core::LoginPost', [ 'user'=>&$oneuser ] );

        // now redirect someplace
        $homepage = \cms_userprefs::get_for_user($oneuser->id,'homepage');
        if( !$homepage ) $homepage = $config['admin_url'];
        $homepage = html_entity_decode( $homepage );
        $homepage = \CMSMS\AdminUtils::get_session_url( $homepage );
        redirect( $homepage );
    }
    catch( LoginUserError $e ) {
        $error = $e->GetMessage();
        \CMSMS\HookManager::do_hook('Core::LoginFailed', [ 'user'=>$username ] );
        $ip_login_failed = \cms_utils::get_real_ip();
        cms_warning('(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Login Failed');
    }
    catch( \Exception $e ) {
        $error = $e->GetMessage();
    }
}
elseif( isset( $_POST['cancel'] ) ) {
    debug_buffer("Login cancelled.  Returning to login.");
    \CMSMS\internal\LoginOperations::get_instance()->deauthenticate(); // just in case
    redirect( $config['root_url'].'/index.php', true );
}

$csrf = $_SESSION[$csrf_key] = md5(__FILE__.time().rand());
$lang_code = CmsNlsOperations::get_current_language();
if (($p = strpos($lang_code,'_')) !== false) {
    $lang_code = substr($lang_code,0,$p);
}
$lang_dir = CmsNlsOperations::get_language_direction();
$encoding = CmsNlsOperations::get_encoding();
$header_includes = ''; //TODO dynamic
$bottom_includes = ''; //TODO dynamic
$baseurl = $this->GetModuleURLPath();
$actionurl = ''; //TODO $this->create_url( whatever );

// display the login form

$tpl = $smarty->CreateTemplate( $this->GetTemplateResource( 'admin_login.tpl' ), null, null, $smarty );
$tpl->assign([
    'mod' => $this,
    'actionid' => $id,
    'lang_code' => $lang_code,
    'lang_dir' => $lang_dir,
    'encoding' => $encoding,
    'header_includes' => $header_includes,
    'bottom_includes' => $bottom_includes,
    'root_url'=> CMS_ROOT_URL,
    'module_url' => $baseurl,
    'action_url' => $actionurl,
    'csrf' => $csrf,
    'error' => $error,
    'warning' => $warning,
    'message' => $message,
    'changepwhash' => $pwhash,
    'username' => $username,
    'password' => $password,
]);
$tpl->display();
