<?php
#Class for handling system-configuration data
#Copyright (C) 2008-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace CMSMS;

use ArrayAccess;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\DeprecationNotice;
use RuntimeException;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use const CONFIG_FILE_LOCATION;
use const TMP_CACHE_LOCATION;
use function cms_join_path;
use function cms_to_bool;
use function stack_trace;
use function startswith;

/**
 * A singleton class for interacting with CMSMS configuration data.
 *
 * This class implements PHP's ArrayAccess interface, so properties
 * may be dealt with like array members.
 *
 * @final
 * @since 2.9
 * @since 1.9 as global-namespace cms_config
 * @package CMS
 * @license GPL
 * @author Robert Campbell (calguy1000@cmsmadesimple.org)
 */
final class AppConfig implements ArrayAccess
{
    /**
     * @ignore
     * Where to get advice on config settings
     */
    private const CMS_CONFIGHELP_URL = 'https://docs.cmsmadesimple.org/configuration/config-file/config-reference';

    /**
     * @ignore
     */
    private const TYPE_STRING = 'S';

    /**
     * @ignore
     */
    private const TYPE_INT = 'I';

    /**
     * @ignore
     */
    private const TYPE_BOOL = 'B';

    private const PROPS = [
        'admin_dir' => self::TYPE_STRING,
        'admin_encoding' => self::TYPE_STRING,
        'admin_url' => self::TYPE_STRING,
        'assets_dir' => self::TYPE_STRING, //deprecated since 2.9 see assets_{path,url}
        'assets_path' => self::TYPE_STRING,
        'assets_url' => self::TYPE_STRING,
        'auto_alias_content' => self::TYPE_BOOL,
        'content_language' => self::TYPE_STRING,
        'content_processing_mode' => self::TYPE_INT,
        'db_hostname' => self::TYPE_STRING,
        'db_name' => self::TYPE_STRING,
        'db_password' => self::TYPE_STRING,
        'db_port' => self::TYPE_INT,
        'db_prefix' => self::TYPE_STRING,
        'db_username' => self::TYPE_STRING,
        'dbms' => self::TYPE_STRING,
        'debug_to_log' => self::TYPE_BOOL,
        'debug' => self::TYPE_BOOL,
        'default_encoding' => self::TYPE_STRING,
        'default_upload_permission' => self::TYPE_STRING,
        'image_uploads_path' => self::TYPE_STRING,
        'image_uploads_url' => self::TYPE_STRING,
        'locale' => self::TYPE_STRING,
        'max_upload_size' => self::TYPE_INT,
        'obscure_urls' => self::TYPE_BOOL, //since 2.9 formerly secure_action_url
        'page_extension' => self::TYPE_STRING,
        'permissive_smarty' => self::TYPE_BOOL,
        'persist_db_conn' => self::TYPE_BOOL,
        'pr_root_url' => self::TYPE_STRING,
        'public_cache_location' => self::TYPE_STRING,
        'public_cache_url' => self::TYPE_STRING,
        'query_var' => self::TYPE_STRING,
        'root_path' => self::TYPE_STRING,
        'root_url' => self::TYPE_STRING,
        'set_db_timezone' => self::TYPE_BOOL,
        'set_names' => self::TYPE_BOOL,
        'simpletags_path' => self::TYPE_STRING, //since 2.9 UDTfiles
        'timezone' => self::TYPE_STRING,
        'tmp_cache_location' => self::TYPE_STRING,
        'tmp_templates_c_location' => self::TYPE_STRING,
        'uploads_path' => self::TYPE_STRING,
        'uploads_url' => self::TYPE_STRING,
        'url_rewriting' => self::TYPE_STRING,
    ];

    /**
     * ignore
     */
    private static $_instance = null;

    /**
     * ignore
     */
    private $_data = [];

    /**
     * ignore
     */
    private $_cache = [];

    /**
     * ignore
     */
    private function __construct() {}

    /**
     * @ignore
     */
    private function __clone() {}

