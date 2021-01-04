<?php
/*
TemporaryProfileStorage - a class for caching profile data during a session
Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Fernando Morgado and all other contributors from the CMSMS Development Team.

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

use CMSMS\Crypto;
use CMSMS\SystemCache;
use FilePicker\Profile;

/**
 * Class to manage session-caching of profile data
 */
class TemporaryProfileStorage
{
    private function __construct() {}
    private function __clone() {}

    /**
     * Get the function to use for end-of-session cleanup
     * @return callable
     */
    public static function get_cleaner()
    {
        return [self::class, 'reset'];
    }

    /**
     * @ignore
     */
    protected static function cachegroup()
    {
        return hash('adler32', session_id().self::class);
    }

    /**
     * Store $profile in the cache
     * @param Profile $profile
     * @return string identifier for the stored data
     */
    public static function set(Profile $profile)
    {
        $grp = self::cachegroup();
        $s = serialize($profile);
        $key = Crypto::hash_string($grp . $s . microtime(true));
        SystemCache::get_instance()->set($key ,$s, $grp);
        return $key;
    }

    /**
     * Retrieve the profile having signature $key from the cache
     * @param string $key
     * @return mixed FilePicker\Profile | null
     */
    public static function get($key)
    {
        $grp = self::cachegroup();
        $s = SystemCache::get_instance()->get($key, $grp);
        if ($s) {
            return unserialize($s, ['allowed_classes'=>['FilePicker\\Profile']]);
        }
    }

    /**
     * Clear the profile having signature $key from the cache
     * @param string $key
     */
    public static function clear($key)
    {
        $grp = self::cachegroup();
        SystemCache::get_instance()->delete($key, $grp);
    }

    /**
     * Clear all profiles from the cache
     */
    public static function reset()
    {
        $grp = self::cachegroup();
        SystemCache::get_instance()->clear($grp);
    }
}
