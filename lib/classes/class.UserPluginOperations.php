<?php
#class to process user-plugin (aka user-defined plugin) files
#Copyright (C) 2017-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is part of CMS Made Simple <http://cmsmadesimple.org>
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

namespace CMSMS;

use CmsApp;
use InvalidArgumentException;
use RuntimeException;
use const CMS_ASSETS_PATH;
use function file_put_contents;

/**
 * Singleton class to process user- (a.k.a user-defined) plugin files
 *
 * @final
 * @author      Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since       2.3
 * @package     CMS
 */
final class UserPluginOperations
{
    //TODO namespaced global variables here
    /**
     * @ignore
     */
//    private static $_instance = null;
    private static $_loaded = [];

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
	 * @deprecated since since 2.3 use new UserPluginOperations()
	 * @return self
	 */
    public static function get_instance() : self
    {
//	    if( !self::$_instance ) { self::$_instance = new self(); } return self::$_instance;
		return new self();
    }

    /**
     * Get specified metadata for user-plugin named $name
     * @param string $name Tag name
     * @param string $key  Key of wanted data, or '*' for everything
     * @return mixed null if the tag's metadata doesn't include $key or there's no metadata at all
     */
    public function get_meta_data(string $name, string $key)
    {
        list($meta) = $this->get($name, false);
        if( $meta ) {
            if( $key != '*' ) return $meta[$key] ?? null;
            return $meta;
        }
        return null;
    }

    /**
     * Return interpreted contents of the user-plugin named $name.
     *
     * @param string $name plugin name
     * @param bool   $withcode Optional flag, whether to also retrieve tag code. Default true
     * @return 2-member array:
     *  [0] = assoc array of metadata, with at least 'name', optionally also
     *   some/all of 'description','parameters','license'
     *  [1] = multi-line string of the tag's PHP code,
     * or else empty array upon error
     */
    public function get(string $name, $withcode = true) : array
    {
        $fp = $this->file_path($name);
        if( is_file($fp) ) {
            $cont = file_get_contents($fp);
            if( $cont ) {
                $ps = strpos($cont, '<metadata>');
                $pe = strpos($cont, '</metadata>', $ps);
                if( $ps !== false && $pe !== false ) {
                    $meta = ['name' => $name];
                    $xmlstr = substr($cont,$ps, $pe - $ps + 11);
                    $xml = simplexml_load_string($xmlstr);
                    if( $xml !== false ) {
                        $val = (string)$xml->description;
                        if( $val ) $meta['description'] = htmlspecialchars_decode($val, ENT_XML1);
                        $val = (string)$xml->parameters;
                        if( $val ) $meta['parameters'] = htmlspecialchars_decode($val, ENT_XML1);
                        $val = (string)$xml->license;
                        if( $val ) $meta['license'] = htmlspecialchars_decode($val, ENT_XML1);
                    }
                    if( $withcode ) {
                        $ps = strpos($cont, '*/', $pe);
                        $code = ( $ps !== false ) ? trim(substr($cont, $ps + 2), " \t\n\r") : '';
                    }
                    else {
                        $code = '';
                    }
                    return [$meta, $code];
                } else {
                    // malformed tag file !
                    if( $withcode ) {
                        // skip any introductory comment(s)
                        $skips = '~^\s*(<\?php|#|//)~'; //ignore lines starting like this
                        $patn2 = '~/\*~'; //start of multi-line comment
                        $patn3 = '~\*/~'; //end of multi-line comment
                        $d = 0;
                        $lines = preg_split('/$\R?^/m', $cont);
                        foreach( $lines as $r=>$l ) {
                            if( preg_match($skips, $l) ) {
                                continue;
                            }
                            elseif( preg_match($patn2, $l) ) {
                                ++$d;
                            }
                            elseif( preg_match($patn3, $l) ) {
                                if( --$d == 0 ) {
                                    //too bad if code starts on the same line as the '*/' !
                                    break;
                                }
                                elseif( $d < 0 ) $d = 0; //format error
                            }
                            else {
                                break;
                            }
                        }
                        $code = implode("\n", array_slice($lines, $r, count($lines) - $r, true));
                    }
                    else {
                        $code = '';
                    }
                    return [['name'=>$name], $code];
                }
            }
        }
        return [];
    }

