<?php
# Mechanism for automatic in-memory and in-global-cache caching of 'slow' system-data.
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

namespace CMSMS;

use CmsException;
use CMSMS\AppState;
use CMSMS\DeprecationNotice;
use CMSMS\SysDataCacheDriver;
use UnexpectedValueException;
use const CMS_DEPREC;

/**
 * Singleton class which handles caching of data retrieved e.g. from the
 * database, system-polling etc, and which enables such data to be fetched
 * (or calculated) on demand via a callback, if the respective cache is empty
 * (never filled or later cleared), or too old. Retrieved data are stored
 * in-memory during the current request, and backed-up in the main system cache
 * for inter-request persistence.
 *
 * @see also SysDataCacheDriver class, which defines how data are retrieved on-damand
 * @see also SystemCache class, which defines the main system cache
 * @author      Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since       2.99
 * @since       2.0 as CMSMS\internal\global_cache class
 * @package     CMS
 */
class SysDataCache
{
    private const TIMEOUT = 604800; //1 week data-lifetime in system cache

    //private static $_instance = null;

    /**
     * CMSMS\SystemCache class singleton
     * @var SystemCache
     */
    protected $_maincache;

    /**
     * @var array SysDataCacheDriver objects
     * each of which is tailored to retrieve the data of its type
     */
    protected $_types = [];

    /**
     * @var array | null to trigger loading
     * in-memory cache: data populated by members of $this->_types
     */
    protected $_data = null;

    /**
     * @var array
     * members of $this->_types which include changed value(s)
     */
    protected $_dirty = [];

    /**
     * This is a singleton
     * @ignore
     */
//  private function __construct() {}
    private function __clone() {}

    /**
     * Handle old-class/API calls corresponding to SysDataCache::method()
     * @param string $name method name
     * @param array $args enumerated method argument(s)
     */
    public static function __callStatic($name, $args)
    {
        $obj = AppSingle::SysDataCache();
        return $obj->$name(...$args);
    }

    /**
     * Get the singleton instance of this class
     * @since 2.99
     * @deprecated since 2.99 use CMSMS\AppSingle::SysDataCache()
     * @return self
     */
    public static function get_instance() : self
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::SysDataCache()'));
        return AppSingle::SysDataCache();
    }

    /**
     * Add a cached-data type (or more particularly, the mechanism to
     * retrieve data of that type).
     *
     * @param SysDataCacheDriver $obj
     */
    public function add_cachable(SysDataCacheDriver $obj)
    {
        $name = $obj->get_name();
        $this->_types[$name] = $obj;
    }

    /**
     * Get all cached data recorded for the specified type
     *
     * @param string $type
     * @return mixed
     * @throws UnexpectedValueException if $type is not a recorded/cachable type
     */
    public function get(string $type)
    {
        //if( !isset($this->_types[$type]) ) return; //DEBUG
        if( !isset($this->_types[$type]) ) {
           throw new UnexpectedValueException('Unknown cache-data type: '.$type);
        }
        if( !is_array($this->_data) ) {
            $this->_load();
        }
        if( !isset($this->_data[$type]) ) {
            $this->_data[$type] = $this->_types[$type]->fetch();
            $this->_dirty[$type] = 1;
            $this->save();
        }
        return $this->_data[$type];
    }

    /**
     * Migrate required data from system cache to in-memory cache
     * @ignore
     */
    private function _load()
    {
        $cache = $this->get_main_cache();
        $keys = array_keys($this->_types);
        $this->_data = [];
        foreach( $keys as $key ) {
            $tmp = $cache->get($key,self::class);
            $this->_data[$key] = $tmp;
            unset($tmp);
        }
    }

    /**
     * Remove from the in-memory cache the data of the specified type.
     * Hence reload then-current data when the data-type is next wanted.
     *
     * @param string $type
     */
    public function release(string $type)
    {
        if( isset($this->_data[$type]) ) {
            unset($this->_data[$type]);
        }
    }

    /**
     * Remove the specified type from the in-memory and system caches
     * @since 2.99
     * @param string $type
     */
    public function delete(string $type)
    {
        $this->get_main_cache()->delete($type);
        unset($this->_types[$type], $this->_data[$type], $this->_dirty[$type]);
    }

    /**
     * Remove everything from the in-memory and system caches
     * @deprecated since 2.99 instead use interface-compatible delete(type)
     * to remove only that type
     */
    public function clear()
    {
        if (func_num_args() == 0) {
            $this->get_main_cache()->clear();
            $this->_types = [];
            $this->_data = null;
            $this->_dirty = [];
        } else {
            $args = func_get_args();
            $this->delete($args[0]);
        }
    }

    /**
     * @deprecated since 2.99 instead use interface-compatible clear()
     */
    public function clear_all()
    {
        $this->clear();
    }

    /**
     * Set|replace the data of the specified type in the two caches
     *
     * @since 2.99
     * @param string $type
     * @param mixed $data the data to be stored
     */
    public function set(string $type, $data)
    {
        if( isset($this->_types[$type]) ) {
            $this->_data[$type] = $data;
            $this->_dirty[$type] = 1;
            $this->save();
        }
    }

    /**
     * @since 2.99
     * @deprecated since 2.99 instead use interface-compatibile set()
     * @param string $type
     * @param mixed $data the data to be stored
     */
    public function update(string $type, $data)
    {
        $this->set($type, $data);
    }

    /**
     * Migrate 'dirty' data from in-memory cache to system cache
     */
    public function save()
    {
        if( AppState::test_state(AppState::STATE_INSTALL) ) { return; }
        $cache = $this->get_main_cache();
        $keys = array_keys($this->_types);
        foreach( $keys as $key ) {
            if( !empty($this->_dirty[$key]) && isset($this->_data[$key]) ) {
                $cache->set($key,$this->_data[$key]);
                unset($this->_dirty[$key]);
            }
        }
    }

    /**
     * Get the singleton CMSMS\SystemCache object used here for backup
     * @return SystemCache object
     * @throws Exception
     */
    private function get_main_cache() : SystemCache
    {
        if( empty($this->_maincache) ) {
            $obj = new SystemCache();
            $obj->connect([
             'auto_cleaning'=>1,
             'lifetime'=>self::TIMEOUT,
             'group'=>self::class,
            ]);
            $this->_maincache = $obj; //now we're connected
        }
        return $this->_maincache;
    }
} // class
