<?php
/*
An interface for CMSMS cookie operations classes
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

/**
 * An interface for CMSMS cookie operations classes.
 * @package CMSMS
 * @since 2.99
 * @license GPL
 */
interface ICookieManager
{
    /**
     * Get a cookie value
     *
     * @abstract
     * @param string $key The name of the cookie
     * @return mixed The output may be null, or a string or another data type.
     */
    public function get(string $key);

    /**
     * Set a cookie value
     *
     * @param string $key The name of the cookie
     * @param mixed The cookie contents
     * @param int $expires The default expiry timestamp.  if 0 is specified, then a session cookie is created.
     * @return bool true on success
     */
    public function set(string $key, $value, int $expires = 0) : bool;

    /**
     * Report whether a cookie exists
     *
     * @param string $key The name of the cookie
     * @return bool
     */
    public function exists(string $key) : bool;

    /**
     * Erase a cookie
     *
     * @param string $key The name of the cookie
     */
    public function erase(string $key);
} // interface
