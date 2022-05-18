<?php
/*
Mechanism for automatic in-memory and in-global-cache caching of 'slow' system-data.
Copyright (C) 2013-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppState;
use CMSMS\DeprecationNotice;
use CMSMS\Lone;
use UnexpectedValueException;
use const CMS_DEPREC;

/**
 * Singleton class which handles caching of data retrieved e.g. from the
 * database, system-polling etc, and which enables such data to be fetched
 * (or calculated) on demand via a callback, if the respective cache is
 * empty (never filled or later cleared), or too old. Retrieved data are
 * stored in-memory during the current request, and backed-up in the
 * main system cache for inter-request persistence.
 *
 * @see also the LoadedDataType class, which defines how data are populated on demand
 * @see also the SystemCache class, which defines the main system cache
 * @since 3.0
 * @since 2.0 as CMSMS\internal\global_cache class
 * @package CMS
 */
class LoadedData
{
    protected const TIMEOUT = 2592000; // 30-day maximum data-lifetime in system cache

    /**
     * @ignore
     * System-cache keys-space for this class's data
     */
    protected const LOADS_SPACE = 'jx7NZpi0lt1'; // i.e. CacheDriver::get_cachespace(self::class)

    /**
     * @ignore
     * System-cache keys-subtype-separator
     */
    protected const SUB_SEP = ':::'; // regex-immune and unlikely to be in any (sub)type

//  private static $_instance = null;

    /**
     * @var SystemCache
     * CMSMS\SystemCache class singleton
     */
    protected $maincache;

    /**
     * @var array
     * Recorded loadable-types
     * Each member like 'typename' => (LoadedDataType-compatible) $obj
     * @ignore
     */
    protected $types = [];

    /**
     * @var array
     * Intra-request local cache: data populated from system cache
     * (i.e. indirectly from current or former members of $this->types)
     * and/or directly from current members of $this->types
     * Each array-member like 'typename' => type-data
     * @ignore
     */
    protected $data = [];

    /**
     * @var array
     * Members of $this->data which include changed value(s)
     * Each member like 'typename' => 1
     * @ignore
     */
    protected $dirty = [];

    /**
     * @var boolean
     * Whether $data has been populated from the system cache
     * @ignore
     */
    protected static $cache_loaded;

    /* *
     * @ignore
     * @private to prevent direct creation (even by Lone class)
     */
//  public function __construct() {} //TODO public iff wanted by Lone ?
    #[\ReturnTypeWillChange]
    private function __clone() {}

    /**
     * @ignore
     */
    #[\ReturnTypeWillChange]
    public function __destruct()
    {
        $this->save();
    }

    /**
     * Handle old-class/API calls corresponding to LoadedData::method()
     * @param string $name method name
     * @param array $args enumerated method argument(s)
     */
    #[\ReturnTypeWillChange]
    public static function __callStatic(string $name, array $args)
    {
        $obj = Lone::get('LoadedData');
        return $obj->$name(...$args);
    }

