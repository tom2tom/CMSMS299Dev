<?php
#Secure cookie operations class
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CmsApp;
use LogicException;
use const CMS_ROOT_URL;
use const CMS_VERSION;

/**
 * A class of cookie operations that are capable of obfuscating cookie names,
 * and signing cookie values to minimize the risk of MITM or corruption attacks.
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
     * Constructor.
     *
     * @param CmsApp $app The application instance.
     */
    public function __construct( CmsApp $app )
    {
        $this->_parts = parse_url(CMS_ROOT_URL);
        if( !isset($this->_parts['host']) || $this->_parts['host'] == '' ) {
            self::$parts['host'] = CMS_ROOT_URL;
        }
        if( !isset($this->_parts['path']) || $this->_parts['path'] == '' ) {
            $this->_parts['path'] = '/';
        }
        $this->_secure = $app->is_https_request();
    }

    /**
     * Encode a key.
     *
     * The cookie name is encoded to be obfuscated to minimize the opportunity of attacks.
     *
     * @param string $key The cookie name
     */
    protected function get_key(string $key) : string
    {
        return 'c'.sha1(__FILE__.$key.CMS_VERSION);
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
     * By default, this is relative to whether or not the request was using HTTPS or not.
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
     * @param int $expire The expiry timestamp.  0 may be provided to indicate a session cookie, a timestamp earlier than now may be
     *    provided to indicate that the cookie can be removed.
     */
    protected function set_cookie(string $key, string $encoded = null, int $expire) : bool
    {
        $res = setcookie($key, $encoded, $expire, $this->cookie_path(), $this->cookie_domain(), $this->cookie_secure(), TRUE);
        return $res;
    }

    /**
     * Retrieve the value of a cookie.
     *
     * This method will retrieve the value of the cookie by first obfuscating
     * the cookie name, then ensuring that the signature of retrieved cookie
     * can be verified. If the attached signature does not match the generated
     * signature, no value is returned.
     *
     * @param string $okey The cookie name
     * @return string|null
     */
    public function get(string $okey)
    {
        $key = $this->get_key($okey);
        if( isset($_COOKIE[$key]) ) {
            list($sig,$val) = explode(':::',$_COOKIE[$key],2);
            if( sha1($val.__FILE__.$okey.CMS_VERSION) == $sig ) return $val;
        }
    }

    /**
     * Set a cookie.
     *
     * This method will first obfuscate the cookie name.
     * Then it will generate a signature for the cookie contents, then append it to the cookie value.
     * Then generate a standard cookie.
     *
     * @param string $okey The input cookie name
     * @param string $value The cookie value.
     * @param int $expires The expiry timestamp of the cookie.  A value of 0 indicates that a session cookie should be created.
     */
    public function set(string $okey, $value, int $expires = 0) : bool
    {
        if( !is_string($value) ) throw new LogicException('Cookie value passed to '.__METHOD__.' must be a string');
        $key = $this->get_key($okey);
        $sig = sha1($value.__FILE__.$okey.CMS_VERSION);
        return $this->set_cookie($key,$sig.':::'.$value,$expires);
    }

    /**
     * Test if a cookie exists.
     *
     * This method will obfuscate the input cookie name.
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
     * This method will obfuscate the input cookie name.
     *
     * @param string $key The input cookie name.
     */
    public function erase(string $key)
    {
        $key = $this->get_key($key);
        unset($_COOKIE[$key]);
        $this->set_cookie($key,null,time()-3600);
    }
} // class
