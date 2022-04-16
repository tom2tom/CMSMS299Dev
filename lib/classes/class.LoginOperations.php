<?php
/*
Singleton class of login methods
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
use CMSMS\SignedCookieOperations;
use CMSMS\SingleItem;
use CMSMS\User;
use LogicException;
use RuntimeException;
use const CMS_DEPREC;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;

final class LoginOperations
{
	/* *
	 * @ignore
	 */
//	private static $_instance = null;
	/**
	 * @ignore
	 */
	private $_loginkey;
	/**
	 * @ignore
	 */
	private $_data;

	/**
	 * @ignore
	 */
	public function __construct()
	{
		if (!isset($this->_loginkey)) $this->_loginkey = $this->get_salt();
	}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get the singleton instance of this class.
	 * @deprecated since 3.0 instead use CMSMS\SingleItem::LoginOperations()
	 * @return self i.e. LoginOperations
	 */
	public static function get_instance() : self
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\SingleItem::LoginOperations()'));
		return SingleItem::LoginOperations();
	}

	public function deauthenticate()
	{
		(new SignedCookieOperations())->erase($this->_loginkey);
		unset($_SESSION[$this->_loginkey], $_SESSION[CMS_USER_KEY]);
	}

	/**
	 * Save session/cookie data
	 *
	 * @param User $user Primary user for the session
	 * @param mixed $effective_user Optional Substitute-user object | null
	 * @return boolean TRUE always
	 * @throws LogicException
	 */
	public function save_authentication(User $user, $effective_user = null)
	{
		if ($user->id < 1 || empty($user->password)) {
			throw new RuntimeException('Invalid user-information in '.__METHOD__);
		}
		$private_data = [
		'uid' => $user->id,
		'username' => $user->username,
		'eff_uid' => null,
		'eff_username' => null,
		'hash' => $user->password,
		];
		//From 3.0 - changing to super-user (1) is not supported
		if ($effective_user && $effective_user->id != $user->id && $effective_user->id > 1) {
			$private_data['eff_uid'] = $effective_user->id;
			$private_data['eff_username'] = $effective_user->username;
		}

		$config = SingleItem::Config();
		$k = $config['db_credentials'];
		if ($k) {
			$k = substr($k, 0, 12);
		} else {
			$k = $config['db_password'];
		}
		$pw = hash_hmac('tiger128,3', $this->get_salt(), $k);
		$enc = Crypto::encrypt_string(json_encode($private_data, JSON_PARTIAL_OUTPUT_ON_ERROR), $pw);
		$hash = Crypto::hash_string($this->get_salt() . $enc);
		$_SESSION[$this->_loginkey] = $hash . '::' . $enc;
		(new SignedCookieOperations())->set($this->_loginkey, base64_encode($enc), time() + 84600); // handle all $enc chars
		$this->_data = null;
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
	 * Get current or newly-generated salt
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
				SingleItem::LoadedData()->refresh('site_params');
			}
			return $salt;
		} else {  //must avoid siteprefs circularity
			return $this->create_csrf_token();
		}
	}

	/**
	 *
	 * @return boolean
	 * @throws LogicException
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
//			$config = CMSMS\SingleItem::Config();
//			if( !isset($config['stupidly_ignore_xss_vulnerability']) )
			return false;
		}
		return true;
	}

	public function get_loggedin_uid()
	{
		$data = $this->_get_data();
		if ($data) {
			return (int) $data['uid'];
		}
	}

	public function get_loggedin_username()
	{
		$data = $this->_get_data();
		if ($data) {
			return trim($data['username']);
		}
	}

	public function get_loggedin_user()
	{
		$uid = $this->get_loggedin_uid();
		if ($uid > 0) {
			return SingleItem::UserOperations()->LoadUserByID($uid);
		}
	}

	/*
	 * Effective user support
	 * If an effective-user has been recorded in the session, AND the primary/
	 * logged-in-user is a member of the admin group (see listusers.php), then
	 * the latter user may in effect 'become' the recorded effective-user
	 * Might be useful during user-config, but a small vulnerability ...
	 */

	/**
	 * Get id/enumerator for the recorded 'effective' user (if any) or else for the primary user
	 *
	 * @return mixed int | null
	 */
	public function get_effective_uid()
	{
		$data = $this->_get_data();
		if ($data) {
			if (!empty($data['eff_uid'])) {
				return (int) $data['eff_uid'];
			}
			return (int) $data['uid'];
		}
	}

	/**
	 * Get login-name for the session 'effective' user (if any) or else for the primary user
	 *
	 * @return mixed string | null
	 */
	public function get_effective_username()
	{
		$data = $this->_get_data();
		if ($data) {
			if (!empty($data['eff_username'])) {
				return trim($data['eff_username']);
			}
			return trim($data['username']);
		}
	}

	/**
	 * Change the current admin user.
	 * @since 3.0 this works only if the current user is a member of the superusers group
	 *
	 * @param User $e_user
	 */
	public function set_effective_user(User $e_user)
	{
		if ($e_user) {
			$li_user = $this->get_loggedin_user();
			if ($e_user->id != $li_user->id) {
				if (SingleItem::UserOperations()->UserInGroup($li_user->id, 1)) {
					$this->save_authentication($li_user, $e_user);
				}
			}
		}
	}

	/**
	 * Validate the user, using a custom P/W hash, not PHP's P/W hashing
	 * @param int $uid
	 * @param string $hash
	 * @return boolean
	 */
	private function _check_passhash(int $uid, string $hash) : bool
	{
		// we already confirmed that the payload is not corrupt
		$user = SingleItem::UserOperations()->LoadUserByID($uid);
		if (!$user || !$user->active) {
			return hash_equals(__DIR__, __FILE__); // waste some time
		}
		return hash_equals($user->password, $hash);
	}

	// @return mixed array | null : previously- or currently-generated data
	private function _get_data()
	{
		if (!empty($this->_data)) {
			return $this->_data;
		}

		// use session- and/or cookie-data to check whether user is authenticated
		if (isset($_SESSION[$this->_loginkey])) {
			$private_data = $_SESSION[$this->_loginkey];
			$split = true;
		} else {
			$private_data = (new SignedCookieOperations())->get($this->_loginkey);
			$split = false;
		}
		if (!$private_data) {
			return;
		}

		$config = SingleItem::Config();
		$k = $config['db_credentials'];
		if ($k) {
			$k = substr($k, 0, 12);
		} else {
			$k = $config['db_password'];
		}
		$pw = hash_hmac('tiger128,3', $this->get_salt(), $k);
		if ($split) {
			$parts = explode('::', $private_data, 2);
			if (count($parts) != 2) {
				return;
			}
			if ($parts[0] != Crypto::hash_string($this->get_salt() . $parts[1])) {
				return; // payload corrupted
			}
			$private_data = json_decode(Crypto::decrypt_string($parts[1], $pw), true);
		} else {
//			$private_data = json_decode(Crypto::decrypt_string(base64_decode($private_data), $pw), true);
			$s1 = base64_decode($private_data);
			$private_data = json_decode(Crypto::decrypt_string($s1, $pw), true);
		}

		if (!is_array($private_data)) {
			return;
		}
		if (empty($private_data['uid'])) {
			return;
		}
		if (empty($private_data['username'])) {
			return;
		}
		if (empty($private_data['hash'])) {
			return;
		}

		// authenticate
		if (/*!SingleItem::App()->is_frontend_request() && */!$this->_check_passhash($private_data['uid'], $private_data['hash'])) {
			return;
		}

		// if we get here, the user is authenticated.
		if (isset($_GET[CMS_SECURE_PARAM_NAME])) {
			$_SESSION[CMS_USER_KEY] = $_GET[CMS_SECURE_PARAM_NAME];
		} elseif (!isset($_SESSION[CMS_USER_KEY])) {
			// if we don't have a user key.... we generate a new csrf token.
			$_SESSION[CMS_USER_KEY] = $this->create_csrf_token();
		}

		$this->_data = $private_data;
		return $private_data;
	}
}