    /**
     * Get the singleton instance of this class
     * @since 3.0
     * @deprecated since 3.0 Instead use CMSMS\Lone::get('LoadedData')
     * @return LoadedData object
     */
    public static function get_instance()
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\Lone::get(\'LoadedData\')'));
        return Lone::get('LoadedData');
    }

    /**
     * Add a loaded-data type (including the mechanism to populate data
     * of that type). Any existing loader for the same type will be
     * replaced.
     * @since 3.0
     *
     * @param mixed $obj LoadedDataType or anything else with compatible API e.g. LoadedMetadataType
     */
    public function add_type($obj)
    {
        $name = $obj->get_name();
        $this->types[$name] = $obj;
    }

    /**
     * Add a loaded-data type
     * @deprecated since 3.0 Instead use LoadedData::add_type()
     *
     * @param mixed $obj LoadedDataType or anything else with compatible API e.g. LoadedMetadataType
     */
    public function add_cachable($obj)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\LoadedData::add_type()'));
        $this->add_type($obj);
    }

    /**
     * Report whether a data-type is present in the cache
     *
     * @param string $type Data-identifier
     * @param varargs $details since 3.0 Optional extra parameters UNUSED
     * @return bool
     */
    public function has(string $type, ...$details) : bool
    {
        if( isset($this->types[$type]) ) {
            return true;
        }
//        if( $this->get_main_cache()->has($type, self::LOADS_SPACE) ) {
//            return true; // TODO subsequent use will fail, unless auto loaded - HOW witout a type-object?
//        }
        return false;
    }

    /**
     * Get all loaded or loadable data for the specified type
     *
     * @param string $type Data-identifier
     * @param bool $force since 3.0 Optional flag signalling
     *  source-data are wanted (i.e. no system-cache). Default false.
     * @param varargs $details since 3.0 Optional extra parameters
     * @return mixed
     * @throws UnexpectedValueException if $type is not a recorded/cachable type
     */
    public function get(string $type, bool $force = false, ...$details)
    {
        if( !isset($this->types[$type]) ) {
//            if( !$this->get_main_cache()->has($type, self::LOADS_SPACE) ) {
                throw new UnexpectedValueException("Invalid cached-data type '$type'");
//            }
//            else {
                // TODO figure out how to get it witout a type-object
//            }
        }

        if( !$force ) {
            if( !self::$cache_loaded ) {
                // Migrate all (formerly loaded) data from system cache to in-memory cache
                $saved = $this->get_main_cache()->getall(self::LOADS_SPACE);
                if( $saved ) {
                    foreach( $saved as $name => $val ) {
                        if( !isset($this->data[$name]) ) {
                            $this->data[$name] = $val; // this might precede corresponding type-addition
                        }
                        else {
                            $this->dirty[$name] = 1;
                        }
                    }
                }
                self::$cache_loaded = true;
            }
            if( !isset($this->data[$type]) ) {
                $this->data[$type] = $this->types[$type]->fetch($force, ...$details);
                $this->dirty[$type] = 1;
            }
        }
        else {
            $this->data[$type] = $this->types[$type]->fetch($force, ...$details);
            $this->dirty[$type] = 1;
        }
        return $this->data[$type];
    }

    /**
     * Convenience combination of delete() then get() to re-populate
     * from the original data source. Nothing is returned.
     *
     * @param string $type Specific data-identifier or '*'
     *  If $type is '*' or falsy, all types will be refreshed
     * @param varargs $details since 3.0 Optional extra parameters
     */
    public function refresh(string $type, ...$details)
    {
        if( $type && $type !== '*' ) {
            $this->delete($type, ...$details);
            try {
                $this->get($type, true, ...$details);
            }
            catch (Throwable $t) {
                // nothing here
            }
        }
        else {
            $this->get_main_cache()->clear(self::LOADS_SPACE);
            // re-populate from source for the types we have now
            foreach( $this->types as $type => $obj ) {
                $uses = $obj->get_uses();
                if( $uses ) {
                    foreach( $uses as $args ) {
                        try {
                            $this->get($type, true, ...$args);
                        }
                        catch (Throwable $t) {
                            // nothing here
                        }
                    }
                }
                else {
                    try {
                        $this->get($type, true); // $details irrelevant for multi-type
                    }
                    catch (Throwable $t) {
                        // nothing here
                    }
                }
            }
        }
        // effectively, self::$cache load-status is unchanged
    }

    /**
     * Remove from the in-memory cache the data of the specified type.
     * Hence reload then-current data when the data-type is next wanted.
     *
     * @param mixed $type string | null Optional type-name.
     *  If $type is '*' or falsy or not supplied, all types will be released
     * @param varargs $details since 3.0 Optional extra parameters
     */
    public function release($type = null, ...$details)
    {
        if( $details ) { $type .= $this->get_subtype($details); }
        if( $type && $type !== '*' ) {
            unset($this->data[$type], $this->dirty[$type]);
        }
        else {
            $this->data = [];
            $this->dirty = [];
        }
        self::$cache_loaded = false;
    }

    /**
     * Remove the specified type from the in-memory and system caches
     * @since 3.0
     *
     * @param string $type
     * @param varargs $details since 3.0 Optional extra parameters
     */
    public function delete(string $type, ...$details)
    {
        if( $details ) { $type .= $this->get_subtype($details); }
        $this->get_main_cache()->delete($type, self::LOADS_SPACE);
        unset($this->data[$type], $this->dirty[$type]);
    }

    /**
     * Remove all loadable-data, or a named type, from the in-memory and system caches
     * @since 3.0
     * @param mixed $type string | null Optional type-name.
     *  If $type is '*' or falsy or not supplied, all types will be cleared
     * @param varargs $details since 3.0 Optional extra parameters
     */
    public function clear($type = null, ...$details)
    {
        if( $details ) { $type .= $this->get_subtype($details); }
        if( $type && $type !== '*' ) {
            $this->delete($type);
        }
        else {
            $this->get_main_cache()->clear(self::LOADS_SPACE);
            $this->data = [];
            $this->dirty = [];
        }
        // effectively, self::$cache load-status is unchanged
    }

    /**
     * Remove everything from the in-memory and system caches
     * @deprecated since 3.0 Instead use interface-compatible LoadedData::clear()
     */
    public function clear_all()
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\LoadedData::clear()'));
        $this->clear();
    }

    /**
     * Set|replace the data of the specified type in the in-memory cache.
     * The system cache will be updated correspondingly during object destruction.
     * @since 3.0
     *
     * @param string $type
     * @param mixed $val the data to be recorded
     * @param varargs $details since 3.0 Optional extra parameters
     */
    public function set(string $type, $val, ...$details)
    {
        if( $details ) { $type .= $this->get_subtype($details); }
        if( isset($this->types[$type]) ) {
            $this->data[$type] = $val;
            $this->dirty[$type] = 1;
        }
    }

    /**
     * @since 3.0
     * @deprecated since 3.0 Instead use interface-compatible LoadedData::set()
     *
     * @param string $type
     * @param mixed $val the data to be recorded
     * @param varargs $details since 3.0 Optional extra parameters
     */
    public function update(string $type, $val, ...$details)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\LoadedData::set()'));
        $this->set($type, $val, ...$details);
    }

    /**
     * Migrate 'dirty' data from in-memory cache to system cache
     * This is a shutdown/destruction method, not intended for external use
     * @internal
     * @ignore
     */
    public function save()
    {
        if( AppState::test(AppState::INSTALL) ) { return; }
        $cache = $this->get_main_cache();
        foreach( array_keys($this->types) as $type ) {
            if( !empty($this->dirty[$type]) ) {
                $cache->set_timed($type, $this->data[$type], self::TIMEOUT, self::LOADS_SPACE);
                unset($this->dirty[$type]);
            }
        }
    }

    /**
     * Return sub-type identifier suffix
     * @since 3.0
     *
     * @param array $details
     * @return string
     */
    protected function get_subtype(array $details) : string
    {
        switch( count($details) ) {
            case 1: // most-likely
                return self::SUB_SEP.$details[0];
            case 0: // should never happen
                return '';
            default: // prob. never happen
                return self::SUB_SEP.$this->hash_subtype($details);
        }
    }

    /**
     * Return hashed sub-type identifier suffix
     * @since 3.0
     *
     * @param array $details
     * @return string 10 alphanum bytes
     */
    protected function hash_subtype(array $details) : string
    {
        $value = hash('fnv1a64', json_encode($details,
            JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PARTIAL_OUTPUT_ON_ERROR
        ));
        return substr(base_convert($value, 16, 36), 0, 10);
    }

    /**
     * Get the singleton CMSMS\SystemCache object used here for backup
     * @ignore
     * @return SystemCache object
     */
    protected function get_main_cache() : SystemCache
    {
        if( empty($this->maincache) ) {
            $this->maincache = Lone::get('SystemCache');
        }
        return $this->maincache;
    }
} // class
