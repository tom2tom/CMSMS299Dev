<?php
#class to process simple (aka user-defined) plugin files
#Copyright (C) 2017-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
#This file is part of CMS Made Simple <http://cmsmadesimple.org>
#
#This file is free software. You can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation, either version 2 of the License, or
#(at your option) any later version.
#
#This file is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY, without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this file. If not, write to the Free Software

namespace CMSMS;

use InvalidArgumentException;
use RuntimeException;
use const CMS_ASSETS_PATH;

/**
 * Class to process simple (a.k.a user-defined) plugin files
 *
 * @author      Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since       2.3
 * @package     CMS
 */
final class SimplePluginOperations
{
    /**
     * @ignore
     */
    private static $_instance = null;
    private $_loaded = [];

    /**
     * @ignore
     */
	private function __construct() {}

	/**
     * @ignore
     */
    private function __clone() {}

    final public static function &get_instance() : self
    {
        if( !self::$_instance ) self::$_instance = new self();
        return self::$_instance;
    }

    /**
     * List all simple (aka user-defined) plugins in the assets/simple_plugins directory.
     *
     * @return array
     */
    public function get_list() : array
    {
        $patn = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'simple_plugins'.DIRECTORY_SEPARATOR.'*.php';
        $files = glob($patn, GLOB_NOESCAPE);

        $out = [];
        if( $files ) {
            foreach( $files as $file ) {
                $name = basename($file, '.php');
                if( $this->is_valid_plugin_name( $name ) ) $out[] = $name;
            }
        }
        return $out;
    }

    /**
     * @ignore
     */
    protected function get_plugin_filename(string $name) : string
    {
        return CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'simple_plugins'.DIRECTORY_SEPARATOR.$name.'.php';
    }

    /**
     * Check whether $name is acceptable for a simple plugin, and if so,
     * whether the corresponding file exists.
     *
     * @param $name plugin identifier (as used in tags)
     * @return bool
     * @throws InvalidArgumentException
    */
    public function plugin_exists(string $name) : bool
    {
        if( !$this->is_valid_plugin_name( $name ) ) throw new InvalidArgumentException("Invalid name passed to ".__METHOD__);
        $fn = $this->get_plugin_filename( $name );
        return is_file($fn);
    }

    /**
     * Check whether $name is acceptable for a simple plugin.
     *
     * @param $name plugin identifier (as used in tags)
     * @return bool
     */
    public function is_valid_plugin_name(string $name) : bool
    {
        $name = trim($name);
        if( $name ) {
            return preg_match('<^[ a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$>',$name) != 0;
        }
        return false;
    }

    /**
     * Check and log whether a simple plugin corresponding to $name exists.
     *
     * @param $name plugin identifier (as used in tags)
     * @return callable by which smarty will process the plugin
     * @throws InvalidArgumentException
     */
    public function load_plugin(string $name) : array
    {
        $name = trim($name);
        if( !$this->is_valid_plugin_name( $name ) ) {
			throw new InvalidArgumentException("Invalid name passed to ".__METHOD__);
		}
        if( !isset($this->_loaded[$name]) ) {
            $fn = $this->get_plugin_filename( $name );
            if( !is_file($fn) ) {
				throw new RuntimeException('Could not find simple plugin named '.$name);
			}
            $code = file_get_contents($fn);
			if( !preg_match('/^[\s\n]*<\?php/', $code) ) {
                throw new RuntimeException('Invalid file content for simple plugin named '.$name);
            }
            $this->_loaded[$name] = [__CLASS__, $name]; //fake callable to trigger __callStatic()
        }
        return $this->_loaded[$name];
    }

    /**
     * Get the appropriate simple plugin file for $name, and include it.
     *
     * @param string $name plugin identifier (as used in tags)
     * @param array $args [0]=parameters for plugin [1]=smarty object (Smarty_Internal_Template or wrapper)
     * @throws RuntimeException
     */
    public static function __callStatic(string $name, array $args)
    {
        $fn = self::get_instance()->get_plugin_filename( $name );
        if( !is_file($fn) ) throw new \RuntimeException('Could not find simple plugin named '.$name);

        // in-scope variables for the file code
        $params = $args[0];
		if( $params ) extract($params);
        $smarty = $template = $args[1];

        include_once $fn;
    }
} // class

