<?php
#Class for handling configuration data
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

//use CMSMS\AppSingle;
use CMSMS\AppState;

/**
 * A singleton class for interacting with CMSMS configuration data.
 *
 * This class uses the ArrayAccess interface to behave like a PHP array.
 *
 * @final
 * @since 1.9
 * @package CMS
 * @license GPL
 * @author Robert Campbell (calguy1000@cmsmadesimple.org)
 */
final class cms_config implements ArrayAccess
{
    /**
     * @ignore
     */
    const TYPE_STRING = 'S';

    /**
     * @ignore
     */
    const TYPE_INT = 'I';

    /**
     * @ignore
     */
    const TYPE_BOOL = 'B';

    const KNOWN = [
        'app_mode' => self::TYPE_BOOL, //since 2.9
        'admin_dir' => self::TYPE_STRING,
        'admin_encoding' => self::TYPE_STRING,
        'admin_url' => self::TYPE_STRING,
        'assets_dir' => self::TYPE_STRING,
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
        'developer_mode' => self::TYPE_BOOL, //since 2.9
        'image_uploads_path' => self::TYPE_STRING,
        'image_uploads_url' => self::TYPE_STRING,
        'locale' => self::TYPE_STRING,
        'max_upload_size' => self::TYPE_INT,
        'page_extension' => self::TYPE_STRING,
        'permissive_smarty' => self::TYPE_BOOL,
        'persist_db_conn' => self::TYPE_BOOL,
        'pr_root_url' => self::TYPE_STRING,
        'public_cache_location' => self::TYPE_STRING,
        'public_cache_url' => self::TYPE_STRING,
        'query_var' => self::TYPE_STRING,
        'root_path' => self::TYPE_STRING,
        'root_url' => self::TYPE_STRING,
        'secure_action_url' => self::TYPE_BOOL,
        'set_db_timezone' => self::TYPE_BOOL,
        'set_names' => self::TYPE_BOOL,
        'timezone' => self::TYPE_STRING,
        'tmp_cache_location' => self::TYPE_STRING,
        'tmp_templates_c_location' => self::TYPE_STRING,
        'uploads_path' => self::TYPE_STRING,
        'uploads_url' => self::TYPE_STRING,
        'url_rewriting' => self::TYPE_STRING,
        'usertags_dir' => self::TYPE_STRING, //since 2.9 UDTfiles
    ];

