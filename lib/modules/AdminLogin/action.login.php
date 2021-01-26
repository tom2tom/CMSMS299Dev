<?php
/*
AdminLogin module action - login : generate a login page.
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
use CMSMS\FormUtils;
use CMSMS\HookOperations;
use CMSMS\internal\LoginOperations;
use CMSMS\ScriptsMerger;
//use CMSMS\StylesMerger;
use CMSMS\UserParams;
use CMSMS\Utils;

if(!isset($gCms)) exit;

class LoginUserError extends RuntimeException {}

$theme_object = Utils::get_theme_object();
$csrf_key = hash('tiger192,3', AppSingle::App()->GetSiteUUID());
$login_ops = LoginOperations::get_instance();
$username = $password = $error = $warning = $pwhash = $message = null;

if(isset($_GET['recoverme'])) { //should be hexits hash
	$token = sanitizeVal($_GET['recoverme']);
	$user = $this->getLoginUtils()->find_recovery_user($token);
	if($user) {
		$pwhash = $token;
	}
	else {
		$error = $this->Lang('error_nouser');
	}
}
elseif(isset($params['forgotpwchangeform'])) {
	try {
		$expected_csrf_val = (isset($_SESSION[$csrf_key])) ? $_SESSION[$csrf_key] : null;
		$provided_csrf_val = (isset($params['csrf'])) ? $params['csrf'] : null;
		if(!$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val) {
			throw new RuntimeException($this->Lang('error_csrfinvalid'));
		}

		$token = $params['changepwhash']; // no pre-process cleanup in this context
		$username = $params['username'];
		$password1 = $params['password'];
		$password2 = $params['passwordagain'];
		if(!$token || !$username || !$password1 || !$password2) throw new LoginUserError($this->Lang('error_missingdata'));
		if($password1 != $password2) throw new LoginUserError($this->Lang('error_nomatch'));

		$user = $this->getLoginUtils()->find_recovery_user($token);
		if(!$user || $user->username != $username) throw new LoginUserError($this->Lang('error_nouser'));

//        Events::SendEvent('Core', 'PasswordStrengthTest', $password1);
		$TODO = HookOperations::do_hook('Core::PasswordStrengthTest', ['user' => $user, 'password' => $password1]);

		//TODO P/W policy / blacklist check
		$user->SetPassword($password1);
		$user->Save();
		$this->getLoginUtils()->remove_reset_code($user);

		$ip_passw_recovery = Utils::get_real_ip();
		audit('','Core','Completed lost password recovery for: '.$user->username.' (IP: '.$ip_passw_recovery.')');
		HookOperations::do_hook('Core::LostPasswordReset', ['uid'=>$user->id, 'username'=>$user->username, 'ip'=>$ip_passw_recovery]);
		$message = $this->Lang('msg_passwordchanged');
	}
	catch(LoginUserError $e) {
		$error = $e->GetMessage();
		HookOperations::do_hook('Core::LoginFailed', ['user'=>$username]);
		$ip_login_failed = Utils::get_real_ip();
		$pwhash = $token;
		cms_warning('', '(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Password Reset Failed');
	}
	catch(Throwable $t) {
		$error = $t->GetMessage();
	}
}
elseif(isset($params['forgotpwform'])) {
	// got the forgot password form request
	try {
		$expected_csrf = (isset($_SESSION[$csrf_key])) ? $_SESSION[$csrf_key] : null;
		$provided_csrf = (isset($params['csrf'])) ? $params['csrf'] : null;
		if(!$expected_csrf || !$provided_csrf || $expected_csrf != $provided_csrf) {
			throw new RuntimeException($this->Lang('error_csrfinvalid'));
		}

		$username = $params['username'] ?? null;
		unset($params['username']);
		if(!$username) throw new LoginUserError($this->Lang('error_nouser'));
		$username = sanitizeVal($username, 4);
		HookOperations::do_hook('Core::LostPassword', ['username'=>$username]);
		$userops = AppSingle::UserOperations();
		$oneuser = $userops->LoadUserByUsername($username, '', true, true);
		if(!$oneuser) {
			HookOperations::do_hook('Core::LoginFailed', ['user'=>$username]);
			throw new LoginUserError($this->Lang('error_nouser'));
		}

		$this->getLoginUtils()->send_recovery_email($oneuser);
		$warning = $this->Lang('warn_recoveryemailsent');
	}
	catch(LoginUserError $e) {
		$error = $e->GetMessage();
		HookOperations::do_hook('Core::LoginFailed', ['user'=>$username]);
		$ip_login_failed = Utils::get_real_ip();
		cms_warning('(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Password Recovery Failed');
	}
	catch(Throwable $t) {
		$error = $t->GetMessage();
	}
}
elseif(isset($params['submit'])) {
	// validate CSRF key
	try {
		$expected_csrf_val = $_SESSION[$csrf_key] ?? null;
		$provided_csrf_val = $params['csrf'] ?? null;
		if(!$expected_csrf_val || !$provided_csrf_val || $expected_csrf_val != $provided_csrf_val) {
			throw new RuntimeException($this->Lang('csrfinvalid'));
		}

		$username = $params['username'] ?? null;
		if($username) {
			$username = sanitizeVal($username, 4);
		}
		if(!$username) {
			$username = lang('nofieldgiven', lang('username')); // maybe illegal chars
		}
		$password = $params['password'] ?? null;
		//per https://pages.nist.gov/800-63-3/sp800-63b.html : P/W chars = printable ASCII | space | Unicode
		if($password) $password = sanitizeVal($password, 0);
		if(!$username || !$password) throw new LoginUserError($this->Lang('error_invalid'));

		$userops = AppSingle::UserOperations();
		$oneuser = $userops->LoadUserByUsername($username, $password, true, true);
		if(!$oneuser) throw new LoginUserError($this->Lang('error_invalid'));
		if(!$oneuser->Authenticate($password)) throw new LoginUserError($this->Lang('error_invalid'));

		// now we could redirect somewhere for a second stage of authenticateion.
		// but for core... we don't need to.

		// user is authenticated. log him hin.
		$login_ops->save_authentication($oneuser);
		audit($oneuser->id, "Admin Username: ".$oneuser->username, 'Logged In');
		HookOperations::do_hook('Core::LoginPost', ['user'=>&$oneuser]);

		// now redirect someplace
		$homepage = UserParams::get_for_user($oneuser->id,'homepage');
		if (!$homepage) {
			$homepage = $config['admin_url'].'/menu.php';
		}
		// TODO cleanups per function.login.php
//        $homepage = AdminUtils::get_session_url($homepage);
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
// done before save  $homepage = cms_specialchars_decode($homepage);
		//TODO generally support the websocket protocol 'wss' : 'ws'
		if (!startswith($homepage, 'http') && !startswith($homepage, '//') && startswith($homepage, '/')) {
			$homepage = CMS_ROOT_URL.$homepage;
		}
		redirect($homepage);
	}
	catch(LoginUserError $e) {
		$error = $e->GetMessage();
		HookOperations::do_hook('Core::LoginFailed', ['user'=>$username]);
		$ip_login_failed = Utils::get_real_ip();
		cms_warning('(IP: ' . $ip_login_failed . ') ' . "Admin Username: " . $username, 'Login Failed');
	}
	catch(Exception $e) {
		$error = $e->GetMessage();
	}
}
elseif(isset($params['cancel'])) {
	debug_buffer("Login cancelled.  Returning to login.");
	$login_ops->deauthenticate(); // just in case
	redirect($config['admin_url'].'/menu.php', true);
}

/*
$rel = substr(__DIR__, strlen(CMS_ADMIN_PATH) + 1);
$rel_url = strtr($rel, DIRECTORY_SEPARATOR, '/');
$fn = 'module';
if (NlsOperations::get_language_direction() == 'rtl') {
	if (is_file(__DIR__.DIRECTORY_SEPARATOR.'css'.DIRECTORY_SEPARATOR.$fn.'-rtl.css')) {
		$fn .= '-rtl';
	}
}
$fn.='.css';
*/
$enc = 'utf-8'; // TODO

