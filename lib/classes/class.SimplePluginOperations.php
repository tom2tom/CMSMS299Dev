<?php
#class: CmsSimplePluginOperations
#This file is part of CMS Made Simple <http://cmsmadesimple.org>
#Copyright (C)2017-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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
     * @since 2.3
     * @return string[]|null
     */
    public function get_list()
    {
        $config = \cms_config::get_instance();
        $patn = $config['assets_path'].DIRECTORY_SEPARATOR.'simple_plugins'.DIRECTORY_SEPARATOR.'*.php';
        $files = glob($patn);

        $out = null;
        if( $files ) {
            foreach( $files as $file ) {
                $name = substr(basename($file),0,-4);
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
        $config = \cms_config::get_instance();
        $fn = $config['assets_path'].DIRECTORY_SEPARATOR.'simple_plugins'.DIRECTORY_SEPARATOR.$name.'.php';
        return $fn;
    }

    /**
     * Check whether $name is acceptable for a simple plugin, and if so,
     * whether the corresponding file exists.
     *
     * @since 2.3
     * @return bool, or may throw InvalidArgumentException
    */
    public function plugin_exists(string $name) : bool
    {
        if( !$this->is_valid_plugin_name( $name ) ) throw new \InvalidArgumentException("Invalid name passed to ".__METHOD__);
        $fn = $this->get_plugin_filename( $name );
        return is_file($fn);
    }

    /**
     * Check whether $name is acceptable for a simple plugin.
     *
     * @since 2.3
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
     * @since 2.3
     * @return string like: CMSMS\CmsSimplePluginOperations::the_name or
     *   may throw InvalidArgumentException
     */
    public function load_plugin(string $name) : string
    {
        $name = trim($name);
        if( !$this->is_valid_plugin_name( $name ) ) throw new \InvalidArgumentException("Invalid name passed to ".__METHOD__);
        if( !isset($this->_loaded[$name]) ) {
            $fn = $this->get_plugin_filename( $name );
            if( !is_file($fn) ) throw new \RuntimeException('Could not find simple plugin named '.$name);

            $code = trim(file_get_contents($fn));
            if( !startswith( $code, '<?php' ) ) throw new \RuntimeException('Invalid format for simple plugin '.$name);

            $this->_loaded[$name] = "\\CMSMS\\CmsSimplePluginOperations::$name";
        }
        return $this->_loaded[$name];
    }

    /**
     * Get the appropriate simple plugin file for $name, and include it.
     * May throw RuntimeException.
     *
     * @since 2.3
     */
    public static function __callStatic(string $name, array $args)
    {
        $fn = self::get_instance()->get_plugin_filename( $name );
        if( !is_file($fn) ) throw new \RuntimeException('Could not find simple plugin named '.$name);

        // variables for plugins to use in scope.
        $params = $args[0];
        $smarty = $args[1]; //CHECKME is Smarty_Internal_Template-object or derivative?

        include_once $fn;
    }
} // end of file