    /**
     * Retrieve the singleton instance of this class.
     * This method is used during request-setup, when caching via the
     * AppSingle class might not yet be possible. Later, use
     * CMSMS\AppSingle::Config() instead of this method, to get the
     * (same) singleton.
     *
     * @return self
     */
    public static function get_instance() : self
    {
        if( !self::$_instance ) {
            self::$_instance = new self();
            // initialize settings, if possible
            self::$_instance->load();
        }
        return self::$_instance;
    }

    /**
     * interface method
     * @ignore
     */
    public function offsetExists($key)
    {
        return isset(self::PROPS[$key]) || isset($this->_data[$key]); //TODO de we want to allow 'foreign' parameters in there?
    }

    /**
     * interface method
     * @ignore
     */
    public function offsetGet($key)
    {
        // hardcoded config vars
        // usually old values valid in past versions.
        switch( $key ) {
        case 'use_adodb_lite':
        case 'use_hierarchy':
            assert(empty(CMS_DEPREC), new DeprecationNotice($key.' property is no longer used'));
            // deprecated, back-compat only
            return true;

        case 'use_smarty_php_tags':
        case 'output_compression':
        case 'ignore_lazy_load':
            assert(empty(CMS_DEPREC), new DeprecationNotice($key.' property is no longer used'));
            // deprecated, back-compat only
            return false;

        case 'default_upload_permission':
            $mask = octdec(AppParams::get('global_umask','0022'));
            $val = 0666 & ~$mask;
            return sprintf('%o',$val);

        case 'assume_mod_rewrite':
            // deprecated, back-compat only
            assert(empty(CMS_DEPREC), new DeprecationNotice('property','url_rewriting'));
            return $this[''] == 'mod_rewrite';

        case 'internal_pretty_urls':
            // deprecated, back-compat only
            return $this['url_rewriting'] == 'internal';
        }

        // from the config file.
        if( isset($this->_data[$key]) ) return $this->_data[$key];

        // cached, calculated values.
        if( isset($this->_cache[$key]) ) return $this->_cache[$key]; // this saves recursion and dynamic calculation every time.

        // not explicitly specified in the config file.
        switch( $key ) {
        case 'db_hostname':
        case 'db_username':
        case 'db_password':
        case 'db_name':
            // these guys have to be set
            if( !AppState::test_state(AppState::STATE_INSTALL) ) {
                stack_trace();
            }
            die('FATAL ERROR: Could not find database connection key "'.$key.'" in the config file');
            break;

        case 'dbms':
            return 'mysqli';

        case 'db_prefix':
            return CMS_DB_PREFIX;

        case 'query_var':
            return 'page';

        case 'permissive_smarty':
        case 'persist_db_conn':
        case 'obscure_urls':
            return false;

        case 'smart_urls':
        case 'set_db_timezone':
        case 'set_names':
            return true;

        case 'content_processing_mode':
            return 2;

        case 'root_path':
            $str = dirname(__DIR__, 2);
            $this->_cache[$key] = $str;
            return $str;

        case 'root_url':
            if( !isset($_SERVER['HTTP_HOST']) ) { return ''; }
            $parts = parse_url($_SERVER['PHP_SELF']);
            if( !empty($parts['path']) ) {
                $path = rtrim($parts['path'],' /');
//              if( ($pos = strrpos($path, '/')) !== false ) { $path = substr($path,0,$pos); }
                $str = $this->offsetGet('admin_dir');
                if( ($pos = stripos($path,'/'.$str.'/')) !== false ) { $path = substr($path,0,$pos); }
                if( ($pos = stripos($path,'/index.php')) !== false ) { $path = substr($path,0,$pos); }
                elseif( ($pos = stripos($path,'install/')) !== false ) {
                    if( ($pos2 = strrpos($path, '/', $pos-strlen($path))) !== false ) {
                        $path = substr($path,0,$pos2);
                    }
                    else {
                        $path = substr($path,0,$pos);
                    }
                }
                elseif( ($pos = stripos($path,'installer/')) !== false ) {
                    if( ($pos2 = strrpos($path, '/', $pos-strlen($path))) !== false ) {
                        $path = substr($path,0,$pos2);
                    }
                    else {
                        $path = substr($path,0,$pos);
                    }
                }
                elseif( ($pos = strpos($path,'/lib')) !== false ) {
                    do {
                        $path = substr($path,0,$pos);
                    } while ( ($pos = strpos($path,'/lib')) !== false );
                }
//              else {}
                $path = rtrim($path,' /');
            }
            else {
                $path = '';
            }
            //TODO generally support the websocket protocol 'wss' : 'ws'
            if(!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off') { //c.f. CmsApp::get_instance()->is_https_request() but CmsApp N/A at this stage
                $prefix = 'https://';
            }
            else {
                $prefix = 'http://';
            }
            $str = $prefix.$this->calculate_request_hostname().$path;
            $this->_cache[$key] = $str;
            return $str;

        case 'pr_root_url':
            $str = $this->offsetGet('root_url');
            //TODO generally support the websocket protocol 'wss' : 'ws'
            if( startswith($str,'http:') ) {
                $str = substr($str,5);
            }
            elseif( startswith($str,'https:') ) {
                $str = substr($str,6);
            }
            $this->_cache[$key] = $str;
            return $str;

        case 'ssl_url':
            // as of v2.3 this is just an alias for the root_url
            assert(empty(CMS_DEPREC), new DeprecationNotice('property','root_url'));
            return $this->offsetGet('root_url');

        case 'uploads_path':
            //TODO $this->url2path();
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),'uploads');
            return $this->_cache[$key];

