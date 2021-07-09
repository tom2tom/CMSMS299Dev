<?php
/*
TemporaryInstanceStorage - a class for caching filesystem information during a session
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

namespace FilePicker;

use CMSMS\AppSingle;

/**
 * Class to manage session-caching of 'flat' data
 */
class TemporaryInstanceStorage
{
    private function __construct() {}
    private function __clone() {}

    public static function get_cleaner()
    {
        return [self::class, 'reset'];
    }

    protected static function cachegroup()
	{
		return hash('adler32', session_id().self::class);
	}

    public static function set($key, $val)
    {
        $grp = self::cachegroup();
        $val = trim($val); // make sure it's a string
        AppSingle::SystemCache()->set($key ,$val, $grp);
        return $key;
    }

    public static function get($key)
    {
        $grp = self::cachegroup();
        return AppSingle::SystemCache()->get($key, $grp);
    }

    public static function clear($key)
    {
        $grp = self::cachegroup();
        AppSingle::SystemCache()->delete($key, $grp);
     }

    public static function reset()
    {
        $grp = self::cachegroup();
        AppSingle::SystemCache()->clear($grp);
    }
}
