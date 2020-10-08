<?php
# Cookie operations class
# Copyright (C) 2015-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.
namespace CMSMS;

use CMSMS\AppSingle;
use const CMS_ROOT_URL;

/**
 * A class providing convenience utilities for working with cookies.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 *
 * @since 2.9
 * @since 1.10 as global-namespace cms_cookies
 * @deprecated since 2.9 instead use SignedCookieOperations
 */
final class Cookies implements CookieManager
{
    // static properties here >> StaticProperties class ?
    /**
     * @ignore
     */
    private static $_parts;

    /**
     * @ignore
     */
    private function __construct() {}

    /**
     * @ignore
     * @return string
     */
    private static function __path()
    {
        if (!is_array(self::$_parts)) {
            self::$_parts = parse_url(CMS_ROOT_URL);
        }
        if (!isset(self::$_parts['path']) || self::$_parts['path'] == '') {
            self::$_parts['path'] = '/';
        }
        return self::$_parts['path'];
    }

    /**
     * @ignore
     * @return string
     */
    private static function __domain() : string
    {
        if (!is_array(self::$_parts)) {
            self::$_parts = parse_url(CMS_ROOT_URL);
        }
        if (!isset(self::$_parts['host']) || self::$_parts['host'] == '') {
            self::$_parts['host'] = CMS_ROOT_URL;
        }
        return self::$_parts['host'];
    }

    /**
     * @ignore
     * @param string $key
     * @param string $value
     * @param int $expire
     * @return bool indicating success
     */
    private static function __setcookie(string $key, string $value, int $expire) : bool
    {
        $res = setcookie($key,$value,$expire,
                     self::__path(),
                     self::__domain(),
                     AppSingle::App()->is_https_request(),
                     true);
        return $res;
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
        return self::__setcookie($key, $value, $expire);
    }

    /**
     * Get the value of a cookie
     *
     * @param string $key The cookie name
     * @return mixed NULL if the cookie does not exist, otherwise a string containing the cookie value.
     */
    public static function get(string $key)
    {
        if (isset($_COOKIE[$key])) {
            return $_COOKIE[$key];
        }
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
        self::__setcookie($key, '', 1);
    }
} // class
