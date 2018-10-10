<?php
# Mechanism for caching data in filessytem files
# Copyright (C) 2013-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# BUT withOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use cms_filecache_driver;
use CMSMS\internal\global_cachable;

/**
 * Class which enables data to be cached automatically (in file-system text files),
 * and fetched (or calculated) via a callback if the cache is too old, or
 * the cached value has been cleared or not yet been saved.
 *
 * @author      Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since       2.0
 * @ignore
 * @internal
 * @package     CMS
 */
class global_cache
{
    const TIMEOUT = 604800; //1 week
    private static $_types = [];
    private static $_dirty;
    private static $_cache;

    private function __construct() {}
    private function __clone() {}

    public static function add_cachable(global_cachable $obj)
    {
        $name = $obj->get_name();
        self::$_types[$name] = $obj;
    }

    public static function get($type)
    {
//      if( !isset(self::$_types[$type]) ) throw new \LogicException('Unknown type '.$type);
        if( !isset(self::$_types[$type]) ) return;
        if( !is_array(self::$_cache) ) self::_load();

        if( !isset(self::$_cache[$type]) ) {
            self::$_cache[$type] = self::$_types[$type]->fetch();
            self::$_dirty[$type] = 1;
            self::save();
        }
        return self::$_cache[$type];
    }

    public static function release($type)
    {
        if( isset(self::$_cache[$type]) ) unset(self::$_cache[$type]);
    }

    public static function clear($type)
    {
        // clear it from the cache
        $driver = self::_get_driver();
        $driver->erase($type);
        unset(self::$_cache[$type]);
    }

    public static function save()
    {
        global $CMS_INSTALL_PAGE;
        if( !empty($CMS_INSTALL_PAGE) ) return;
        $driver = self::_get_driver();
        $keys = array_keys(self::$_types);
        foreach( $keys as $key ) {
            if( !empty(self::$_dirty[$key]) && isset(self::$_cache[$key]) ) {
                $driver->set($key,self::$_cache[$key]);
                unset(self::$_dirty[$key]);
            }
        }
    }

    private static function _get_driver()
    {
        static $_driver = null;
        if( !$_driver ) {
            $_driver = new cms_filecache_driver(['lifetime'=>self::TIMEOUT,'autocleaning'=>1,'group'=>__CLASS__]);
        }
        return $_driver;
    }

    private static function _load()
    {
        $driver = self::_get_driver();
        $keys = array_keys(self::$_types);
        self::$_cache = [];
        foreach( $keys as $key ) {
            $tmp = $driver->get($key);
            self::$_cache[$key] = $tmp;
            unset($tmp);
        }
    }

    public static function clear_all()
    {
        self::_get_driver()->clear();
        self::$_cache = [];
    }

} // class