    /* *
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
    private function __construct() { /*$this->load_config();*/}

    /**
     * @ignore
     */
    private function __clone() {}

    /**
     * Retrieve the global config object, after instantiating it if necessary
     * @deprecated since 2.9 instead use CMSMS\AppSingle::cms_config()
     *
     * @return self
     */
    public static function get_instance() : self
    {
        if (!self::$_instance) {
            self::$_instance = new self();

            // populate from file
            self::$_instance->load_config();
        }
        return self::$_instance;
//      return AppSingle::cms_config();
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
     * @ignore
     */
    private function load_config()
    {
        $config = [];
        if (defined('CONFIG_FILE_LOCATION') && is_file(CONFIG_FILE_LOCATION)) {
            include CONFIG_FILE_LOCATION;
            foreach( $config as $key => &$value ) {
                if( isset(self::KNOWN[$key]) ) {
                    switch( self::KNOWN[$key] ) {
                    case self::TYPE_STRING:
                        switch( $key ) {
                        case 'assets_path':
                        case 'image_uploads_path':
                        case 'public_cache_location':
                        case 'root_path':
                        case 'tmp_cache_location':
                        case 'tmp_templates_c_location':
                        case 'uploads_path':
                            $value = rtrim($value,' /\\');
                            break;
                        case 'admin_url':
                        case 'assets_url':
                        case 'image_uploads_url':
                        case 'public_cache_url':
                        case 'root_url':
                        case 'uploads_url':
                            $value = rtrim($value,' /');
                            break;
                        case 'admin_dir':
                        case 'assets_dir':
                        case 'usertags_dir':
                            $value = strtr($value, ['\\' => '','/' => '',' ' => '_']);
                            break;
                        }
                        $value = trim($value);
                        break;

                    case self::TYPE_BOOL:
                        $value = cms_to_bool($value);
                        break;

                    case self::TYPE_INT:
                        $value = (int)$value;
                        break;
                    }
                }
            }
            unset($value);
            //we will always get these from INI
            unset($config['max_upload_size']);
            unset($config['upload_max_filesize']);
        }

        $this->_data = $config;
    }

    /**
     * @ignore
     * @internal
     * @access private
     */
    public function merge($newconfig)
    {
        if( !is_array($newconfig) ) return;

                if( !AppState::test_state(AppState::STATE_INSTALL) ) {
            trigger_error('Modification of config variables is deprecated',E_USER_ERROR);
            return;
        }

        $this->_data = array_merge($this->_data,$newconfig);
    }

    /**
     * interface method
     * @ignore
     */
    public function offsetExists($key)
    {
        return isset(self::KNOWN[$key]) || isset($this->_data[$key]);
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
            // deprecated, backwards compat only
            return true;

        case 'use_smarty_php_tags':
        case 'output_compression':
        case 'ignore_lazy_load':
            assert(empty(CMS_DEPREC), new DeprecationNotice($key.' property is no longer used'));
            // deprecated, backwards compat only
            return false;

        case 'default_upload_permission':
            $mask = octdec(cms_siteprefs::get('global_umask','0022'));
            $val = 0666 & ~$mask;
            return sprintf('%o',$val);

        case 'assume_mod_rewrite':
            // deprecated, backwards compat only
            assert(empty(CMS_DEPREC), new DeprecationNotice('property','url_rewriting'));
            return $this[''] == 'mod_rewrite';

        case 'internal_pretty_urls':
            // deprecated, backwards compat only
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
            stack_trace();
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
        case 'secure_action_url':
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
            if( !isset($_SERVER['HTTP_HOST']) ) return;
            $parts = parse_url($_SERVER['PHP_SELF']);
            $path = '';
            if( !empty($parts['path']) ) {
                $path = dirname($parts['path']);
                if( endswith($path,'install') ) {
                    $path = substr($path,0,strlen($path)-strlen('install')-1);
                }
                elseif( endswith($path,$this->offsetGet('admin_dir')) ) {
                    $path = substr($path,0,strlen($path)-strlen($this->offsetGet('admin_dir'))-1);
                }
                else {
                    $lseg = DIRECTORY_SEPARATOR.'lib';
                    if( strstr($path,$lseg) !== false ) {
                        while( strstr($path,$lseg) !== false ) {
                            $path = dirname($path);
                        }
                    }
                }
                while(endswith($path, DIRECTORY_SEPARATOR)) {
                    $path = substr($path,0,strlen($path)-1);
                }
                if( ($pos = strpos($path,DIRECTORY_SEPARATOR.'index.php')) !== false ) $path = substr($path,0,$pos);
            }
            //TODO generally support the websocket protocol
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
            //TODO generally support the websocket protocol
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
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),'uploads');
            return $this->_cache[$key];

        case 'uploads_url':
            $this->_cache[$key] = $this->offsetGet('root_url').'/uploads';
            return $this->_cache[$key];

        case 'ssl_uploads_url':
            // as of v2.3 this is just an alias for the uploads_url
            return $this->offsetGet('uploads_url');

        case 'image_uploads_path':
            $this->_cache[$key] = cms_join_path($this->offsetGet('uploads_path'),'images');
            return $this->_cache[$key];

        case 'image_uploads_url':
            $this->_cache[$key] = $this->offsetGet('uploads_url').'/images';
            return $this->_cache[$key];

        case 'ssl_image_uploads_url':
            // as of v2.3 this is just an alias for the image_uploads_url
            assert(empty(CMS_DEPREC), new DeprecationNotice('property','image_uploads_url'));
            return $this->offsetGet('image_uploads_url');

        case 'previews_path':
            return TMP_CACHE_LOCATION;

        case 'admin_dir':
            return 'admin';

        case 'app_mode':
        case 'debug':
        case 'developer_mode':
            return false;

        case 'timezone':
            return 'UTC';

        case 'assets_dir':
            return 'assets';

        case 'assets_path':
            $this->_cache[$key] = cms_join_path($this->OffsetGet('root_path'),$this->OffsetGet('assets_dir'));
            return $this->_cache[$key];

        case 'assets_url':
            $this->_cache[$key] = $this->offsetGet('root_url').'/'.$this->offsetGet('assets_dir');
            return $this->_cache[$key];

        case 'db_port':
            return '';

        case 'max_upload_size':
        case 'upload_max_filesize':
            $this->_cache[$key] = $this->get_upload_size();
            return $this->_cache[$key];

        case 'auto_alias_content':
            return true;

        case 'url_rewriting':
            return 'none';

        case 'page_extension':
            return '';

        case 'locale':
            return '';

        case 'default_encoding':
        case 'admin_encoding':
            return 'utf-8';

        case 'content_language':
            return 'xhtml';

        case 'admin_path':
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),$this->offsetGet('admin_dir'));
            return $this->_cache[$key];

        case 'admin_url':
            $this->_cache[$key] = $this->offsetGet('root_url').'/'.$this->offsetGet('admin_dir');
            return $this->_cache[$key];

        case 'css_path':
            return PUBLIC_CACHE_LOCATION.DIRECTORY_SEPARATOR;

        case 'css_url':
            return PUBLIC_CACHE_URL;

        case 'tmp_cache_location':
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),'tmp','cache');
            return $this->_cache[$key];

        case 'public_cache_location':
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),'tmp','cache','public');
            return $this->_cache[$key];

        case 'public_cache_url':
            $this->_cache[$key] = $this->offsetGet('root_url').'/tmp/cache/public';
            return $this->_cache[$key];

        case 'tmp_templates_c_location':
            $this->_cache[$key] = cms_join_path($this->offsetGet('root_path'),'tmp','templates_c');
            return $this->_cache[$key];

        case 'usertags_dir':
            return 'user_plugins';

        default:
            // not a mandatory key for the config.php file... and one we don't understand.
            return null;
        }
    }

    /**
     * interface method
     * @ignore
     */
    public function offsetSet($key,$value)
    {
        if( !AppState::test_state(AppState::STATE_INSTALL) ) {
            trigger_error('Modification of config variables is deprecated',E_USER_ERROR);
            return;
        }
        $this->_data[$key] = $value;
    }

    /**
     * interface method
     * @ignore
     */
    public function offsetUnset($key)
    {
        trigger_error('Unsetting config variable '.$key.' is invalid',E_USER_ERROR);
    }

    /**
     * @ignore
     * Retrieve the maximum file upload size (in bytes)
     */
    private function get_upload_size()
    {
        $maxFileSize = ini_get('upload_max_filesize');
        if (!is_numeric($maxFileSize)) {
            $l=strlen($maxFileSize);
            $i=0;$ss='';$x=0;
            while ($i < $l) {
                if (is_numeric($maxFileSize[$i])) {
                    $ss .= $maxFileSize[$i];
                }
                else {
                    if (strtolower($maxFileSize[$i]) == 'g') $x=1000000000;
                    if (strtolower($maxFileSize[$i]) == 'm') $x=1000000;
                    if (strtolower($maxFileSize[$i]) == 'k') $x=1000;
                }
                $i++;
            }
            $maxFileSize=$ss;
            if ($x >0) $maxFileSize = $ss * $x;
        }
        else {
            $maxFileSize = 1000000;
        }
        return $maxFileSize;
    }

    /**
     * @ignore
     */
    private function _printable_value(string $key,$value) : string
    {
        if( isset(self::KNOWN[$key]) ) $type = self::KNOWN[$key];
        else $type = self::TYPE_STRING;

        $str = '';
        switch( $type ) {
        case self::TYPE_STRING:
            $str = "'".$value."'";
            break;

        case self::TYPE_BOOL:
            $str = ($value)?'true':'false';
            break;

        case self::TYPE_INT:
            $str = (int)$value;
            break;
        }
        return $str;
    }

    /**
     * Save the current state of the config.php file in TMP_CACHE_LOCATION.
     * Any existing file is backed up before overwriting.
     *
     *
     * @param bool $verbose indicates whether comments should be stored in the config.php file.
     * @param string $filename An optional complete file specification.  If not specified the standard config file location will be used.
     */
    public function save(bool $verbose = true,string $filename = '')
    {
        if( !$filename ) $filename = CONFIG_FILE_LOCATION;

        // backup the original config.php file (just in case)
        if( is_file($filename) ) @copy($filename,cms_join_path(TMP_CACHE_LOCATION,basename($filename).time().'.bak'));

        $output = "<?php\n# CMS Made Simple configuration\n# Documentation: https://docs.cmsmadesimple.org/configuration/config-file/config-reference\n\n";
        // output header to the config file.

        foreach( $this->_data as $key => $value ) {
            $outvalue = $this->_printable_value($key,$value);
            $output .= "\$config['{$key}'] = $outvalue;\n";
        }

        // and write it.
        $fh = fopen($filename,'w');
        if( $fh ) {
            fwrite($fh,$output);
            fclose($fh);
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
        return $this->offsetGet('image_uploads_url');
    }
} // class
