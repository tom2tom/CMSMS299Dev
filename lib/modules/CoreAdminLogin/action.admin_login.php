<?php

namespace CoreAdminLogin;

use CMSMS\AdminUtils;
use CMSMS\AppSingle;
use CMSMS\Crypto;
use CMSMS\HookOperations;
use CMSMS\internal\LoginOperations;
use CMSMS\UserParams;
use CMSMS\Utils;
use CoreAdminLogin\LoginUserError;
use Exception;
use RuntimeException;
use function audit;
use function cms_warning;
use function debug_buffer;
use function lang;
use function redirect;

if( !isset($gCms) ) exit;

class LoginUserError extends RuntimeException
{
}

$username = $password = null;
$theme_object = Utils::get_theme_object();
$csrf_key = hash('tiger192,3', AppSingle::App()->GetSiteUUID());
$login_ops = LoginOperations::get_instance();
$username = $password = $error = $warning = $pwhash = $message = null;

if( isset( $_GET['recoverme'] ) ) {
    $code = filter_input( INPUT_GET, 'recoverme', FILTER_SANITIZE_STRING ); //TODO or clean_string() ?
    $user = $this->getLoginUtils()->find_recovery_user( $code );
    if( !$user ) {
        $error = $this->Lang('err_usernotfound');
    }
    else {
        $pwhash = $code;
    }
}
else if( isset( $params['forgotpwchangeform']) ) {
    try {
        $expected_csrf_val = ( isset($_SESSION[$csrf_key]) ) ? $_SESSION[$csrf_key] : null;
        $provided_csrf_val = ( isset($params['csrf']) ) ? $params['csrf'] : null;
        if( !$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val ) {
            throw new RuntimeException( $this->Lang('err_csrfinvalid') );
        }

        $usercode = filter_var( $params['changepwhash'], FILTER_SANITIZE_STRING );
        $username = html_entity_decode( filter_var( $params['username'], FILTER_SANITIZE_STRING, //TODO prohibit many non-LOW chars?
		    FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ) );
        $password1 = trim( filter_var( $params['password'], FILTER_UNSAFE_RAW, //TODO allow anything in P/W ?
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ) );
        $password2 = trim( filter_var( $params['passwordagain'], FILTER_UNSAFE_RAW, //TODO allow anything in P/W ?
            FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ) );
        if( !$usercode || !$username || !$password1 || !$password2 ) throw new LoginUserError( $this->Lang('err_missingdata') );
        if( $password1 != $password2 ) throw new LoginUserError( $this->Lang('err_passwordmismatch') );
        HookOperations::do_hook('Core::PasswordStrengthTest', $password1 );

        $user = $this->getLoginUtils()->find_recovery_user( $usercode );
        if( !$user || $user->username != $username ) throw new LoginUserError( $this->Lang('err_usernotfound') );

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
        $pwhash = $usercode;
        cms_warning('', '(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Password Reset Failed');
    }
    catch( Exception $e ) {
        $error = $e->GetMessage();
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
        if( $username ) $username = html_entity_decode( filter_var($username, FILTER_SANITIZE_STRING, //TODO prohibit many non-LOW chars?
		    FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ) );
        if( !$username ) throw new LoginUserError( $this->Lang('err_usernotfound') );
        unset( $params['username'] );

        HookOperations::do_hook('Core::LostPassword', [ 'username'=>$username ]);
        $userops = $gCms->GetUserOperations();
        $oneuser = $userops->LoadUserByUsername($username, null, true, true);
        if( !$oneuser ) {
            HookOperations::do_hook('Core::LoginFailed', [ 'user'=>$username ] );
            throw new LoginUserError( $this->Lang('err_usernotfound') );
        }

        $this->getLoginUtils()->send_recovery_email( $oneuser );
        $warning = $this->Lang('warn_recoveryemailsent');
    }
    catch( LoginUserError $e ) {
        $error = $e->GetMessage();
        HookOperations::do_hook('Core::LoginFailed', [ 'user'=>$username ]);
        $ip_login_failed = Utils::get_real_ip();
        cms_warnng('(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Password Recovery Failed');
    }
    catch( Exception $e ) {
        $error = $e->GetMessage();
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
		if( $username ) $username = html_entity_decode( filter_var( $username, FILTER_SANITIZE_STRING, //TODO prohibit many non-LOW chars?
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ) );
		$password = $params['password'] ?? null;
        if( $password ) $password = trim( filter_var( $password, FILTER_UNSAFE_RAW, //TODO allow anything in P/W ?
			FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK ) );
        if( !$username || !$password ) throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );

        $userops = $gCms->GetUserOperations();
        $oneuser = $userops->LoadUserByUsername( $username, null, true, true );
        if( !$oneuser ) throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );
        if( !$oneuser->Authenticate( $password ) )  throw new LoginUserError( $this->Lang('err_invalidusernamepassword') );

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
    redirect( $config['root_url'].'/menu.php', true );
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