$baseurl = $this->GetModuleURLPath();
$incs = cms_installed_jquery(true, true, true, true);
$jsm = new ScriptsMerger();
$jsm->queue_file($incs['jqcore'], 1);
//if (CMS_DEBUG) {
$jsm->queue_file($incs['jqmigrate'], 1); //in due course, omit this or keep if (CMS_DEBUG)
//}
$jsm->queue_file($incs['jqui'], 1);
$jsm->queue_matchedfile('login.js', 3, __DIR__.DIRECTORY_SEPARATOR.'lib');

//$csm = new StylesMerger();
//$csm->queue_matchedfile('normalize.css', 1);
//$csm->queue_file($incs['jquicss'], 2);
//$out = $csm->page_content();
$p = cms_get_css('normalize.css');
$u1 = cms_path_to_url($p);
$u2 = cms_path_to_url($incs['jquicss']);
$out = <<<EOS
<link rel="stylesheet" href="$u1" />
<link rel="stylesheet" href="$u2" />
<link rel="stylesheet" href="{$baseurl}/css/module.css" />

EOS;
$out .= $jsm->page_content('', false, false);

$_SESSION[$csrf_key] = $csrf = Crypto::random_string(16, true);
// generate this here, not via {form_start}, to work around forced incorrect formaction-value
$start_form = FormUtils::create_form_start($this, [
 'id' => $id,
 'action' => 'login',
 'extraparms' => [
  CMS_JOB_KEY => 1,
  'csrf' => $csrf,
]]);

$tpl = $smarty->CreateTemplate($this->GetTemplateResource('login.tpl')); //, null, null, $smarty);
$tpl->assign([
 'encoding' => $enc,
 'start_form' => $start_form,
 'admin_url' => $config['admin_url'],
 'header_includes' => $out,
 'error' => $error,
 'warning' => $warning,
 'message' => $message,
 'csrf' => $csrf,
 'changepwhash' => $pwhash,
 'username' => $username,
 'password' => $password,
]);
//'mod' => $this,
//'theme' => $theme_object,
//'theme_root' => $theme_object->root_url,

try {
	$tpl->display();
	return '';
} catch (Throwable $t) {
	return $t->getMessage();
}
