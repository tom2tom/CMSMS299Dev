<?php
/*
Singleton class of methods for managing module metadata
Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace CMSMS\internal;

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\ModuleOperations;
use CMSMS\SystemCache;
use ReflectionMethod;
use const CMS_DEPREC;
use function debug_buffer;

/**
 * A singleton class for managing metadata polled from modules.
 *
 * This class caches information from modules as needed.
 *
 * @package CMS
 * @internal
 * @final
 * @since 1.10
 * @author  Robert Campbell
 *
 */
final class module_meta
{
    /* *
     * @ignore
     */
    //private static $_instance = null;
    //TODO namespaced global variables here
    /**
     * null to trigger cache loading, or array, possibly having member(s)
     *  'capability', 'methods' (those being arrays) or possibly no member
     * @ignore
     */
    private $_data = null;

    /* *
     * @ignore
     */
    //private function __construct() {}

    /**
     * @ignore
     */
    private function __clone() {}

    /**
     * Get the singleton instance of this class
     * @deprecated since 2.99 instead use CMSMS\AppSingle::module_meta()
     * @return object
     */
    public static function get_instance() : self
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::module_meta()'));
        return AppSingle::module_meta();
    }

    //we do not use SysDataCache + Driver(s) to populate the cache on demand
    //cuz' not feasible to pre-check every module for every possible method
    private function _load_cache()
    {
        if( AppState::test_state(AppState::STATE_INSTALL) ) return;

        if( $this->_data === null ) {
            //TODO consider > 1 key for this group: 'capability', 'methods'
            $data = SystemCache::get_instance()->get(self::class,'module_meta');
            if( $data ) {
                $this->_data = $data;
            }
            else {
                $this->_data = [];
            }
        }
    }

    private function _save_cache()
    {
        if( AppState::test_state(AppState::STATE_INSTALL) ) return;
        //TODO consider > 1 key for this group: 'capability', 'methods'
        SystemCache::get_instance()->set(self::class,$this->_data,'module_meta');
    }

    /**
     * Zap the capabilities-and-methods cache
     */
    public function clear_cache()
    {
        $this->_data = null;
        //TODO consider > 1 key for this group: 'capability', 'methods'
        SystemCache::get_instance()->delete(self::class,'module_meta');
    }

    /**
     * Check whether the named module has ANY recorded capability
     * @since 2.99
     * @param string $modname The module name
     * @return bool
     */
    public function module_is_capable($modname)
    {
        $this->_load_cache();
        foreach( $this->_data['capability'] as $sig => $name ) {
            if( $name == $modname ) {
                if( $sig[$name] ) { return TRUE; }
            }
        }
        return FALSE;
    }

    /**
     * Return a list of installed modules which have, or don't have,
     * the specified capability
     *
     * @param string $capability The capability name
     * @param array  $params Optional capability parameters
     * @param bool   $match  Optional capability-status to match. Default true.
     * @return array of matching module names i.e. possibly empty
     */
    public function module_list_by_capability($capability,$params = [],$match = TRUE)
    {
        if( !$capability ) return [];

        $sig = Crypto::hash_string($capability.serialize($params));
        $this->_load_cache();
        if( !isset($this->_data['capability']) || !isset($this->_data['capability'][$sig]) ) {
            debug_buffer('start building module capability list');
            if( !isset($this->_data['capability']) ) $this->_data['capability'] = [];

            $modops = ModuleOperations::get_instance();
            $installed_modules = $modops->GetInstalledModules();
            $loaded_modules = $modops->GetLoadedModules();
            $this->_data['capability'][$sig] = [];
            foreach( $installed_modules as $modname ) {
                if( $loaded_modules && isset($loaded_modules[$modname]) ) {
                    $object = $loaded_modules[$modname];
                    $loaded = TRUE;
                }
                else {
                    $object = $modops->get_module_instance($modname);
                    $loaded = FALSE;
                }
                if( !$object ) continue;

                // now do the test
                $res = $object->HasCapability($capability,$params);
                $this->_data['capability'][$sig][$modname] = $res;
                if( !$loaded ) $object = null; //help the garbage collector
            }

            debug_buffer('Finished building module capability list');
            // store it
            $this->_save_cache();
        }

        $res = [];
        foreach( $this->_data['capability'][$sig] as $key => $value ) {
            if( $value == $match ) $res[] = $key;
        }
        return $res;
    }

    /**
     * Return a list of installed modules which have the specified method,
     * and that method returns the specified result.
     *
     * @param string Method name
     * @param mixed  Optional value to (non-strictly) compare with method
     *  return-value, and only report matches. May be
     *  ModuleOperations::ANY_RESULT for any value. Default true.
     * @return array of matching module names i.e. possibly empty
     */
    public function module_list_by_method($method,$returnvalue = TRUE)
    {
        if( empty($method) ) return [];

        $this->_load_cache();
        if( !isset($this->_data['methods']) || !isset($this->_data['methods'][$method]) ) {
            debug_buffer('Start building module method cache');
            if( !isset($this->_data['methods']) ) $this->_data['methods'] = [];

            $modops = ModuleOperations::get_instance();
            $installed_modules = $modops->GetInstalledModules();
            $loaded_modules = $modops->GetLoadedModules();
            $this->_data['methods'][$method] = [];
            foreach( $installed_modules as $modname ) {
                if( isset($loaded_modules[$modname]) ) {
                    $object = $loaded_modules[$modname];
                    $loaded = TRUE;
                }
                else {
                    $object = $modops->get_module_instance($modname);
                    $loaded = FALSE;
                }
                if( !$object ) continue;
                if( method_exists($object,$method) ) {
                    // check if this is just an inherited method
                    $reflector = new ReflectionMethod($object,$method);
                    if( $reflector->getDeclaringClass()->getName() == $modname ) { //or == get_class($object) if modules are namespaced
                        // do the test
                        $res = $object->$method();
                        $this->_data['methods'][$method][$modname] = $res;
                    }
                }
                if( !$loaded ) $object = null; //help the garbage collector
            }

            debug_buffer('Finished building module method cache');
            // store it
            $this->_save_cache();
        }

        $res = [];
        if( $this->_data['methods'][$method] ) {
            foreach( $this->_data['methods'][$method] as $key => $value ) {
                if( $returnvalue === ModuleOperations::ANY_RESULT || $returnvalue == $value ) $res[] = $key;
            }
        }
        return $res;
    }
} // class
