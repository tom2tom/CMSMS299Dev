<?php
#Class for managing module metadata
#Copyright (C) 2010-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS\internal;

use cms_cache_handler;
use cms_utils;
use CMSMS\AppState;
use CMSMS\ModuleOperations;
use ReflectionMethod;
use function debug_buffer;

/**
 * A class for managing metadata acquired from modules.
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
    /**
     * @ignore
     */
    private static $_instance = null;
    //TODO namespaced global variables here
    /**
     * @ignore
     */
    private static $_data = null;

    /**
     * @ignore
     */
    private function __construct() {}

    /**
     * @ignore
     */
    private function __clone() {}

    /**
     * Get the instance of this class.
     * @return object
     */
    public static function get_instance() : self
    {
        if( !self::$_instance ) { self::$_instance = new self(); }
		return self::$_instance;
    }

    private function _load_cache()
    {
        if( AppState::test_state(AppState::STATE_INSTALL) ) return;

        if( self::$_data === null ) {
            $data = cms_cache_handler::get_instance()->get(__CLASS__,'module_meta');
            if( $data ) {
                self::$_data = $data;
            }
            else {
                self::$_data = [];
            }
        }
    }

    private function _save_cache()
    {
        if( AppState::test_state(AppState::STATE_INSTALL) ) return;

        cms_cache_handler::get_instance()->set(__CLASS__,self::$_data,'module_meta');
    }


    /**
     * List modules by their capabilities
     *
     * @param string $capability The capability name
     * @param array  $params Optional capability parameters
     * @param bool   $returnvalue Optional capability value to match. Default true.
     * @return array of matching module names, maybe empty
     */
    public function module_list_by_capability($capability,$params = [],$returnvalue = TRUE)
    {
        if( empty($capability) ) return;

        $this->_load_cache();
        $sig = cms_utils::hash_string($capability.serialize($params));
        if( !isset(self::$_data['capability']) || !isset(self::$_data['capability'][$sig]) ) {
            debug_buffer('start building module capability list');
            if( !isset(self::$_data['capability']) ) self::$_data['capability'] = [];

            $modops = ModuleOperations::get_instance();
            $installed_modules = $modops->GetInstalledModules();
            $loaded_modules = $modops->GetLoadedModules();
            self::$_data['capability'][$sig] = [];
            foreach( $installed_modules as $onemodule ) {
                if( isset($loaded_modules[$onemodule]) ) {
                    $object = $loaded_modules[$onemodule];
                    $loaded = TRUE;
                }
                else {
                    $object = $modops->get_module_instance($onemodule);
                    $loaded = FALSE;
                }
                if( !$object ) continue;

                // now do the test
                $res = $object->HasCapability($capability,$params);
                self::$_data['capability'][$sig][$onemodule] = $res;
                if( !$loaded ) $object = null; //help the garbage collector
            }

            debug_buffer('Finished building module capability list');
            // store it.
            $this->_save_cache();
        }

        $res = [];
        if( self::$_data['capability'][$sig] ) {
            foreach( self::$_data['capability'][$sig] as $key => $value ) {
                if( $value == $returnvalue ) $res[] = $key;
            }
        }
        return $res;
    }

    /**
     * Return a list of modules that have the specified method and it returns
     * the specified result.
     *
     * @param string Method name
     * @param mixed  Optional value to (non-strictly) compare with method return-value,
	 *  and only report matches. May be ModuleOperations::ANY_RESULT for any value.
	 *  Default true.
     * @return array of matching module names, maybe empty
     */
    public function module_list_by_method($method,$returnvalue = TRUE)
    {
        if( empty($method) ) return;

        $this->_load_cache();
        if( !isset(self::$_data['methods']) || !isset(self::$_data['methods'][$method]) ) {
            debug_buffer('Start building module method cache');
            if( !isset(self::$_data['methods']) ) self::$_data['methods'] = [];

            $modops = ModuleOperations::get_instance();
            $installed_modules = $modops->GetInstalledModules();
            $loaded_modules = $modops->GetLoadedModules();
            self::$_data['methods'][$method] = [];
            foreach( $installed_modules as $onemodule ) {
                if( isset($loaded_modules[$onemodule]) ) {
                    $object = $loaded_modules[$onemodule];
                    $loaded = TRUE;
                }
                else {
                    $object = $modops->get_module_instance($onemodule);
                    $loaded = FALSE;
                }
                if( !$object ) continue;
                if( method_exists($object,$method) ) {
					// check if this is just an inherited method
					$reflector = new ReflectionMethod($object,$method);
					if( $reflector->getDeclaringClass()->getName() == $onemodule ) { //or == get_class($object) if modules are namespaced
					    // do the test
					    $res = $object->$method();
					    self::$_data['methods'][$method][$onemodule] = $res;
					}
				}
                if( !$loaded ) $object = null; //help the garbage collector
            }

            debug_buffer('Finished building module method cache');
            // store it.
            $this->_save_cache();
        }

        $res = [];
        if( self::$_data['methods'][$method] ) {
            foreach( self::$_data['methods'][$method] as $key => $value ) {
                if( $returnvalue === ModuleOperations::ANY_RESULT || $returnvalue == $value ) $res[] = $key;
            }
        }
        return $res;
    }
} // class