        case 'uploads_url':
            //TODO $this->path2url();
            $this->_cache[$key] = $this->offsetGet('root_url').'/uploads';
            return $this->_cache[$key];

        case 'ssl_uploads_url':
            // From v 2.3 this is just an alias for the uploads_url
            return $this->offsetGet('uploads_url');

        case 'image_uploads_path':
            //TODO $this->url2path();
            $this->_cache[$key] = cms_join_path($this->offsetGet('uploads_path'),'images');
            return $this->_cache[$key];

        case 'image_uploads_url':
            //TODO $this->path2url();
            $this->_cache[$key] = $this->offsetGet('uploads_url').'/images';
            return $this->_cache[$key];

        case 'ssl_image_uploads_url':
            // From v 2.3 this is just an alias for the image_uploads_url
            assert(empty(CMS_DEPREC), new DeprecationNotice('property','image_uploads_url'));
            return $this->offsetGet('image_uploads_url');

        case 'previews_path':
            return TMP_CACHE_LOCATION;

        case 'admin_dir':
            return 'admin';

        case 'developer_mode';
            // deprecated from v 2.9 this is just an alias for develop_mode
            assert(empty(CMS_DEPREC), new DeprecationNotice('property', 'develop_mode'));
            return $this->offsetGet('develop_mode');
//        case 'app_mode':
//        case 'debug':
//        case 'develop_mode':
//            return false; c.f. default return null

        case 'assets_dir':
            assert(empty(CMS_DEPREC), new DeprecationNotice('property', 'assets_path'));
            return 'assets';

        case 'assets_path':
            //TODO $this->url2path();
            $this->_cache[$key] = cms_join_path($this->OffsetGet('root_path'),'assets');
            return $this->_cache[$key];

        case 'assets_url':
            //TODO $this->path2url();
            $this->_cache[$key] = $this->offsetGet('root_url').'/'.$this->offsetGet('assets_dir');
            return $this->_cache[$key];

        case 'db_port':
            if( isset($this->_cache[$key]) && is_numeric($this->_cache[$key]) ) {
                return (int)$this->_cache[$key];
            }
            return null;

        case 'max_upload_size': //deprecated since 2.9
            assert(empty(CMS_DEPREC), new DeprecationNotice('property', 'upload_max_filesize'));
            // no break here
        case 'upload_max_filesize':
            $this->_cache[$key] = $this->get_upload_size();
            return $this->_cache[$key];

        case 'auto_alias_content':
            return true;

        case 'url_rewriting':
            return 'none';

        case 'page_extension':
            return '';

        case 'timezone':
            return 'UTC';

        case 'locale':
            return '';

        case 'default_encoding':
        case 'admin_encoding':
            return 'utf-8';

        case 'content_language':
            return 'xhtml';

        case 'secure_action_url':
            return $this->offsetGet('obscure_urls');

