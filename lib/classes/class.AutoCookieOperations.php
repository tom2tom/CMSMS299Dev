<?php
/*
Cookie operations class
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace CMSMS;

use CMSMS\SignedCookieOperations;
use RuntimeException;
use function startswith;

/**
 * A class of methods for cookies that are capable of handling different data types.
 *
 * This class adjusts the way cookies are stored to allow identifying
 * their primitive type (string, object, array). And after retrieval
 * it can automatically restore them.
 *
 * This class uses json to encode arrays and objects, for security.
 * Objects should implement the JsonSerializable interface if they can
 * be saved to a cookie.
 *
 * @since 2.99
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 */
class AutoCookieOperations extends SignedCookieOperations
{
    const KEY_OBJ = 'OBJ__';
    const KEY_ASSOC = 'ASSOC__';

    /**
     * Set a cookie
     *
     * @param string $key The cookie name
     * @param mixed $value The cookie contents
     * @param int $expires The timestamp of expiry.  If 0 it indicates a session cookie.
     * @return bool
     */
    public function set(string $key, $value, int $expires = 0) : bool
    {
        $is_empty = empty($value);
        if( is_object($value) ) {
            $tmp = json_encode($value);
            if( !$tmp ) throw new RuntimeException('Could not encode object to json');
            $value = self::KEY_OBJ.$tmp;
        }
        else if( is_array($value) && array_keys($value) !== range(0, count($value) - 1) ) {
            $value = self::KEY_ASSOC.json_encode($value);
        }
        else {
            $value = json_encode($value);
        }
        if( !$value && !$is_empty ) throw new RuntimeException('Attempt to store un-encodable data in a cookie');
        return parent::set($key, $value, $expires);
    }

    /**
     * Get a cookie.
     *
     * If the cookie exists, and appropriate info can be found, this method
     * will automatically decode the cookie from a string into a more complex data type.
     *
     * @param string $key
     * @return mixed
     */
    public function get(string $key)
    {
        $val = parent::get($key);
        if( $val ) {
            if( startswith($val,self::KEY_OBJ) ) {
                $val = substr($val,strlen(self::KEY_OBJ));
                $val = json_decode($val);
            }
            else if( startswith($val,self::KEY_ASSOC) ) {
                $val = substr($val,strlen(self::KEY_OBJ));
                $val = json_decode($val, TRUE);
            }
            else {
                $val = json_decode($val);
            }
            return $val;
        }
    }
} // class
