<?php
#class to process simple-plugin (aka User Defined Tag) files
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
 * Singleton class to process simple (a.k.a user-defined) plugin files
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
	 * Get specified metadata for simple plugin named $name
	 * @param string $name Tag name
	 * @param string $key  Key of wanted data, or '*' for everything
	 * @return mixed null if the tag's metadata doesn't include $key or there's no metadata at all
	 */
	public function get_meta(string $name, string $key)
    {
		list($meta,$code) = $this->get($name);
		if( $key != '*' ) return $meta[$key] ?? null;
		return ($meta) ? $meta : null;
	}

    /**
     * Process lines of a tagfile into usable form
     * @ignore
     * @param array $lines Tagfile content split into individual lines
     * @param int   $r     Index of the member of $lines which is the start of actual PHP code
     * @return array 2-members, [0]=assoc array of metadata, [1]=PHP code multi-line string
     */
    protected function parse_content(array $lines, int $r) : array
    {
        $meta = [
         'license'=>'',
         'description'=>'',
         'parameters'=>[],
        ];
        $head = implode('', array_slice($lines, 0, $r-1, true));
        $ps = strpos($head, '<simpleplugin>');
        $pe = strpos($head, '</simpleplugin>', $ps);
        if( $ps !== false && $pe !== false ) {
            $xmlstr = substr($head,$ps, $pe - $ps + 15);
            $xml = simplexml_load_string($xmlstr);
            if( $xml !== false ) {
                $meta['license'] = htmlspecialchars_decode((string)$xml->license, ENT_XML1);
                $meta['description'] = htmlspecialchars_decode((string)$xml->description, ENT_XML1);
                if( ($arr = $xml->xpath('parameters')) ) {
                    foreach( $arr as $node ) {
                        $meta['parameters'][] = htmlspecialchars_decode((string)$node->parameter, ENT_XML1);
                    }
                }
            }
        }

        $code = implode('', array_slice($lines, $r, count($lines) - $r, true));
        return [$meta, $code];
    }

    /**
     * Return interpreted contents of the simple plugin named $name.
     *
     * @param string $name plugin name
     * @return 2-member array:
     *  [0] = assoc array of metadata,
     *  [1] = multi-line string of the tag's PHP code,
     * or else empty array upon error
     */
    public function get(string $name) : array
    {
        $fp = $this->plugin_filepath($name);
        if( is_file($fp) ) {
            $skips = '~^\s*(<\?php|#|//|</?[\w]+>)~'; //ignore lines starting like this
            $patn2 = '~/\*~'; //start of multi-line comment
            $patn3 = '~\*/~'; //end of multi-line comment
            $d = 0;
			$p = 0;
            $lines = file($fp);
            foreach( $lines as $r=>$l ) {
                if( preg_match($skips, $l) ) {
					if( strpos($l, '<parameter') !== false ) ++$p;
					if( strpos($l, '</parameter') !== false ) --$p; //maybe same line
                    continue;
                }
                elseif( preg_match($patn2, $l) ) {
                    ++$d;
                }
                elseif( preg_match($patn3, $l) ) {
                    if( --$d == 0 ) {
                        //too bad if code starts on the same line as the '*/' !
                        return $this->parse_content($lines, $r+1);
                    }
                    elseif( $d < 0 ) break; //format error
                }
                elseif( $p > 0 ) {
					continue;
				}
                else {
                    return $this->parse_content($lines, $r);
                }
            }
        }
        return [];
    }

    /**
     * Store contents of the simple plugin named $name, creating the file if not already existing.
     *
     * @param string $name
     * @param string $meta Assoc array of tag metadata,
     * @param string $code The tag's PHP code (assumed no trailing newline)
     * @return bool indicating success
     */
    public function save(string $name, string $meta, string $code) : bool
    {
        if( !$this->is_valid_plugin_name($name) ) {
            return false;
        }
        if( !$code ) { //TODO also some sort of validation
            return false;
        }

        $l = ( !empty($meta['license']) ) ? htmlspecialchars(trim($meta['license']), ENT_XML1) : '';
        $d = ( !empty($meta['description']) ) ? htmlspecialchars(trim($meta['license']), ENT_XML1) : '';
        $out = <<<EOS
<?php
/*
<simpleplugin>
<license>$l</license>
<description>$d</description>
<parameters>

EOS;
        if( isset($meta['parameters']) ) {
            foreach($meta['parameters'] as $l) {
                $l = trim($l);
                if( $l ) {
                    $d = htmlspecialchars($l, ENT_XML1);
                    $out .= <<<EOS
<parameter>$d</parameter>
EOS;
                }
            }
        }

        $out .= <<<'EOS'
</parameters>
</simpleplugin>
*/

EOS;
        $fp = $this->plugin_filepath($name);
        return @file_put_contents($fp, $out.$code."\n", LOCK_EX);
    }

    /**
     * List all simple-plugin (aka UDT) files in the assets/simple_plugins directory.
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
     * @param string $name plugin name
     * @return string absolute path for a plugin named $name
     */
    public function plugin_filepath(string $name) : string
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
        $fn = $this->plugin_filepath( $name );
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
     * Check whether a simple plugin corresponding to $name exists.
     *
     * @param $name plugin identifier (as used in tags)
     * @return callable (array) by which smarty will process the plugin
     * @throws InvalidArgumentException if the named plugin is not known
     */
    public function load_plugin(string $name) : array
    {
        $name = trim($name);
        if( !$this->is_valid_plugin_name( $name ) ) {
            throw new InvalidArgumentException("Invalid name passed to ".__METHOD__);
        }
        if( !isset($this->_loaded[$name]) ) {
            $fn = $this->plugin_filepath( $name );
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
        $fn = self::get_instance()->plugin_filepath( $name );
        if( !is_file($fn) ) throw new \RuntimeException('Could not find simple plugin named '.$name);

        // in-scope variables for the file code
        $params = $args[0];
        if( $params ) extract($params);
        $smarty = $template = $args[1];

        include_once $fn;
    }
} // class