        case 'admin_path':
            //TODO $this->url2path();
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),$this->offsetGet('admin_dir'));
            return $this->_cache[$key];

        case 'admin_url':
            //TODO $this->path2url();
            $this->_cache[$key] = $this->offsetGet('root_url').'/'.$this->offsetGet('admin_dir');
            return $this->_cache[$key];

        case 'css_path': // since 2.9 officially the same as tmp_cache_location, instead of relying on public == tmp
            //TODO $this->url2path();
            return $this->offsetGet('tmp_cache_location');

        case 'css_url':
            //TODO $this->path2url();
            $len = strlen($this->offsetGet('root_path'));
            $str = substr($this->offsetGet('tmp_cache_location'), $len);
            $this->_cache[$key] = $this->offsetGet('root_url') . strtr($str, '\\', '/');
            return $this->_cache[$key];

        case 'public_cache_location':
            //TODO $this->url2path();
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),'tmp','cache','public');
            return $this->_cache[$key];

        case 'public_cache_url':
            //TODO $this->path2url();
            $this->_cache[$key] = $this->offsetGet('root_url').'/tmp/cache/public';
            return $this->_cache[$key];

        case 'tmp_cache_location':
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),'tmp','cache');
            return $this->_cache[$key];

        case 'tmp_templates_c_location':
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),'tmp','templates_c');
            return $this->_cache[$key];

        case 'simpletags_path':
            $this->_cache[$key] = cms_join_path($this->OffsetGet('assets_path'),'simple_plugins');
            return $this->_cache[$key];

        default:
            return null; // a key that we can't autofill
        }
    }

    /**
     * interface method, use of which is deprecated since 2.9
     * instead supply install-time settings directly : Config::get_instance($config)
     * @ignore
     */
    public function offsetSet($key,$value)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('Direct setting of a config property is not supported'));
    }

    /**
     * interface method, use of which is deprecated since 2.9
     * instead supply install-time settings directly : Config::get_instance($config)
     * @ignore
     */
    public function offsetUnset($key)
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('Direct removal of a config property is not supported'));
    }

    /**
     * @ignore
     */
    private function url2path(string $urlkey, string $pathkey, string $default) : string
    {
        return 'TODO';
    }

    /**
     * @ignore
     */
    private function path2url2(string $pathkey, string $urlkey, string $default) : string
    {
        return 'TODO';
    }

    /**
     * @ignore
     */
    private function calculate_request_hostname()
    {
        if( $_SERVER['HTTP_HOST'] === $_SERVER['SERVER_NAME'] ) return $_SERVER['SERVER_NAME'];

        // $_SERVER['HTTP_HOST'] can be spoofed... so if a root_url is not specified
        // we determine if the requested host is in a whitelist.
        // if all else fails, we use $_SERVER['SERVER_NAME']
        $whitelist = (isset($this['host_whitelist'])) ? $this['host_whitelist'] : null;
        if( !$whitelist ) return $_SERVER['SERVER_NAME'];
        $requested = $_SERVER['HTTP_HOST'];

        $out = null;
        if( is_callable($whitelist) ) {
            $out = call_user_func($whitelist,$requested);
        }
        else if( is_array($whitelist) ) {
            // could use array_search here, but can't rely on the quality of the input (empty strings, whitespace etc).
            for( $i = 0, $n = count($whitelist); $i < $n; $i++ ) {
                $item = $whitelist[$i];
                if( !is_string($item) ) continue;
                if( !$item ) continue;
                if( strcasecmp($requested,$item) == 0 ) {
                    $out = $item;
                    break;
                }
            }
        }
        else if( is_string($whitelist) ) {
            $whitelist = explode(',',$whitelist);
            // could use array_search here, but can't rely on the quality of the input (empty strings, whitespace etc).
            for( $i = 0, $n = count($whitelist); $i < $n; $i++ ) {
                $item = $whitelist[$i];
                if( !is_string($item) ) continue;
                $item = strtolower(trim($item));
                if( !$item ) continue;
                if( strcasecmp($requested,$item) == 0 ) {
                    $out = $item;
                    break;
                }
            }
        }
        if( !$out ) {
            trigger_error('HTTP_HOST attack prevention: The host value of '.$requested.' is not whitelisted.  Using '.$_SERVER['SERVER_NAME']);
            $out = $_SERVER['SERVER_NAME'];
        }
        return $out;
    }

    /**
     * Return the maximum file upload size (in bytes)
     * @ignore
     */
    private function get_upload_size() : int
    {
        $maxFileSize = ini_get('upload_max_filesize');
        if( !is_numeric($maxFileSize) ) {
            $l = strlen($maxFileSize);
            $i = 0; $ss = ''; $x = 0;
            while( $i < $l ) {
                if( is_numeric($maxFileSize[$i]) ) {
                    $ss .= $maxFileSize[$i];
                }
                else {
                    $c = strtolower($maxFileSize[$i]);
                    if( $c == 'g' ) { $x = 1000000000; }
                    elseif( $c == 'm' ) { $x = 1000000; }
                    elseif( $c == 'k' ) { $x = 1000; }
                }
                $i++;
            }
            if( $x == 0 ) { return (int)$ss; }
            else { return ($ss * $x); }
        }
        elseif( $maxFileSize ) {
            return (int)maxFileSize;
        }
        else {
            return 1000000;
        }
    }

    /**
     * Return a config-file-friendly version of $value
     * @ignore
     * @param string $key
     * @param mixed $value int | bool | string | null
     * @return string
     */
    private function _printable_value(string $key, $value) : string
    {
        if( isset(self::PROPS[$key]) ) { $type = self::PROPS[$key]; }
        else { $type = self::TYPE_STRING; }

        switch( $type ) {
        case self::TYPE_STRING:
            return "'".$value."'";
        case self::TYPE_BOOL:
            return ( $value )?'true':'false';
        case self::TYPE_INT:
            if( $value !== null ) return (int)$value;
            // no break here
        default:
            return '';
        }
    }

    /**
     * Sanitize and [re]populate configuration properties
     * @access private
     * @ignore
     * @param array $config Optional config parameters. Default [].
     *  Ignored unless installer is running.
     */
    private function load(array $config = [])
    {
        if( defined('CONFIG_FILE_LOCATION') && is_file(CONFIG_FILE_LOCATION) ) {
            $config = [];
            include_once CONFIG_FILE_LOCATION;
        }
        elseif( !AppState::test_state(AppState::STATE_INSTALL) || !$config) {
            $this->_data = [];
            return;
        }

        foreach( $config as $key => &$value ) {
            if( isset(self::PROPS[$key]) ) {
                switch( self::PROPS[$key] ) {
                case self::TYPE_STRING:
                    switch( $key ) {
                    case 'root_path':
                        $value = strtr(rtrim($value,' /\\'), '/\\ ', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR.'_');
                        break 2;
                    case 'simpletags_path':
                    case 'tmp_cache_location':
                    case 'tmp_templates_c_location':
                        // root-relative, no leading separator
                        $value = strtr(trim($value,' /\\'), '/\\ ', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR.'_');
                        break 2;
                    case 'assets_path':
                    case 'image_uploads_path':
                    case 'public_cache_location':
                    case 'uploads_path':
                        // root-relative, no leading separator
                        $tmp = strtr(trim($value, ' /\\'), '\\ ', '/_');
                        $tmp = filter_var($tmp, FILTER_SANITIZE_URL);
                        $value = strtr($tmp, '/', DIRECTORY_SEPARATOR);
                        break 2;
                    case 'admin_url':
                    case 'assets_url':
                    case 'image_uploads_url':
                    case 'public_cache_url':
                    case 'root_url':
                    case 'uploads_url':
                        $value = filter_var(rtrim($value,' /'), FILTER_SANITIZE_URL);
                        break 2;
                    case 'admin_dir':
                    case 'assets_dir': // deprecated since 2.9 use assets_path
                        $value = strtr(trim($value, ' /\\'), ['\\' => '', '/' => '', ' ' => '_']);
                        break 2;
                    default:
                        $value = trim($value);
                        break 2;
                    }
                    // no break here

                case self::TYPE_BOOL:
                    $value = cms_to_bool($value);
                    break;

                case self::TYPE_INT:
                    switch( $key ) {
                    case 'db_port':
                        // port 0 is 'reserved' - probably also invalid in this context
                        if( !is_numeric($value) || $value < 0 ) {
                            unset($config[$key]);
                            break;
                        }
                    // no break here
                    default:
                        $value = (int)$value;
                        break;
                    }
                }
            }
            unset($value);
            // always retrieve these from php.ini
            unset($config['max_upload_size']); // deprecated, not an ini setting
            unset($config['upload_max_filesize']);
        }

        $this->_data = $config;
    }

    /**
     * If the installer is running, merge the supplied config settings
     * into the current config (if any)
     * @ignore
     * @internal
     * @param array $newconfig config parameters to be processed
     */
    public function merge(array $newconfig)
    {
        if( AppState::test_state(AppState::STATE_INSTALL) ) {
            $this->load($newconfig + ($this->_data ?? []));
        }
    }

    /**
     * Save a config.php file reflecting current config parameters.
     * Any existing file of the same name is backed up into the same
     * folder as $filename.
     * This method might be called when the installer is running, and
     * other CMSMS infrastructure is not accessible.
     *
     * @param bool $verbose Optional flag whether to include extra
     *  comments in the saved file. Default true.
     * @param string $filename Optional absolute filepath. Default ''.
     *  If empty, the standard config file path, or 'config.php' in
     *  the grandparent of this file's folder, will be used.
     * @throws RuntimeException if the folder to contain the file does not exist
     */
    public function save(bool $verbose = true, string $filename = '')
    {
        if( !$filename ) {
            $filename = constant('CONFIG_FILE_LOCATION');
            if( !$filename ) {
                $filename = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'config.php';
            }
        }
//      elseif( !preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $filename) ) { //not absolute
//          $filename = somebasepath.DIRECTORY_SEPARATOR.$filename;
//      }
        if( !is_dir(dirname($filename)) ) {
            throw new RuntimeException('Configuration file location is invalid');
        }
        // backup the current version, if any and possible
        if( is_file($filename) ) {
            $str = gmdate('-Ymd-His', @filemtime($filename)).'.bak';
            $path = dirname($filename).DIRECTORY_SEPARATOR.basename($filename, '.php').$str;
            if( @copy($filename, $path) ) {
                @chmod($path, 0440);
            }
            else {
                throw new RuntimeException('Configuration file backup failed');
            }
        }

        $output = "<?php\n";
        if( $verbose ) {
            $u = self::CMS_CONFIGHELP_URL;
            $output .= <<<EOS
// CMS Made Simple system configuration parameters
// Details are at $u

EOS;
        }
        $output .= "// PROTECT THIS FILE AGAINST INVALID INSPECTION OR CHANGE !\n";
        ksort ($this->_data);
        foreach( $this->_data as $key => $value ) {
            $outvalue = $this->_printable_value($key, $value);
            if( $outvalue !== '' ) {
                $output .= "\$config['$key'] = $outvalue;\n";
            }
        }
        // write the [new] version
        $fh = fopen($filename, 'w');
        if( $fh ) {
            fwrite($fh, $output);
            fclose($fh);
        }
        else {
            throw new RuntimeException('Failed to save configuration');
        }
    }

    /**
     * Returns either the http root url or the https root url depending upon the request mode.
     *
     * @deprecated since 2.3 use 'root_url'
     * @return string
     */
    public function smart_root_url() : string
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('property','root_url'));
        return $this->offsetGet('root_url');
    }

    /**
     * Returns either the http uploads url or the https uploads url depending upon the request mode.
     *
     * @deprecated since 2.3 use 'uploads_url'
     * @return string
     */
    public function smart_uploads_url() : string
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('property','uploads_url'));
        return $this->offsetGet('uploads_url');
    }

    /**
     * Returns either the http image uploads url or the https image uploads url depending upon the request mode.
     *
     * @deprecated since 2.3 use 'image_uploads_url'
     * @return string
     */
    public function smart_image_uploads_url() : string
    {
        assert(empty(CMS_DEPREC), new DeprecationNotice('property','image_uploads_url'));
        return $this->offsetGet('image_uploads_url');
    }
} // class
