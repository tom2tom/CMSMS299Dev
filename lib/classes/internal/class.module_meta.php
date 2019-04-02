<?php
#class for managing module metadata
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
use ModuleOperations;

/**
 * A singleton class for managing meta data acquired from modules.
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
//    private static $_instance = null;
    //TODO namespaced global variables here
	/**
     * @ignore
     */
    private static $_data = [];

	/**
     * @ignore
     */
//    private function __construct() {}

	/**
     * @ignore
     */
//    private function __clone() {}

    /**
	 * Get an instance of this class.
	 * @deprecated since 2.3 use new module_meta()
     * @return object
     */
    public static function get_instance() : self
    {
//		if( !self::$_instance ) { self::$_instance = new self(); } return self::$_instance;
		return new self();
    }


    private function _load_cache()
    {
        global $CMS_INSTALL_PAGE;
        if( isset($CMS_INSTALL_PAGE) ) return;

        if( count(self::$_data) == 0 ) {
			$data = cms_cache_handler::get_instance()->get(__CLASS__);
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
        global $CMS_INSTALL_PAGE;
        if( isset($CMS_INSTALL_PAGE) ) return;

        cms_cache_handler::get_instance()->set(__CLASS__,self::$_data);
    }


    /**
     * List modules by their capabilities
     *
     * @param string capability name
     * @param array optional capability parameters
     * @param bool optional test value.
     * @return array of matching module names
     */
    public function module_list_by_capability($capability,$params = [],$returnvalue = TRUE)
    {
        if( empty($capability) ) return;

        $this->_load_cache();
        $sig = md5($capability.serialize($params));
        if( !isset(self::$_data['capability']) || !isset(self::$_data['capability'][$sig]) ) {
            debug_buffer('start building module capability list');
            if( !isset(self::$_data['capability']) ) self::$_data['capability'] = [];

            $modops = new ModuleOperations();
            $installed_modules = $modops->GetInstalledModules();
            $loaded_modules = $modops->GetLoadedModules();
            self::$_data['capability'][$sig] = [];
            foreach( $installed_modules as $onemodule ) {
                if( isset($loaded_modules[$onemodule]) ) {
                    $object = $loaded_modules[$onemodule];
                }
                else {
                    $object = $modops->get_module_instance($onemodule);
                }
                if( !$object ) continue;

                // now do the test
                $res = $object->HasCapability($capability,$params);
                self::$_data['capability'][$sig][$onemodule] = $res;
            }

            debug_buffer('Finished building module capability list');
            // store it.
            $this->_save_cache();
        }

        $res = null;
        if( self::$_data['capability'][$sig] ) {
            $res = [];
            foreach( self::$_data['capability'][$sig] as $key => $value ) {
                if( $value == $returnvalue ) $res[] = $key;
            }
        }

        return $res;
    }


    /**
     * Return a list of modules that have the supplied method.
     *
     * This method will query all available modules, check if the method name
	 * exists for each module, and if so, call the method and trap the return value.
     *
     * @param string method name
     * @param mixed  optional return value.
     * @return array of matching module names
     */
    public function module_list_by_method($method,$returnvalue = TRUE)
    {
        if( empty($method) ) return;

        $this->_load_cache();
        if( !isset(self::$_data['methods']) || !isset(self::$_data['methods'][$method]) ) {
            debug_buffer('Start building module method cache');
            if( !isset(self::$_data['methods']) ) self::$_data['methods'] = [];

            $modops = new ModuleOperations();
            $installed_modules = $modops->GetInstalledModules();
            $loaded_modules = $modops->GetLoadedModules();
            self::$_data['methods'][$method] = [];
            foreach( $installed_modules as $onemodule ) {
                if( isset($loaded_modules[$onemodule]) ) {
                    $object = $loaded_modules[$onemodule];
                }
                else {
                    $object = $modops->get_module_instance($onemodule);
                }
                if( !$object ) continue;
                if( !method_exists($object,$method) ) continue;

                // now do the test
                $res = $object->$method();
                self::$_data['methods'][$method][$onemodule] = $res;
            }

            // store it.
            debug_buffer('Finished building module method cache');
            $this->_save_cache();
        }

        $res = null;
        if( self::$_data['methods'][$method] ) {
            $res = [];
            foreach( self::$_data['methods'][$method] as $key => $value ) {
                if( $value == $returnvalue ) $res[] = $key;
            }
        }
        return $res;
    }
} // class
