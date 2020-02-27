<?php
/*
TemporaryProfileStorage - a CMSMS class for caching profile data during a session
Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

namespace FilePicker;

use cms_utils;
use CMSMS\FilePickerProfile;

/**
 * Class to manage caching of profile data in the session data.
 */
class TemporaryProfileStorage
{
    private function __construct() {}

	/**
	 * Store $profile in $_SESSION[]
	 * @param FilePickerProfile $profile
	 * @return string
	 */
	public static function set(FilePickerProfile $profile)
    {
        $key = cms_utils::hash_string(__FILE__);
		$s = serialize($profile);
        $sig = cms_utils::hash_string(__FILE__.$s.microtime(TRUE).'1');
        $_SESSION[$key][$sig] = $s;
        return $sig;
    }

	/**
	 * Retrieve profile having signature $sig from $_SESSION[]
	 * @param string $sig
	 * @return mixed FilePickerProfile | null
	 */
    public static function get($sig)
    {
        $key = cms_utils::hash_string(__FILE__);
        if( isset($_SESSION[$key][$sig]) ) return unserialize($_SESSION[$key][$sig], ['allowed_classes'=>['CMSMS\\FilePickerProfile']]);
    }

	/**
	 * Clear profile having signature $sig from $_SESSION[]
	 * @param string $sig
	 */
	public static function clear($sig)
    {
        $key = cms_utils::hash_string(__FILE__);
        if( isset($_SESSION[$key][$sig]) ) unset($_SESSION[$key][$sig]);
    }

	/**
	 * Clear all profiles from $_SESSION[]
	 */
    public static function reset()
    {
        $key = cms_utils::hash_string(__FILE__);
        if( isset($_SESSION[$key]) ) unset($_SESSION[$key]);
    }
}
