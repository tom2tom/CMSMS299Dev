<?php

namespace cms_installer;

use cms_installer\request;
use cms_installer\utils;
use Exception;
use FilesystemIterator;
use FilterIterator;
use Iterator;
use Phar;
use PharData;
use PHPArchive\Tar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Smarty_Autoloader;
use function cms_installer\CMSMS\endswith;
use function cms_installer\CMSMS\joinpath;
use function cms_installer\CMSMS\lang;
use function cms_installer\CMSMS\startswith;
use function file_put_contents;

class FilePatternFilter extends FilterIterator
{
    private $pattern;

    public function __construct(Iterator $iterator, $pattern)
    {
        parent::__construct($iterator);
        $this->pattern = $pattern;
    }

    public function accept()
    {
        $file = $this->getInnerIterator()->current();
        return preg_match($this->pattern, $file);
    }
}

abstract class installer_base
{
    const CONFIG_ROOT_URL = 'root_url';
    const CONTENTXML = ['assets','install','democontent.xml']; //path segments rel to top phardir
    const CONTENTFILESDIR = ['assets','install','uploadfiles']; //ditto

    private static $_instance;
    private $_archive;
    private $_assetdir;
    private $_config; //array or false
    private $_custom_destdir;
    private $_custom_tmpdir;
    private $_dest_name;
    private $_dest_schema;
    private $_dest_version;
    private $_destdir;
    private $_have_phar;
    private $_nls;
    private $_orig_error_level;
    private $_orig_tz;

    /**
     * @param string $configfile Optional filepath of a non-default 'config.ini'
     *  containing build settings. Default ''.
     * @throws Exception
     */
    public function __construct(string $configfile = '')
    {
        if (is_object(self::$_instance)) {
            throw new Exception('Cannot create another object of type installer_base');
        }
        self::$_instance = $this;

        // setup autoloading
        spl_autoload_register([installer_base::class,'autoload']);
        $p = dirname(__DIR__).DIRECTORY_SEPARATOR;
        require_once $p.'Smarty'.DIRECTORY_SEPARATOR.'Autoloader.php';
        Smarty_Autoloader::register();
//      require_once $p.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

        require_once $p.'compat.functions.php';
        require_once $p.'msg_functions.php';
        $p .= 'CMSMS'.DIRECTORY_SEPARATOR;
        require_once $p.'misc.functions.php';
        require_once $p.'accessor.functions.php';

        if (!$configfile) {
            $configfile = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'config.ini';
        }
        $this->_assetdir = dirname($configfile);
        $p = $this->_assetdir.DIRECTORY_SEPARATOR.'config.ini';
        $config = (is_file($p)) ? parse_ini_file($p,false,INI_SCANNER_TYPED) : [];

        // custom config data
        $p = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'config.ini';
        $xconfig = (is_file($p)) ? parse_ini_file($p,false,INI_SCANNER_TYPED) : false;
        if( $xconfig ) {
            foreach( $xconfig as $k =>$v) {
                if( $v ) {
                    $config[$k] = $v;
                }
                elseif( is_numeric($v) ) {
                    $config[$k] = $v + 0;
                }
            }
        }

        $this->_config = ($config) ? $config : false;

        // handle debug mode
        if( !empty($config['debug']) ) {
            @ini_set('display_errors',1);
            @error_reporting(E_ALL);
        }

        // find our archive, copy it... and rename it securely.
        // we do this because phar data cannot read from a .tar.gz file that is already embedded within a phar
        // (some environments)
        $p = $config['archive'] ?? 'data/data.tar.gz';
        $src_archive = dirname(__DIR__,2).DIRECTORY_SEPARATOR.$p;
        if( !is_file($src_archive) ) throw new Exception('Could not find installation archive at '.$src_archive);
        $src_md5 = md5_file($src_archive);
        $tmpdir = $this->get_tmpdir().DIRECTORY_SEPARATOR.'m'.md5(__FILE__.session_id());
        $dest_archive = $tmpdir.DIRECTORY_SEPARATOR.'f'.md5($src_archive.session_id()).'.tgz';

        for( $i = 0; $i < 2; $i++ ) {
            if( !is_file($dest_archive) ) {
                @mkdir($tmpdir,0771,TRUE);
                @copy($src_archive,$dest_archive);
            }
            $dest_md5 = md5_file($dest_archive);
            if( is_readable($dest_archive) && $src_md5 == $dest_md5 ) break;
            @unlink($dest_archive);
        }
        if( $i == 2 ) throw new Exception('Checksum of temporary archive does not match... copying/permissions problem');
        $this->_archive = $dest_archive;

        // for every request we're gonna make sure it's not cached.
        session_cache_limiter('private');

        // initialize the session
        $sess = session::get();
        $p = $sess[__CLASS__]; // trigger session start.

        // get the request
        $request = request::get();
        if( isset($request['clear']) ) {
            $sess->reset();
        }

        // get version details (version we are installing)
        // if not in the session, save them there.
        if( isset($sess[__CLASS__.'version']) ) {
            $ver = $sess[__CLASS__.'version'];
            $this->_dest_version = $ver['version'];
            $this->_dest_name = $ver['version_name'];
            $this->_dest_schema = $ver['schema_version'];
        }
        else {
            $verfile = dirname($src_archive).DIRECTORY_SEPARATOR.'version.php';
            if( !is_file($verfile) ) throw new Exception('Could not find version file');
            include_once $verfile;
            $ver = ['version' => CMS_VERSION, 'version_name' => CMS_VERSION_NAME, 'schema_version' => CMS_SCHEMA_VERSION];
            $sess[__CLASS__.'version'] = $ver;
            $this->_dest_version = CMS_VERSION;
            $this->_dest_name = CMS_VERSION_NAME;
            $this->_dest_schema = CMS_SCHEMA_VERSION;
        }

        $this->_have_phar = extension_loaded('phar');
    }

