<?php
/*
Cookie operations class
Copyright (C) 2015-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\ICookieManager;
use function CMSMS\is_secure_request;
use const CMS_ROOT_URL;

/**
 * A class providing convenience utilities for working with cookies.
 *
 * @package CMS
 * @license GPL
 *
 * @since 3.0
 * @since 1.10 as global-namespace cms_cookies
 * @deprecated since 3.0 instead use SignedCookieOperations
 */
final class Cookies implements ICookieManager
{
    // static properties here >> Lone property|ies ?
    /**
     * @ignore
     */
    private static $_parts;

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    private function __construct() {}

    /**
     * @ignore
     * @return string
     */
    private static function _path()
    {
        if (!is_array(self::$_parts)) {
            self::$_parts = parse_url(CMS_ROOT_URL);
        }
        if (!empty(self::$_parts['path'])) return self::$_parts['path'];
        return '/'; // default to whole domain
    }

    /**
     * @ignore
     * @return string
     */
    private static function _domain() : string
    {
        if (!is_array(self::$_parts)) {
            self::$_parts = parse_url(CMS_ROOT_URL);
        }
        if (!empty(self::$_parts['host'])) return self::$_parts['host'];
        return CMS_ROOT_URL; // default to whole domain (including all subdomains)
    }

    /**
     * @ignore
     * @param string $key
     * @param string $value
     * @param int $expire
     * @return bool indicating success
     */
    private static function _setcookie(string $key, string $value, int $expire) : bool
    {
        $secure = is_secure_request();
        return setcookie($key, $value, $expire,
            self::_path(), self::_domain(), $secure, true);
    }

    /**
     * Set a cookie
     *
     * @param string $key The cookie name
     * @param string $value The cookie value
     * @param int    $expire *NIX timestamp of the time the cookie will expire.
     *  By default, cookies that expire when the browser closes will be created.
     * @return bool indicating success
     */
    public static function set(string $key, string $value, int $expire = 0) : bool
    {
        return self::_setcookie($key, $value, $expire);
    }

    /**
     * Get the value of a cookie
     *
     * @param string $key The cookie name
     * @return mixed string containing the cookie value | null if the cookie does not exist
     */
    public static function get(string $key)
    {
        return $_COOKIE[$key] ?? null;
    }

    /**
     * Test whether a cookie exists
     *
     * @since 1.11
     * @param string $key The cookie name.
     * @return bool
     */
    public static function exists(string $key) : bool
    {
        return isset($_COOKIE[$key]);
    }

    /**
     * Erase a cookie
     *
     * @param string $key The cookie name
     */
    public static function erase(string $key)
    {
        unset($_COOKIE[$key]);
        self::_setcookie($key, '', 1);
    }
} // class
