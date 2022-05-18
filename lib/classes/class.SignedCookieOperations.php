<?php
/*
Secure cookie operations class
Copyright (C) 2019-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\ICookieManager;
use function CMSMS\get_site_UUID;
use function CMSMS\is_secure_request;
use const CMS_ROOT_URL;
use const CMS_VERSION;

/**
 * A class of convenience utilities for using cookies having an obfuscated name
 * and signed value, to reduce the risk of MITM or corruption attacks.
 *
 * @package CMS
 * @license GPL
 */
final class SignedCookieOperations implements ICookieManager
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
     */
    #[\ReturnTypeWillChange]
    public function __construct()
    {
        $this->_parts = parse_url(CMS_ROOT_URL);
        if (empty($this->_parts['host'])) {
            $this->_parts['host'] = CMS_ROOT_URL; // default to whole domain (including all subdomains)
        }
        if (empty($this->_parts['path'])) {
            $this->_parts['path'] = '/'; // default to entire domain
        }
        $this->_secure = is_secure_request();
        $this->_uuid = get_site_UUID();
    }

    /**
     * Retrieve the validated value of a cookie.
     *
     * This method will retrieve the value of a cookie if the signature of the
     * cookie is valid. Otherwise, an empty string is returned.
     *
     * @param string $okey The cookie name
     * @return string
     */
    public function get(string $okey) : string
    {
        $key = $this->get_key($okey);
        if (!empty($_COOKIE[$key])) {
            list($sig, $val) = explode(':::', $_COOKIE[$key], 2);
            if (hash('sha3-224', $val.$this->_uuid.$okey) == $sig) { // no compare-timing risk
                return $val;
            }
        }
        return '';
    }

    /**
     * Set a cookie.
     *
     * @param string $okey The cookie name
     * @param mixed $value The cookie value
     *  Since 3.0, the supplied $value need not be a string.
     *  If not scalar, the value  will be json_encode()'d before storage.
     * @param int $expires Optional expiry timestamp of the cookie. Default 0,
     *  hence a session cookie.
     * @return bool indicating success
     */
    public function set(string $okey, $value, int $expires = 0) : bool
    {
        $val = is_scalar($value) ? ''.$value : json_encode($value,
            JSON_NUMERIC_CHECK |
            JSON_UNESCAPED_UNICODE |
            JSON_UNESCAPED_SLASHES |
            JSON_PARTIAL_OUTPUT_ON_ERROR);
        $key = $this->get_key($okey);
        $sig = hash('sha3-224', $val.$this->_uuid.$okey);
        return $this->set_cookie($key, $sig.':::'.$val, $expires);
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
        $this->set_cookie($key, '', 1);
    }

    /**
     * Get the cookie name for $key.
     *
     * The name is obfuscated to reduce the opportunity for attacks.
     *
     * @param string $key The cookie name
     * @return string
     */
    private function get_key(string $key) : string
    {
        //any algo >= 36 bytes will do, this one is fastest
        $s = hash('sha1', CMS_VERSION.$this->_uuid.$key);
        $s = substr($s, 0, 18) ^ substr($s, -18, 18);
        $s = strtr(base64_encode($s), '+/', 'qd'); //24 alphanums
        $s[0] = 'c';
        return $s;
    }

    /**
     * Generate the cookie path.
     *
     * By default, this is the path portion of the root URL.
     * @return string
     */
    private function cookie_path() : string
    {
        return $this->_parts['path'];
    }

    /**
     * Generate the cookie domain.
     *
     * By default, this is the host portion of the root URL.
     * @return string
     */
    private function cookie_domain() : string
    {
        return $this->_parts['host'];
    }

    /**
     * Generate the cookie secure flag.
     *
     * This reflects whether or not the current request uses HTTPS.
     * @return bool
     */
    private function cookie_secure() : bool
    {
        return $this->_secure;
    }

    /**
     * Set the actual cookie.
     *
     * @param string $key The cookie name (obfuscated)
     * @param string $value The cookie value (encoded)
     * @param int $expire The expiry timestamp
     *   0 indicates a session cookie
     *   < now (e.g. 1) indicates that the cookie is to be removed
     * @return bool indicating success
     */
    private function set_cookie(string $key, string $value, int $expire) : bool
    {
        return setcookie($key, $value, $expire, $this->cookie_path(),
          $this->cookie_domain(), $this->cookie_secure(), true);
    }
} // class
