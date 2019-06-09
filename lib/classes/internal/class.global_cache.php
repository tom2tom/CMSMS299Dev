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
use CmsException;
use CMSMS\AppState;
use CMSMS\internal\global_cachable;
use UnexpectedValueException;

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

    /**
     * global cache singleton
     * @var cms_cache_handler
     */
    private static $instance;

    /**
     * intra-request cache
     * @var array
     */
    private static $_types = [];

    /**
     * values of members of $_types
     * @var array | null to trigger loading
     */
    private static $_data = null;

    /**
     * members of $_types which include changed value(s)
     * @var array
     */
    private static $_dirty = [];

    private function __construct() {}
    private function __clone() {}

    /**
     * Add a cached-data type to the intra-request cache
     *
     * @param global_cachable $obj
     */
    public static function add_cachable(global_cachable $obj)
    {
        $name = $obj->get_name();
        self::$_types[$name] = $obj;
    }

    /**
     * Remove from the intra-request cache the data of the specified type.
     * Hence reload when such cache-data are next wanted.
     *
     * @param string $type
     */
    public static function release($type)
    {
        if( isset(self::$_data[$type]) ) {
            unset(self::$_data[$type]);
        }
    }

    /**
     * Get all cached data in the specified type
     *
     * @param string $type
     * @return mixed
     * @throws UnexpectedValueException if $type is not a recorded/cachable type
     */
    public static function get($type)
    {
        //if( !isset(self::$_types[$type]) ) return; //DEBUG
        if( !isset(self::$_types[$type]) ) {
           throw new UnexpectedValueException('Unknown cache-data type: '.$type);
        }
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

    private static function _load()
    {
        $cache = self::_get_cache();
        $keys = array_keys(self::$_types);
        self::$_data = [];
        foreach( $keys as $key ) {
            $tmp = $cache->get($key,self::class);
            self::$_data[$key] = $tmp;
            unset($tmp);
        }
    }

    /**
     * Remove the specified type from the cache
     *
     * @param string $type
     */
    public static function clear($type)
    {
        self::_get_cache()->erase($type);
        unset(self::$_types[$type], self::$_data[$type], self::$_dirty[$type]);
    }

    /**
     * Remove everything from the cache
     */
    public static function clear_all()
    {
        self::_get_cache()->clear();
        self::$_types = [];
        self::$_data = null;
        self::$_dirty = [];
    }

    /**
     * [Re]cache the specified type using the specified data
     *
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
     */
    public static function save()
    {
        if( AppState::test_state(AppState::STATE_INSTALL) ) return;
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
     * @return cms_cache_handler object
     * @throws CmsException
     */
    private static function _get_cache() : cms_cache_handler
    {
        if( !self::$instance ) {
            $obj = new cms_cache_handler();
            $obj->connect([
             'auto_cleaning'=>1,
             'lifetime'=>self::TIMEOUT,
             'group'=>self::class,
            ]);
            self::$instance = $obj; //now we're connected
        }
        return self::$instance;
    }
} // class