    /**
     * Save user-plugin named $name. The file will be created or overwitten as appropriate.
     *
     * @param string $name Tag name
     * @param array  $meta Assoc array of tag metadata with at least 'name',
     *  optionally also any/all of 'description','parameters','license'
     * @param string $code The tag's PHP code
	 * @param Smarty $smarty Optional smarty object Default null (MUST be supplied during install/upgrade)
     * @return bool indicating success
     */
    public function save(string $name, array $meta, string $code, $smarty = null) : bool
    {
        if( !$this->is_valid_plugin_name($name) ) {
            return false;
        }

        $code = trim($code, " \t\n\r");
        if( $code ) {
	        global $CMS_INSTALL_PAGE;
            $code = filter_var($code, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_BACKTICK);
/* TODO sensible PHP validation eval() often crashes due to smarty parameters
			// variables which might be needed during content eval
            $params = [];
	        if (empty($CMS_INSTALL_PAGE)) {
				$smarty = CmsApp::get_instance()->GetSmarty();
			}
            $template = $smarty;

            ob_start();
            try {
                eval($code);
            } catch (ParseError $e) {
                ob_end_clean();
                return false;
            }
            ob_end_clean();
*/
        }
        else {
            return false;
        }

        $d = ( !empty($meta['description']) ) ?
            '<description>'."\n".htmlspecialchars(trim($meta['description']), ENT_XML1)."\n".'</description>':
            '<description></description>';
        $p = ( !empty($meta['parameters']) ) ?
            '<parameters>'."\n".htmlspecialchars(trim($meta['parameters']), ENT_XML1)."\n".'</parameters>':
            '<parameters></parameters>';
        $l = ( !empty($meta['license']) ) ?
            '<license>'."\n".htmlspecialchars(trim($meta['license']), ENT_XML1)."\n".'</license>':
            '<license></license>';

        $out = <<<EOS
<?php
/*
<metadata>
$l
$d
$p
</metadata>
*/

EOS;
        $fp = $this->file_path($name);
        return @file_put_contents($fp, $out.$code."\n", LOCK_EX);
    }

    /**
     * Delete user-plugin named $name.
     *
     * @param string $name plugin name
     * @return bool indicating success
     */
    public function delete(string $name) : bool
    {
        $fp = $this->file_path($name);
        return is_file($fp) && @unlink($fp);
    }

    /**
     * List all user-plugin (aka UDT) files in the assets/user_plugins directory.
     *
     * @return array
     */
    public function get_list() : array
    {
        $patn = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'user_plugins'.DIRECTORY_SEPARATOR.'*.php';
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
    public function file_path(string $name) : string
    {
        return CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'user_plugins'.DIRECTORY_SEPARATOR.$name.'.php';
    }

    /**
     * Check whether $name is acceptable for a user-plugin, and if so,
     * whether the corresponding file exists.
     *
     * @param $name plugin identifier (as used in tags)
     * @return bool
     * @throws InvalidArgumentException
    */
    public function plugin_exists(string $name) : bool
    {
        if( !$this->is_valid_plugin_name( $name ) ) throw new InvalidArgumentException('Invalid name passed to '.__METHOD__);
        $fp = $this->file_path( $name );
        return is_file($fp);
    }

    /**
     * Check whether $name is acceptable for a user-plugin.
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
     * Check whether a user-plugin corresponding to $name exists.
     *
     * @param $name plugin identifier (as used in tags)
     * @return callable (array) by which smarty will process the plugin
     * @throws InvalidArgumentException if the named plugin is not known
     */
    public function load_plugin(string $name) : array
    {
        $name = trim($name);
        if( !$this->is_valid_plugin_name( $name ) ) {
            throw new InvalidArgumentException('Invalid name passed to '.__METHOD__);
        }
        if( !isset(self::$_loaded[$name]) ) {
            $fp = $this->file_path( $name );
            if( !is_file($fp) ) {
                throw new RuntimeException('Could not find plugin file named '.$name);
            }
            $code = file_get_contents($fp);
            if( !preg_match('/^[\s\n]*<\?php/', $code) ) {
                throw new RuntimeException('Invalid file content for user-plugin named '.$name);
            }
            self::$_loaded[$name] = [__CLASS__, $name]; //fake callable to trigger __callStatic()
        }
        return self::$_loaded[$name];
    }

    /**
     * Get the appropriate user-plugin file for $name, and include it.
     *
     * @param string $name plugin identifier (as used in tags)
     * @param array $args [0]=parameters for plugin [1]=smarty object (Smarty_Internal_Template or wrapper)
     * @throws RuntimeException
     */
    public static function __callStatic(string $name, array $args)
    {
        $fp = self::get_instance()->file_path( $name );
        if( !is_file($fp) ) {
			throw new RuntimeException('Could not find plugin file named '.$name);
		}
        // in-scope variables for the file code
        $params = $args[0];
        if( $params ) extract($params);
        $smarty = $template = $args[1];

        return include_once $fp;
    }

    /**
     * If a user-plugin corresponding to $name exists, 'call' (i.e. include) it
     *
     * @param string $name plugin identifier (as used in tags)
     * @param array  $args Optional parameters provided by the caller
	 * @return mixed Whatever is returned by the included file, or null
     */
    public function call_plugin(string $name, array $args = [])
    {
        $fp = $this->file_path( $name );
        if( is_file($fp) ) {
            return include_once $fp;
        }
    }

    /**
     * If a user-plugin corresponding to $name exists, include it to handle an event
     * identified by $eventname and $originator.
     * Variables $gCms, $db, $config and (global) $smarty are in-scope for the inclusion.
	 *
     * @param string $name plugin identifier (as used in tags)
     * @param string $originator The name of the event originator, a module-name or 'Core'
     * @param string $eventname The name of the event
     * @param array  $params Event parameters provided by the originator
     * @return bool
     */
    public function DoEvent(string $name, string $originator, string $eventname, array &$params)
    {
        if ($originator && $eventname) {
            $fp = $this->file_path( $name );
            if( is_file($fp) ) {
                $gCms = CmsApp::get_instance();
                $db = $gCms->GetDb();
                $config = $gCms->GetConfig();
                $smarty = $gCms->GetSmarty();
                return include_once $fp;
            }
        }
    }
} // class
