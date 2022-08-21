<?php
/*
Singleton class of authentication methods
Copyright (C) 2016-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\Events;
use CMSMS\Lone;
use CMSMS\SignedCookieOperations;
use CMSMS\User;
use LogicException;
use RuntimeException;
use const CMS_DEPREC;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use function get_secure_param;
use function redirect;
use function startswith;

final class AuthOperations
{
	/**
	 * @var string a site-constant
	 * @ignore
	 */
	private $loginkey;
	/**
	 * @var array current login id(s), name(s)
	 * @ignore
	 */
	private $data;

	/**
	 * @ignore
	 */
	public function __construct()
	{
		$this->loginkey = $this->get_salt();
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	private function __clone() {}// : void {}

	/**
	 * Get the singleton instance of this class.
	 * @deprecated since 3.0 instead use CMSMS\Lone::get('AuthOperations')
	 * @return self i.e. AuthOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'AuthOperations\')'));
		return Lone::get('AuthOperations');
	}

	public function deauthenticate()
	{
//		(new SignedCookieOperations())->erase($this->loginkey); TODO support logout but also future auto-login
		unset($_SESSION[$this->loginkey], $_SESSION[CMS_USER_KEY]);
	}

	/**
	 * Check whether credentials are sufficient to log in or redirect
	 * @param mixed $param value(s) to use for checking. Default null
	 * @return mixed bool indicating acceptance, or not at all (redirection)
	 */
	public function authenticate($param = null)
	{
		$v = $_SESSION[CMS_USER_KEY] ?? ''; // preserve maybe-overwritten value
		$k = $_SESSION[$this->loginkey] ?? '';
		if ($k) {
			unset($_SESSION[$this->loginkey]); // force a cookie-check
			unset($this->data);
		}
		$private_data = $this->get_data();
		if ($k) {
			$_SESSION[$this->loginkey] = $k;
		}
		if ($v) {
			$_SESSION[CMS_USER_KEY] = $v;
		}
		if ($private_data) {
			$uid = $private_data['eff_uid'] ?? $private_data['uid'] ?? 0;
			if ($uid > 0) {
				if ($param) {
					//TODO check stuff
					return $_SESSION[CMS_USER_KEY] == $param;
				}
				$this->login_finish($uid);
			}
		}
		return false;
	}

	/**
	 * Save login data in session and cookie
	 *
	 * @param User $userobj Primary user object for the session
	 * @param mixed $effective_user Optional Substitute-user object | null
	 * @return boolean true always
	 * @throws RuntimeException
	 */
	public function save_authentication(User $userobj, $effective_user = null)
	{
		if ($userobj->id < 1 || empty($userobj->password)) {
			throw new RuntimeException('Invalid user-information in '.__METHOD__);
		}
		//TODO consider server-side data (db|cache) as well as or instead of cookie
		//consider also recording a 'fingerprint' parameter (albeit not much security-enhancement) for use in auto-logins
		$k = Crypto::random_string(6, true, true);
		$n = ord($k[0]) >> 3;
		$v = Crypto::random_string($n); // 5..32 bytes
		$salt = $this->get_salt();
		$private_data = [
		 $k.$n => $v, // additional cookie obfuscation
		 'hash' => hash_hmac('tiger128,3', $salt.$userobj->id, $salt ^ $userobj->username),
		 'eff_username' => '      '
		];
		//From 3.0 - changing to super-user (1) is not supported
		if ($effective_user && $effective_user->id != $userobj->id && $effective_user->id > 1) {
			$private_data['eff_username'] = $effective_user->username;
		}
		$config = Lone::get('Config');
		$k = $config['db_credentials'];
		if ($k) {
			$k = substr($k, 0, 12);
		} else {
			$k = $config['db_password'];
		}
		$pw = hash_hmac('tiger128,3', $salt, $k);
		$enc = Crypto::encrypt_string(json_encode($private_data,
			JSON_UNESCAPED_UNICODE |
			JSON_UNESCAPED_SLASHES |
			JSON_INVALID_UTF8_IGNORE //PHP7.2+, needed for random-string value
		), $pw);
		$hash = Crypto::hash_string($salt . $enc);
		$_SESSION[$this->loginkey] = $hash . '::' . $enc;
		$v = base64_encode($enc); // encode cookie value for URL compatibility
		$v = strtr($v, '+/=', '-~.'); //~ is not actually url-safe!
		$n = (int)AppParams::get('logintimeout');
		if ($n > 0) {
			$n = $n * 86400 + time(); // cookie endures past this session
		}
		(new SignedCookieOperations())->set($this->loginkey, $v, $n);
		$this->data = [
		 'uid' => $userobj->id,
		 'username' => $userobj->username,
		 'eff_uid' => null,
		 'eff_username' => null,
		];
		if ($effective_user && $effective_user->id != $userobj->id && $effective_user->id > 1) {
			$this->data['eff_uid'] = $effective_user->id;
			$this->data['eff_username'] = $effective_user->username;
		}
		return true;
	}

	/**
	 * Get a randomish string
	 * 8-12 bytes, all from the 64-char subset of ASCII immune to [raw]urlencoding
	 * @return string
	 */
	public function create_csrf_token() : string
	{
		$l = mt_rand(8, 12);
		$str = str_repeat(' ', $l);
		//accept alphanum and -_ (none of which would be affected by [raw]urlencode())
		$ex = [46,47,58,59,60,61,62,63,64,91,92,93,94,96];
		for ($i = 0; $i < $l; ++$i) {
			$n = mt_rand(45, 122); // good enough for here
			if (!in_array($n, $ex)) {
				$str[$i] = chr($n);
			} else {
				--$i;
			}
		}
		return $str;
	}

	/**
	 * Get current or newly-generated login salt
	 * @since 3.0 access public
	 * @return string
	 */
	public function get_salt() : string
	{
		if (!AppState::test(AppState::INSTALL)) {
			$salt = AppParams::get('loginsalt');
			if (!$salt) {
				$salt = $this->create_csrf_token();
				AppParams::set('loginsalt', $salt);
				Lone::get('LoadedData')->refresh('site_params');
			}
			return $salt;
		} else { // must avoid siteprefs circularity
			return $this->create_csrf_token();
		}
	}

	/**
	 *
	 * @return boolean
	 * @throws RuntimeException
	 */
	public function validate_requestkey() : bool
	{
		// asume we are authenticated
		// now we validate that the request has the user key in it somewhere.
		if (!isset($_SESSION[CMS_USER_KEY])) {
			throw new RuntimeException('Internal: User key not found in session.');
		}
		$v = $_REQUEST[CMS_SECURE_PARAM_NAME] ?? '<no$!tgonna!$happen>';

		// validate the key in the request against what we have in the session.
		if ($v != $_SESSION[CMS_USER_KEY]) {
//			$config = CMSMS\Lone::get('Config');
//			if( !isset($config['stupidly_ignore_xss_vulnerability']) )
			return false;
		}
		return true;
	}

	/**
	 * Get logged-in user's id/enumerator, if possible
	 * @return mixed int | null
	 */
	public function get_loggedin_uid()
	{
		$data = $this->get_data();
		if ($data) {
			return (int)$data['uid'];
		}
	}

	/**
	 * Get logged-in user's login/account, if possible
	 * @return mixed string | null
	 */
	public function get_loggedin_username()
	{
		$data = $this->get_data();
		if ($data) {
			return trim($data['username']);
		}
	}

	/**
	 * Get all properties of the logged-in user, if any
	 * @see UserOperations::LoadUserByID()
	 * @return mixed User | null
	 */
	public function get_loggedin_user()
	{
		$uid = $this->get_loggedin_uid();
		if ($uid > 0) {
			return Lone::get('UserOperations')->LoadUserByID($uid);
		}
	}

	/*
	 * Effective user support
	 * If an effective-user has been recorded in the session, AND the primary/
	 * logged-in-user is a member of the admin/super group (see listusers.php),
	 * then the latter user may in effect 'become' the recorded effective-user
	 * Might be useful during user-config, but a small vulnerability ...
	 */

	/**
	 * Get id/enumerator for the recorded 'effective' user (if any) or else for the primary user
	 *
	 * @return mixed int | null
	 */
	public function get_effective_uid()
	{
		$data = $this->get_data();
		if ($data) {
			if (!empty($data['eff_uid'])) {
				return (int)$data['eff_uid'];
			}
			return (int)$data['uid'];
		}
	}

	/**
	 * Get recorded login/account for the session 'effective' user (if any)
	 *  or else for the primary user
	 *
	 * @return mixed string | null
	 */
	public function get_effective_username()
	{
		$data = $this->get_data();
		if ($data) {
			if (!empty($data['eff_username'])) {
				return trim($data['eff_username']);
			}
			return trim($data['username']);
		}
	}

	/**
	 * Change the current admin user.
	 * @since 3.0 this works only if the current user is a member of the
	 *  admin/super users group
	 *
	 * @param User $e_user
	 */
	public function set_effective_user(User $e_user)
	{
		if ($e_user) {
			$li_user = $this->get_loggedin_user();
			if ($e_user->id != $li_user->id) {
				if (Lone::get('UserOperations')->UserInGroup($li_user->id, 1)) {
					$this->save_authentication($li_user, $e_user);
				}
			}
		}
	}

	/**
	 * Finalize login arrangements then redirect to user's start-page
	 * @since 3.0
	 * @param int $uid user enumerator
	 */
	public function login_finish($uid)
	{
		//TODO refresh login cookie ??
		$userobj = Lone::get('UserOperations')->LoadUserByID($uid);
		log_info($userobj->id, 'Admin User '.$userobj->username, 'Logged In');
		Events::SendEvent('Core', 'LoginPost', ['user'=>&$userobj]);
		// find the user's start-page, if any
		$url = UserParams::get_for_user($uid, 'homepage');
		if (!$url) {
			$config = Lone::get('Config');
			$url = $config['admin_url'].'/menu.php';
		} elseif (startswith($url, 'lib/moduleinterface.php')) {
			$url = CMS_ROOT_URL.'/'.$url;
		} elseif (startswith($url, '/') && !startswith($url, '//')) {
			$url = CMS_ROOT_URL.$url;
		}
		$url .= get_secure_param($url);
		redirect($url);
	}

	/**
	 * Validate the user, using a custom P/W hash, not PHP's P/W hashing
	 * @internal
	 *
	 * @param int $uid
	 * @param string $hash
	 * @return boolean
	 */
/*	private function check_passhash(int $uid, string $hash) : bool
	{
		// we already confirmed that the payload is not corrupt
		$userobj = Lone::get('UserOperations')->LoadUserByID($uid);
		if (!$userobj || !$userobj->active) {
			return hash_equals(__DIR__, __FILE__); // waste some time
		}
		return hash_equals($userobj->password, $hash);
	}
*/
	/**
	 * Retrieve credentials data from a previous call here or
	 * (after authentication) from $_SESSION or a login cookie.
	 * Sets $_SESSION[CMS_USER_KEY] if newly authenticated.
	 * @internal
	 *
	 * @return mixed array | null
	 */
	private function get_data()
	{
		if (!empty($this->data)) {
			return $this->data;
		}

		// use session- and/or cookie-data to check whether user is authenticated
		if (isset($_SESSION[$this->loginkey])) {
			$private_data = $_SESSION[$this->loginkey];
			$cooked = false;
		} else {
			$private_data = (new SignedCookieOperations())->get($this->loginkey);
			$cooked = true;
		}
		if (empty($private_data)) {
			return;
		}

		$config = Lone::get('Config');
		$k = $config['db_credentials'];
		if ($k) {
			$k = substr($k, 0, 12);
		} else {
			$k = $config['db_password'];
		}
		$salt = $this->get_salt();
		$pw = hash_hmac('tiger128,3', $salt, $k);
		if ($cooked) {
			$v = strtr($private_data, '-~.', '+/=');
			$v = base64_decode($v);
			$private_data = json_decode(Crypto::decrypt_string($v, $pw),
				JSON_OBJECT_AS_ARRAY | JSON_INVALID_UTF8_IGNORE);
		} else {
			$parts = explode('::', $private_data, 2);
			if (count($parts) != 2) {
				return;
			}
			if ($parts[0] != Crypto::hash_string($salt . $parts[1])) {
				return; // payload corrupted
			}
			$private_data = json_decode(Crypto::decrypt_string($parts[1], $pw),
				JSON_OBJECT_AS_ARRAY | JSON_INVALID_UTF8_IGNORE);
		}

		if (!$private_data ||
			empty($private_data['eff_username']) ||
			empty($private_data['hash'])) {
			sleep(1); // in case some brute-forcing is underway
			return;
		}
		// authenticate
		//TODO consider server-side data (db|cache) as well as or instead of cookie
		//see also $find_recovery_user() which uses same algo and similar hash
		$n = -1;
		$ops = Lone::get('UserOperations');
		$usernames = $ops->GetUsers(true); // public name not used here
		foreach ($usernames as $uid => $name) {
			if ($private_data['hash'] == hash_hmac('tiger128,3', $salt.$uid, $salt ^ $name)) { // no compare-timing risk
				$private_data['uid'] = $n = $uid;
				$private_data['username'] = $name;
				break;
			}
		}
		if ($n == -1) {
			unset($usernames); //garbage-collector assistance
			return;
		}

		$k = key($private_data); // remove padder
		unset($private_data[$k]);
		unset($private_data['hash']);

		$k = trim($private_data['eff_username']);
		if ($k) {
			$n = array_search($k, $usernames);
			if ($n !== false) {
				$private_data['eff_uid'] = $n;
				$private_data['eff_username'] = $k;
			} else {
				$private_data['eff_uid'] = null;
				$private_data['eff_username'] = null;
			}
		} else {
			$private_data['eff_uid'] = null;
			$private_data['eff_username'] = null;
		}
		unset($usernames); //garbage-collector assistance

		// if we get here, the user is authenticated
		if (!empty($_REQUEST[CMS_SECURE_PARAM_NAME])) {
			$_SESSION[CMS_USER_KEY] = $_REQUEST[CMS_SECURE_PARAM_NAME];
		} elseif (!isset($_SESSION[CMS_USER_KEY])) {
			// no user key, so generate one
			$_SESSION[CMS_USER_KEY] = $this->create_csrf_token();
		}

		$this->data = $private_data;
		return $private_data;
	}
}
