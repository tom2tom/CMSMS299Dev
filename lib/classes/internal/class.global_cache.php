<?php
# Mechanism for automatic data-caching
# Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use cms_cache_handler;
use CMSMS\internal\global_cachable;
use LogicException;

/**
 * Class which enables data to be cached automatically, and fetched
 * (or calculated) via a callback if the cache is too old, or the cached
 * data have been cleared or not yet saved.
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
    private static $_data;

    private function __construct() {}
    private function __clone() {}

    /**
     *
     * @param global_cachable $obj
     */
    public static function add_cachable(global_cachable $obj)
    {
        $name = $obj->get_name();
        self::$_types[$name] = $obj;
    }

    /**
     *
     * @param string $type
     * @return mixed
     * @throws LogicException if $type is not a recorded/cachable type
     */
    public static function get($type)
    {
//        if( !isset(self::$_types[$type]) ) return;
        if( !isset(self::$_types[$type]) ) throw new LogicException('Unknown type '.$type);
        if( !is_array(self::$_data) ) {
            self::_load();
        }
        if( !isset(self::$_data[$type]) ) {
            self::$_data[$type] = self::$_types[$type]->fetch();
            self::$_dirty[$type] = 1;
            self::save();
        }
        return self::$_data[$type];
    }

    /**
     *
     * @param string $type
     */
    public static function release($type)
    {
        if( isset(self::$_data[$type]) ) unset(self::$_data[$type]);
    }

    /**
     *
     * @param string $type
     */
    public static function clear($type)
    {
        // clear it from the cache
        $cache = self::_get_cache();
        $cache->erase($type);
        unset(self::$_data[$type]);
    }

    /**
     *
     */
    public static function clear_all()
    {
        self::_get_cache()->clear();
        self::$_data = [];
    }

    /**
     * @since 2.3
     * @param string $type
	 * @param mixed $data
     */
    public static function update($type, $data)
    {
        if( isset(self::$_types[$type]) ) {
            self::$_data[$type] = $data;
            self::$_dirty[$type] = 1;
            self::save();
        }
    }

    /**
     *
     * @global int $CMS_INSTALL_PAGE
     */
    public static function save()
    {
        global $CMS_INSTALL_PAGE;
        if( !empty($CMS_INSTALL_PAGE) ) return;
        $cache = self::_get_cache();
        $keys = array_keys(self::$_types);
        foreach( $keys as $key ) {
            if( !empty(self::$_dirty[$key]) && isset(self::$_data[$key]) ) {
                $cache->set($key,self::$_data[$key]);
                unset(self::$_dirty[$key]);
            }
        }
    }

    /**
     *
     * @staticvar cms_cache_handler $_handler
     * @return cms_cache_handler object
     * @throws CmsException
     */
    private static function _get_cache()
    {
        static $_handler = null; //global cache singleton

        if( !$_handler ) {
            $obj = new cms_cache_handler();
            $obj->connect([
             'auto_cleaning'=>1,
             'lifetime'=>self::TIMEOUT,
             'group'=>__CLASS__,
            ]);
            $_handler = $obj; //now we're connected
        }
        return $_handler;
    }

    private static function _load()
    {
        $cache = self::_get_cache();
        $keys = array_keys(self::$_types);
        self::$_data = [];
        foreach( $keys as $key ) {
            $tmp = $cache->get($key);
            self::$_data[$key] = $tmp;
            unset($tmp);
        }
    }
} // class
