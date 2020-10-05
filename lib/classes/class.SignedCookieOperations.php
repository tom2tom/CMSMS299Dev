<?php
#Secure cookie operations class
#Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use CMSMS\AppSingle;
use const CMS_ROOT_URL;
use const CMS_VERSION;

/**
 * A class of cookie operations that use obfuscated cookie names and signed
 *  cookie values, to reduce the risk of MITM or corruption attacks.
 *
 * @package CMS
 * @license GPL
 */
class SignedCookieOperations implements CookieManager
{

	/**
	 * @ignore
	 */
	private $_parts;

	/**
	 * @ignore
	 */
	private $_secure;

	/**
	 * @ignore
	 */
	private $_uuid;

	/**
	 * Constructor.
	 *
	 * @param mixed $app App | null. Optional since 2.9
	 */
	public function __construct($app = null)
	{
		$this->_parts = parse_url(CMS_ROOT_URL);
		if( !isset($this->_parts['host']) || $this->_parts['host'] == '' ) {
			self::$parts['host'] = CMS_ROOT_URL;
		}
		if( !isset($this->_parts['path']) || $this->_parts['path'] == '' ) {
			$this->_parts['path'] = '/';
		}
		if( !$app ) { $app = AppSingle::App(); }
		$this->_secure = $app->is_https_request();
		$this->_uuid = $app->GetSiteUUID();
	}

	/**
	 * Get the cookie name for $key.
	 *
	 * The name is obfuscated to minimize the opportunity for attacks.
	 *
	 * @param string $key The cookie name
	 */
	public function get_key(string $key) : string
	{
		return 'c'.hash('sha3-224',CMS_VERSION.$this->_uuid.$key);
	}

	/**
	 * Generate the cookie path.
	 *
	 * By default, this is the path portion of the root URL.
	 */
	protected function cookie_path() : string
	{
		return $this->_parts['path'];
	}

	/**
	 * Generate the cookie domain.
	 *
	 * By default, this is the host portion of the root URL.
	 */
	protected function cookie_domain() : string
	{
		return $this->_parts['host'];
	}

	/**
	 * Generate the cookie secure flag.
	 *
	 * This reflects whether or not the current request uses HTTPS.
	 */
	protected function cookie_secure() : bool
	{
		return $this->_secure;
	}

	/**
	 * Set the actual cookie.
	 *
	 * @param string $key The final cookie name (may be obfuscated)
	 * @param string $encoded The final cookie value.
	 * @param int $expire The expiry timestamp.
	 *   0 indicates a session cookie
	 *   < now (e.g. 1) indicates that the cookie may be removed
	 */
	protected function set_cookie(string $key, string $encoded, int $expire) : bool
	{
		$res = setcookie($key, $encoded, $expire, $this->cookie_path(), $this->cookie_domain(), $this->cookie_secure(), TRUE);
		return $res;
	}

	/**
	 * Retrieve the value of a validated cookie.
	 *
	 * This method will retrieve the value of a cookie if the signature of the
	 * cookie is valid. Otherwise, no value is returned.
	 *
	 * @param string $okey The cookie name
	 * @return string|null
	 */
	public function get(string $okey)
	{
		$key = $this->get_key($okey);
		if( !empty($_COOKIE[$key]) ) {
			list($sig,$val) = explode(':::',$_COOKIE[$key],2);
			if( hash('sha3-224',$val.$this->_uuid.$okey) == $sig ) return $val;
		}
	}

	/**
	 * Set a cookie.
	 * @since 2.9, the supplied $value need not be a string.
	 * If not scalar, the value  will be json_encode()'d before storage.
	 *
	 * @param string $okey The cookie name
	 * @param string $value The cookie value
	 * @param int $expires Optional expiry timestamp of the cookie. Default 0,
	 *  hence a session cookie.
	 */
	public function set(string $okey, $value, int $expires = 0) : bool
	{
		$val = is_scalar($value) ? ''.$value : json_encode($value,
			JSON_NUMERIC_CHECK |
			JSON_UNESCAPED_UNICODE |
			JSON_UNESCAPED_SLASHES |
			JSON_PARTIAL_OUTPUT_ON_ERROR);
		$key = $this->get_key($okey);
		$sig = hash('sha3-224',$val.$this->_uuid.$okey);
		return $this->set_cookie($key,$sig.':::'.$val,$expires);
	}

	/**
	 * Check whether a cookie exists (regardless of cookie value).
	 *
	 * @param string $key The input cookie name.
	 */
	public function exists(string $key) : bool
	{
		$key = $this->get_key($key);
		return isset($_COOKIE[$key]);
	}

	/**
	 * Erase a cookie.
	 *
	 * @param string $key The cookie name.
	 */
	public function erase(string $key)
	{
		$key = $this->get_key($key);
		unset($_COOKIE[$key]);
		$this->set_cookie($key,'',1);
	}
} // class

\class_alias(SignedCookieOperations::class, 'CMSMS\SignedCookies', false);