    public static function get_instance() : self
    {
        if (!is_object(self::$_instance)) {
            throw new Exception('There is no registered installer_base instance');
        }
        return self::$_instance;
    }

    public function get_name() : string
    {
        return __CLASS__;
    }

    public function get_tmpdir() : string
    {
        $config = self::$_instance->get_config();
        return $config['tmpdir'];
    }

    public static function get_assetsdir() : string
    {
        return self::$_instance->_assetdir;
    }

    public static function get_rootdir() : string
    {
        return dirname(__DIR__, 2);
    }

    public static function get_rooturl() : string
    {
        $config = self::$_instance->get_config();
        if ($config && isset($config[self::CONFIG_ROOT_URL])) {
            return $config[self::CONFIG_ROOT_URL];
        }

        $request = request::get();
        $dir = dirname($request['SCRIPT_FILENAME']);
        return $dir;
    }

    public static function clear_cache(bool $do_index_html = true)
    {
        $dir = $this->get_tmpdir();
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir)); //LEAVES_ONLY
        foreach ($iter as $file => $info) {
            if ($info->isFile()) {
                @unlink($info->getPathInfo());
            }
        }

        if ($do_index_html) {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir)); //LEAVES_ONLY
            foreach ($iter as $file => $info) {
                if ($info->isFile()) { //WHAT??
                    @touch($info->getPathInfo().'/index.html');
                }
            }
        }
    }

    public static function autoload($classname)
    {
        $o = ($classname[0] != '\\') ? 0 : 1;
        $p = strpos($classname, '\\', $o + 1);
        if ($p !== false) {
            $space = substr($classname, $o, $p - $o);
            if ($space == __NAMESPACE__ || $space == 'CMSMS') {
/*
lib/CMSMS/Database/class.Connection.php    CMSMS\Database\Connection
lib/CMSMS/Database/mysqli/class.ResultSet.php  CMSMS\Database\mysqli\Resultset
lib/CMSMS/classes/class.http_request.php  cms_installer\CMSMS   >> 'CMSMS 'then 'classes'
lib/CMSMS/classes/nls/class.nl_NL.nls.php cms_installer\CMSMS\nls (not autoloaded) >> 'CMSMS 'then 'classes'
lib/classes/class.utils.php               cms_installer          >> prepend 'classes'
lib/classes/wizard/class.wizard_step1.php cms_installer\wizard   >> prepend 'classes'
lib/classes/tests/class.boolean_test.php  cms_installer\tests    >> prepend 'classes'
lib/Smarty/*                              no namespace
lib/PHPArchive/*                          PHPArchive
*/
                $sroot = dirname(__DIR__).DIRECTORY_SEPARATOR; //top 'lib' dir
                $path = str_replace('\\', DIRECTORY_SEPARATOR, substr($classname, $p + 1));
                $classname = basename($path);
                $path = dirname($path);
                if ($path != '.') {
                     if (startswith($path, 'CMSMS')) {
                        $sroot .= 'CMSMS'.DIRECTORY_SEPARATOR.'classes'.substr($path,5);
                     } elseif ($space == 'CMSMS') {
                        $sroot .= 'CMSMS'.DIRECTORY_SEPARATOR.$path;
                     } else {
                        $sroot .= 'classes'.DIRECTORY_SEPARATOR.$path;
                     }
                     $sroot .= DIRECTORY_SEPARATOR;
                } else {
                    $sroot .= 'classes'.DIRECTORY_SEPARATOR;
                }
                foreach (['class.', ''] as $test) {
                    $fp = $sroot.$test.$classname.'.php';
                    if (is_file($fp)) {
                        require_once $fp;
                        return;
                    }
                }
            } elseif ($space == 'PHPArchive') { //files-archive classes
                $path = str_replace('\\', DIRECTORY_SEPARATOR, substr($classname, $o));
                $fp = dirname(__DIR__).DIRECTORY_SEPARATOR.$path.'.php';
                if (is_file($fp)) {
                    require_once $fp;
                    return;
                }
            }
        }
    }

    protected function set_config_defaults() : array
    {
        $tmp = utils::get_sys_tmpdir();
        $config = array_merge(
        [
            'debug' => false,
            'dest' => null,
            'lang' => null,
            'nobase' => false,
            'nofiles' => false,
            'timezone' => null,
            'tmpdir' => $tmp,
            'verbose' => false,
        ], $this->_config);

        $tmp = @date_default_timezone_get();
        if( !$tmp) $tmp = 'UTC';
        $this->_orig_tz = $config['timezone'] = $tmp;
        $tmp = realpath(getcwd());

        $adbg = $tmp.'/assets/install/initial.xml';
        $msg = (is_file($adbg)) ? 'XML EXISTS' : 'NO XML at '.$adbg;
        file_put_contents('/tmp/guiinstaller-cwd.txt', $msg); //DEBUG

        if( endswith($tmp, 'phar_installer') || endswith($tmp, 'installer') ) {
            $tmp = dirname($tmp);
        }
        $config['dest'] = $tmp;
        return $config;
    }

    protected function load_config() : array
    {
        // setup some defaults
        $config = $this->set_config_defaults();

        // override default config with config file(s)
        $tmp = realpath(getcwd());
        $config_file = joinpath($tmp,'assets','config.ini');
        if( is_file($config_file) && is_readable($config_file) ) {
            $list = parse_ini_file($config_file);
            if( $list ) {
                $config = array_merge($config,$list);
                if( isset($list['dest']) ) $this->_custom_destdir = $list['dest'];
            }
        }
        if( endswith($tmp, 'phar_installer') || endswith($tmp, 'installer') ) {
            $tmp = dirname($tmp);
        }
        $config_file = joinpath($tmp,'config.ini');
        if( is_file($config_file) && is_readable($config_file) ) {
            $list = parse_ini_file($config_file);
            if( $list ) {
                $config = array_merge($config,$list);
                if( isset($list['dest']) ) $this->_custom_destdir = $list['dest'];
            }
        }

        // override current config with url params
        $request = request::get();
        $list = [
            'debug',
            'dest',
            'destdir',
            'no_files',
            'nobase',
            'nofiles',
            'timezone',
            'TMPDIR',
            'tmpdir',
            'tz',
        ];
        foreach( $list as $key ) {
        if( !isset($request[$key]) ) continue;
            $val = $request[$key];
            switch( $key ) {
            case 'TMPDIR':
            case 'tmpdir':
                $config['tmpdir'] = trim($val);
                break;
            case 'timezone':
            case 'tz':
                $config['timezone'] = trim($val);
                break;
            case 'dest':
            case 'destdir':
                $this->_custom_destdir = $config['dest'] = trim($val);
                break;
            case 'debug':
                $config['debug'] = utils::to_bool($val);
                break;
            case 'nobase':
                $config['nobase'] = utils::to_bool($val);
                break;
            case 'nofiles':
            case 'no_files':
                $config['nofiles'] = utils::to_bool($val);
                break;
            }
        }
        return $config;
    }

    protected function check_config(array $config) : array
    {
        foreach( $config as $key => $val ) {
            switch( $key ) {
            case 'timezone':
                // do nothing
                break;
            case 'tmpdir':
                if( !$val ) {
                    // no tmpdir set... gotta find or create one.
                    $val = $this->get_tmpdir();
                }
                if( !is_dir($val) || !is_writable($val) ) {
                    // could not find a valid system temporary directory, or none specified. gotta make one
                    $dir = realpath(getcwd()).'/__m'.md5(session_id());
                    if( !@is_dir($dir) && !@mkdir($dir) ) throw new RuntimeException('Sorry, problem determining a temporary directory, non specified, and we could not create one.');
                    $txt = 'This is temporary directory created for installing CMSMS in punitively restrictive environments.  You may delete this directory and its files once installation is complete.';
                    if( !@file_put_contents($dir.'/__cmsms',$txt) ) throw new RuntimeException('We could not create a file in the temporary directory we just created (is safe mode on?).');
                    $this->set_config_val('tmpdir',$dir);
                    $this->_custom_tmpdir = $dir;
                    $val = $dir;
                }
                $config[$key] = $val;
                break;
            case 'dest':
                if( !is_dir($val) || !is_writable($val) ) {
                    throw new RuntimeException('Invalid config value for '.$key.' - not a directory, or not writable');
                }
                break;
            case 'debug':
            case 'nofiles':
            case 'nobase':
                // do nothing
                break;
            }
        }
        return $config;
    }

    public function get_config() : array
    {
        $sess = session::get();
        if( isset($sess['config']) ) {
            // already set once... so you must close and re-open the browser to reset it.
            return $sess['config'];
        }

        // gotta load the config, then cache it in the session
        $config = $this->load_config();
        $config = $this->check_config($config);

        $buildconfig = $this->_config ?? false;
        if( $buildconfig ) {
            $config += $buildconfig;
        }

        $sess['config'] = $config;
        return $config;
    }

    public function set_config_val(string $key,$val)
    {
        $config = $this->get_config();
        $config[trim($key)] = $val;

        $sess = session::get();
        $sess['config'] = $config;
    }

    public function get_orig_error_level() : int
    {
        return $this->_orig_error_level ?? 0;
    }

    public function get_orig_tz() : string
    {
        return $this->_orig_tz ?? '';
    }

    public function get_destdir() : string
    {
        $config = $this->get_config();
        return $config['dest'] ?? '';
    }

    public function set_destdir(string $destdir)
    {
        $this->set_config_val('dest',$destdir);
    }

    public function has_custom_destdir() : bool
    {
        $p1 = realpath(getcwd());
        $p2 = realpath($this->_custom_destdir);
        return ($p1 != $p2);
    }

    public function get_dest_version() : string { return $this->_dest_version ?? ''; }

    public function get_dest_name() : string { return $this->_dest_name ?? ''; }

    public function get_dest_schema() : string { return $this->_dest_schema ?? ''; }

    public function get_archive() : string { return $this->_archive ?? ''; }

    public function get_phar() : string { return $this->_have_phar ? Phar::running() : ''; }

    public function in_phar() : bool { return $this->_have_phar && Phar::running() != ''; }

    /**
     * Extract content from the source-files archive, with or without PHP's Phar extension
     * @param string $pattern Optional regex to be matched for returned item-paths
     * @return 2-member array: [0] = files iterator [1] = root path of each file
     */
    public function unpack_archive(string $pattern = '') : array
    {
        if( $this->_have_phar ) {
            $archive = $this->get_archive();
            if( !is_file($archive) ) {
                $archive = str_replace('\\','/',$archive);
                if( !is_file($archive) ) throw new Exception(lang('error_noarchive'));
            }

            $path = 'phar://'.$archive;  //contents are unpacked as descendants of the arhive
            $iter = new PharData($archive,
                  FilesystemIterator::KEY_AS_FILENAME |
                  FilesystemIterator::CURRENT_AS_PATHNAME |
                  FilesystemIterator::SKIP_DOTS |
                  FilesystemIterator::UNIX_PATHS);
        }
        else {
            $config = self::get_instance()->get_config();
            if (empty($config['archdir'])) {
                $path = tempnam($this->get_tmpdir(),'CMSfiles');
                unlink($path); //we will use this as a dir, not file
                $config['archdir'] = $path;
                $sess = session::get();
                $sess['config'] = $config;
            } else {
                $path = $config['archdir'];
            }
            if (!is_dir($path)) {
                //onetime unpack (it's slow)
                $archive = $this->get_archive();
                if( !is_file($archive) ) {
                    $archive = str_replace('\\','/',$archive);
                    if( !is_file($archive) ) throw new Exception(lang('error_noarchive'));
                }

                $adata = new Tar();
                $adata->open($archive);
                $adata->extract($path);
            }
            $iter = new RecursiveDirectoryIterator($path,
                  FilesystemIterator::KEY_AS_FILENAME |
                  FilesystemIterator::CURRENT_AS_PATHNAME |
                  FilesystemIterator::SKIP_DOTS |
                  FilesystemIterator::UNIX_PATHS);
        }

        $iter = new RecursiveIteratorIterator($iter,RecursiveIteratorIterator::SELF_FIRST);
        if ($pattern) {
            $iter = new FilePatternFilter($iter,$pattern);
        }

        return [$iter, $path];
    }

    public function get_nls() : array
    {
        if( is_array($this->_nls) ) return $this->_nls;

        list($iter,$tmpdir) = $this->unpack_archive('~[\\/]lib[\\/]nls[\\/].*\.nls\.php$~');

        $nls = [];
        foreach( $iter as $fn => $file ) {
            include $file; //populates $nls[]
        }

        if( !$nls ) throw new Exception(lang('error_nlsnotfound'));
        if( !asort($nls['language'],SORT_LOCALE_STRING) ) throw new Exception(lang('error_internal'));
        $this->_nls = $nls;
        return $nls;
    }

    public function get_language_list() : array
    {
        $this->get_nls();
        return $this->_nls['language'];
    }

    public function get_noncore_modules() : array
    {
        list($iter,$tmpdir) = $this->unpack_archive('~[\\/]assets[\\/]modules[\\/][^\\/]+$~');

        $names = [];
        foreach( $iter as $fn => $file ) {
            $names[] = $fn;
        }

        if( $names ) {
            $names = array_unique($names);
            natsort($names);
        }
        return $names;
    }

    public function cleanup()
    {
        if( $this->_custom_tmpdir ) {
            utils::rrmdir($this->_custom_tmpdir);
        }

        if ($this->_have_phar) {
            //TOD
        } else {
            $config = self::get_instance()->get_config();
            $tmp = $config['archdir'] ?? null;
            if( $tmp && is_dir($tmp) ) {
                utils::rrmdir($tmp);
            }
        }
    }

    abstract public function run();
} // class

/**
 * @throws Exception
 */
function get_app()
{
    return installer_base::get_instance();
}
