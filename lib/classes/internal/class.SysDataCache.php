<?php
# Mechanism for automatic in-memory caching of 'slow' data
# Copyright (C) 2013-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
 * Singleton class which handles caching of data retrieved e.g. from the
 * database, system-polling etc, and which enables such data to be fetched
 * (or calculated) on demand via a callback, if the respective cache is
 * empty (never filled or later cleared), or too old. Retrieved data are
 * stored in-memory during the current request, and in-effect backed-up
 * in the main system cache.
 *
 * @see also global_cachable class, which defines how data are retrieved on-damand
 * @see also cms_cache_handler class, which defines the main system cache
 * @author      Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since       2.0 as global_cache class
 * @since       2.9
 * @ignore
 * @internal
 * @package     CMS
 */
class SysDataCache
{
    const TIMEOUT = 604800; //1 week data lifetime in system cache

    /**
     * backup-class singleton
     * @var cms_cache_handler
     */
    private static $instance;

    /**
     * global_cachable objects, each of which is tailored to retrieve the data of its type
     * @var array
     */
    private static $_types = [];

    /**
     * in-memory cache: data populated by members of $_types
     * @var array | null to trigger loading
     */
    private static $_data = null;

    /**
     * members of $_types which include changed value(s)
     * @var array
     */
    private static $_dirty = [];

    /**
     * This is a singleton
     * @ignore
     */
    private function __construct() {}
    private function __clone() {}

    /**
     * Add a cached-data type (or more particularly, the mechanism to
     * retrieve data of that type).
     *
     * @param global_cachable $obj
     */
    public static function add_cachable(global_cachable $obj)
    {
        $name = $obj->get_name();
        self::$_types[$name] = $obj;
    }

    /**
     * Remove from the in-memory cache the data of the specified type.
     * Hence reload the data when the data-type is next wanted.
     *
     * @param string $type
     */
    public static function release(string $type)
    {
        if( isset(self::$_data[$type]) ) {
            unset(self::$_data[$type]);
        }
    }

    /**
     * Get all cached data recorded for the specified type
     *
     * @param string $type
     * @return mixed
     * @throws UnexpectedValueException if $type is not a recorded/cachable type
     */
    public static function get(string $type)
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

	/**
     * Migrate required data from system cache to in-memory cache
	 * @ignore
	 */
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
     * Remove the specified type from the in-memory and system caches
     *
     * @param string $type
     */
    public static function clear(string $type)
    {
        self::_get_cache()->erase($type);
        unset(self::$_types[$type], self::$_data[$type], self::$_dirty[$type]);
    }

    /**
     * Remove everything from the in-memory and system caches
     */
    public static function clear_all()
    {
        self::_get_cache()->clear();
        self::$_types = [];
        self::$_data = null;
        self::$_dirty = [];
    }

    /**
     * Set|replace the data of the specified type in the two caches
     *
     * @since 2.9
     * @param string $type
     * @param mixed $data the data to be stored
     */
    public static function update(string $type, $data)
    {
        if( isset(self::$_types[$type]) ) {
            self::$_data[$type] = $data;
            self::$_dirty[$type] = 1;
            self::save();
        }
    }

    /**
     * Migrate 'dirty' data from in-memory cache to system cache
     */
    public static function save()
    {
		if( AppState::test_state(AppState::STATE_INSTALL) ) { return; }
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
     * Get the singleton system-cache object used here for backup
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
