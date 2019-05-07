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
use const CMS_SCHEMA_VERSION;
use const CMS_VERSION;
use const CMS_VERSION_NAME;
use function cms_installer\endswith;
use function cms_installer\joinpath;
use function cms_installer\lang;
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
    private $_have_phar;
    private $_nls;
    private $_orig_error_level;
    private $_orig_tz;

    /**
     * @param string $configfile Optional filepath of a non-default 'config.ini'
     *  containing build settings. Default ''.
     * @throws Exception
     */
    protected function __construct(string $configfile = '')
    {
        if (is_object(self::$_instance)) {
            throw new Exception('Cannot create another '.__CLASS__.' object');
        }
        self::$_instance = $this; //used during init()
        $this->init($configfile);
    }

    /**
     * Return the singleton object of this class
     * @return installer_base
     * @throws Exception
     */
    public static function get_instance() : self
    {
        if (!is_object(self::$_instance)) {
            throw new Exception('No instance of '.__CLASS__.' is registered');
        }
        return self::$_instance;
    }

    public static function autoload($classname)
    {
        $o = ($classname[0] != '\\') ? 0 : 1;
        $p = strpos($classname, '\\', $o + 1);
        if ($p !== false) {
            $space = substr($classname, $o, $p - $o);
            if ($space == __NAMESPACE__/* || $space == 'CMSMS'*/) {
/*
lib/classes/class.http_request.php        cms_installer        >> prepend 'classes'
lib/classes/nls/class.nl_NL.nls.php       cms_installer\nls (not autoloaded) >> 'classes'
lib/classes/class.utils.php               cms_installer        >> prepend 'classes'
lib/classes/wizard/class.wizard_step1.php cms_installer\wizard >> prepend 'classes'
lib/classes/tests/class.boolean_test.php  cms_installer\tests  >> prepend 'classes'
lib/classes/PHPArchive/*                  stet PHPArchive
lib/smarty/*                              no namespace
*/
                $sroot = dirname(__DIR__).DIRECTORY_SEPARATOR; //top 'lib' dir
                $path = str_replace('\\', DIRECTORY_SEPARATOR, substr($classname, $p + 1));
                $classname = basename($path);
                $path = dirname($path);
                if ($path != '.') {
                    $sroot .= 'classes'.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR;
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
                $fp = __DIR__.DIRECTORY_SEPARATOR.$path.'.php';
                if (is_file($fp)) {
                    require_once $fp;
                }
            }
        }
    }

    abstract public function run();

    /**
     * @throws Exception
     */
    protected function set_config_defaults() : array
    {
        $config = array_merge(
        [
            'debug' => false,
            'dest' => null,
            'lang' => null,
            'nobase' => false,
            'nofiles' => false,
            'timezone' => null,
            'tmpdir' => null,
            'verbose' => false,
        ],
            $this->_config
        );

        if ($config['tmpdir']) {
            if (is_dir($config['tmpdir']) && is_writeable($config['tmpdir'])) {
                $this->_custom_tmpdir = $config['tmpdir'];
            } else {
                throw new Exception('Invalid temporary/working directory specified');
            }
        } else {
            $tmp = utils::get_sys_tmpdir().DIRECTORY_SEPARATOR.chr(random_int(97,122)).bin2hex(random_bytes(10));
            if (mkdir($tmp, 0771, true)) {
                $this->_custom_tmpdir = $tmp;
                $config['tmpdir'] = $tmp;
            } else {
                throw new Exception('No temporary/working directory is available');
            }
        }

        $tmp = @date_default_timezone_get();
        if (!$tmp) {
            $tmp = 'UTC';
        }
        $this->_orig_tz = $config['timezone'] = $tmp;
        $tmp = realpath(getcwd());

        $adbg = $tmp.'/assets/install/initial.xml';
        $msg = (is_file($adbg)) ? 'XML EXISTS' : 'NO XML at '.$adbg;
        file_put_contents('/tmp/guiinstaller-cwd.txt', $msg); //DEBUG

        if (endswith($tmp, 'phar_installer') || endswith($tmp, 'installer')) {
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
        $config_file = joinpath($tmp, 'assets', 'config.ini');
        if (is_file($config_file) && is_readable($config_file)) {
            $list = parse_ini_file($config_file);
            if ($list) {
                $config = array_merge($config, $list);
                if (isset($list['dest'])) {
                    $this->_custom_destdir = $list['dest'];
                }
            }
        }
        if (endswith($tmp, 'phar_installer') || endswith($tmp, 'installer')) {
            $tmp = dirname($tmp);
        }
        $config_file = joinpath($tmp, 'config.ini');
        if (is_file($config_file) && is_readable($config_file)) {
            $list = parse_ini_file($config_file);
            if ($list) {
                $config = array_merge($config, $list);
                if (isset($list['dest'])) {
                    $this->_custom_destdir = $list['dest'];
                }
            }
        }

        // override current config with url params
        $request = request::get_instance();
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
        foreach ($list as $key) {
            if (!isset($request[$key])) {
                continue;
            }
            $val = $request[$key];
            switch ($key) {
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
        foreach ($config as $key => $val) {
            switch ($key) {
            case 'timezone':
                // do nothing
                break;
            case 'tmpdir':
                if (!$val) {
                    // no tmpdir set... gotta find or create one.
                    $val = $this->get_tmpdir();
                }
                if (!is_dir($val) || !is_writable($val)) {
                    // could not find a valid system temporary directory, or none specified. gotta make one
                    $dir = realpath(getcwd()).'/__m'.md5(session_id());
                    if (!@is_dir($dir) && !@mkdir($dir)) {
                        throw new RuntimeException('Sorry, problem determining a temporary directory, non specified, and we could not create one.');
                    }
                    $txt = 'This is temporary directory created for installing CMSMS in punitively restrictive environments.  You may delete this directory and its files once installation is complete.';
                    if (!@file_put_contents($dir.'/__cmsms', $txt)) {
                        throw new RuntimeException('We could not create a file in the temporary directory we just created (is safe mode on?).');
                    }
                    $this->set_config_val('tmpdir', $dir);
                    $this->_custom_tmpdir = $dir;
                    $val = $dir;
                }
                $config[$key] = $val;
                break;
            case 'dest':
                if (!is_dir($val) || !is_writable($val)) {
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
        $sess = session::get_instance();
        if (isset($sess['config'])) {
            // already set once... so you must close and re-open the browser to reset it.
            return $sess['config'];
        }

        // gotta load the config, then cache it in the session
        $config = $this->load_config();
        $config = $this->check_config($config);

        $buildconfig = $this->_config ?? false;
        if ($buildconfig) {
            $config += $buildconfig;
        }

        $sess['config'] = $config;
        return $config;
    }

    public function set_config_val(string $key, $val)
    {
        $config = $this->get_config();
        $config[trim($key)] = $val;

        $sess = session::get_instance();
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

    public function get_name() : string
    {
        return __CLASS__;
    }

    public function get_tmpdir() : string
    {
        $config = self::$_instance->get_config();
        return $config['tmpdir'];
    }

    public function get_destdir() : string
    {
        $config = $this->get_config();
        return $config['dest'] ?? '';
    }

    public function get_assetsdir() : string
    {
        return $this->_assetdir;
    }

    public function get_rootdir() : string
    {
        return dirname(__DIR__, 2);
    }

    public function get_rooturl() : string
    {
        $config = self::$_instance->get_config();
        if ($config && isset($config[self::CONFIG_ROOT_URL])) {
            return $config[self::CONFIG_ROOT_URL];
        }

        $request = request::get_instance();
        $dir = dirname($request['SCRIPT_FILENAME']);
        return $dir;
    }

    public function set_destdir(string $destdir)
    {
        $this->set_config_val('dest', $destdir);
    }

    public function has_custom_destdir() : bool
    {
        $p1 = realpath(getcwd());
        $p2 = realpath($this->_custom_destdir);
        return ($p1 != $p2);
    }

    public function get_dest_version() : string
    {
        return $this->_dest_version ?? '';
    }

    public function get_dest_name() : string
    {
        return $this->_dest_name ?? '';
    }

    public function get_dest_schema() : string
    {
        return $this->_dest_schema ?? '';
    }

    public function get_phar() : string
    {
        return $this->_have_phar ? Phar::running() : '';
    }

    public function in_phar() : bool
    {
        return $this->_have_phar && Phar::running() != '';
    }

    public function get_archive() : string
    {
        return $this->_archive ?? '';
    }

    /**
     * Setup to interrogate the source-files archive, with or without PHP's Phar extension
     * @param string $pattern Optional regex to be matched for returned item-paths
     * @return 2-member array: [0] = files iterator [1] = root path of each file
     */
    public function start_archive_scan(string $pattern = '') : array
    {
        if ($this->_have_phar) {
            $archive = $this->get_archive();
            if (!is_file($archive)) {
                $archive = str_replace('\\', '/', $archive);
                if (!is_file($archive)) {
                    throw new Exception(lang('error_noarchive'));
                }
            }

            $path = 'phar://'.$archive;  //contents are unpacked as descendents of the archive
            $iter = new PharData(
                $archive,
                FilesystemIterator::KEY_AS_FILENAME |
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS |
                FilesystemIterator::UNIX_PATHS
            );
        } else {
            $config = self::get_instance()->get_config();
            if (empty($config['archdir'])) {
                $path = tempnam($this->get_tmpdir(), 'CMSfiles');
                unlink($path); //we will use this as a dir, not file
                $config['archdir'] = $path;
                $sess = session::get_instance();
                $sess['config'] = $config;
            } else {
                $path = $config['archdir'];
            }
            if (!is_dir($path)) {
                //onetime unpack (it's slow)
                $archive = $this->get_archive();
                if (!is_file($archive)) {
                    $archive = str_replace('\\', '/', $archive);
                    if (!is_file($archive)) {
                        throw new Exception(lang('error_noarchive'));
                    }
                }

                $adata = new Tar();
                $adata->open($archive);
                $adata->extract($path);
            }
            $iter = new RecursiveDirectoryIterator(
                $path,
                  FilesystemIterator::KEY_AS_FILENAME |
                  FilesystemIterator::CURRENT_AS_PATHNAME |
                  FilesystemIterator::SKIP_DOTS |
                  FilesystemIterator::UNIX_PATHS
            );
        }

        $iter = new RecursiveIteratorIterator($iter, RecursiveIteratorIterator::SELF_FIRST);
        if ($pattern) {
            $iter = new FilePatternFilter($iter, $pattern);
        }

        return [$iter, $path];
    }

    /**
     * Get the translations included in the source-files archive
     * (as distinct from the translations usable in this installer)
     *
     * @return array
     * @throws Exception
     */
    public function get_nls() : array
    {
        if (is_array($this->_nls)) {
            return $this->_nls;
        }

        list($iter, $tmpdir) = $this->start_archive_scan('~[\\/]lib[\\/]nls[\\/].+\.nls\.php$~');

        $nls = [];
        foreach ($iter as $fn => $file) {
            include $file; //populates $nls[]
        }

        if (!$nls) {
            throw new Exception(lang('error_nlsnotfound'));
        }
        if (!asort($nls['language'], SORT_LOCALE_STRING)) {
            throw new Exception(lang('error_internal'));
        }
        $this->_nls = $nls;
        return $nls;
    }

    /**
     * Get the locale-identifiers of the translations included in the source-files archive
     *
     * @return array
     */
    public function get_language_list() : array
    {
        $this->get_nls();
        return $this->_nls['language'];
    }

    public function get_noncore_modules() : array
    {
        list($iter, $tmpdir) = $this->start_archive_scan('~[\\/]assets[\\/]modules[\\/][^\\/]+$~');

        $names = [];
        foreach ($iter as $fn => $file) {
            $names[] = $fn;
        }

        if ($names) {
            $names = array_unique($names);
            natsort($names);
        }
        return $names;
    }

    public function clear_cache(bool $do_index_html = true)
    {
        $dir = $this->get_tmpdir();
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir) //LEAVES_ONLY
        );
        foreach ($iter as $file => $info) {
            if ($info->isFile()) {
                @unlink($info->getPathInfo());
            }
        }

        if ($do_index_html) {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir) //LEAVES_ONLY
            );
            foreach ($iter as $file => $info) {
                if ($info->isFile()) { //WHAT??
                    @touch($info->getPathInfo().'/index.html');
                }
            }
        }
    }

    public function cleanup()
    {
        $tmp = $this->get_tmpdir();
        if (is_dir($tmp)) {
            utils::rrmdir($tmp);
        }
        $sess = session::get_instance();
        if ($this->in_phar()) {
            // in case it's somewhere outside tmpdir ...
            if (!empty($sess['sourceball'])) {
                $tmp = dirname($sess['sourceball']);
                if (is_dir($tmp)) {
                    utils::rrmdir($tmp);
                }
            }
        }
        $sess->clear();
    }

//TODO register this as an exception/error handler
/*    public function endit()
    {
        $sess = session::get_instance();
//TODO      if not in debug mode $this->cleanup();
        $sess->clear();
    }
*/
    /**
     * Once-per-request initialization
     * @ignore
     * @param string $configfile Optional filepath of a non-default 'config.ini'
     *  containing build settings. Default ''.   *
     * @throws Exception
     */
    private function init(string $configfile)
    {
        if (!$configfile) {
            $configfile = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'config.ini';
        }
        $this->_assetdir = dirname($configfile);
        $p = $this->_assetdir.DIRECTORY_SEPARATOR.'config.ini';
        $config = (is_file($p)) ? parse_ini_file($p, false, INI_SCANNER_TYPED) : [];

        // custom config data
        $p = dirname(__DIR__, 3).DIRECTORY_SEPARATOR.'config.ini';
        $xconfig = (is_file($p)) ? parse_ini_file($p, false, INI_SCANNER_TYPED) : false;
        if ($xconfig) {
            foreach ($xconfig as $k =>$v) {
                if ($v) {
                    $config[$k] = $v;
                } elseif (is_numeric($v)) {
                    $config[$k] = $v + 0;
                }
            }
        }

        $this->_config = ($config) ? $config : false;

        // handle debug mode
        if (!empty($config['debug'])) {
            @ini_set('display_errors', 1);
            @error_reporting(E_ALL);
        }

        $this->_have_phar = extension_loaded('phar');

        // setup core autoloading
        spl_autoload_register([installer_base::class,'autoload']);

        $p = dirname(__DIR__).DIRECTORY_SEPARATOR;
        require_once $p.'compat.functions.php';
        require_once $p.'misc.functions.php';

        // get the session
        $sess = session::get_instance();

        // get the request
        $request = request::get_instance();
        if (isset($request['clear'])) {
            $sess->reset();
        }

        // process (once, or again if a clearance happened) our files-archives
        if (!empty($sess['sourceball'])) {
            $p = joinpath($this->get_tmpdir(),'lib','smarty','Autoloader.php');
            if (!is_file($p)) {
                $sess['sourceball'] = null;
            }
        }
        if (empty($sess['sourceball'])) {
            $p = $config['archive'] ?? 'data/data.tar.gz';
            $src_archive = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$p;
            if (!is_file($src_archive)) {
                throw new Exception('Could not find installation archive at '.$src_archive);
            }

            if ($this->in_phar()) {
                // Copy and rename it securely, because in some environments,
                // we cannot read from a tarball embedded within a phar
                $src_md5 = md5_file($src_archive);
                $dest_archive = $this->get_tmpdir().DIRECTORY_SEPARATOR.'f'.md5($src_archive.session_id()).'.tgz';

                for ($i = 0; $i < 2; $i++) {
                    if (!is_file($dest_archive)) {
                        @copy($src_archive, $dest_archive);
                    }
                    $dest_md5 = md5_file($dest_archive);
                    if (is_readable($dest_archive) && $src_md5 == $dest_md5) {
                        break;
                    }
                    @unlink($dest_archive);
                }
                if ($i == 2) {
                    throw new Exception('Checksum of temporary archive does not match... copying/permissions problem');
                }

                $sess['sourceball'] = $dest_archive;

                // extract installer-Smarty (once) from the sources to an enduring place
                $p = $this->get_tmpdir();

                $tmpdir = 'phar://'.$dest_archive;  //contents are unpacked as descendents of the archive
                $len = strlen($tmpdir) + 1; //separator offset
                $adata = new PharData(
                    $dest_archive,
                    FilesystemIterator::CURRENT_AS_PATHNAME |
                    FilesystemIterator::SKIP_DOTS |
                    FilesystemIterator::UNIX_PATHS
                );
                $iter = new RecursiveIteratorIterator($adata,
                    RecursiveIteratorIterator::SELF_FIRST);
                $iter = new FilePatternFilter($iter,
                    '~[\\/]lib[\\/]smarty[\\/].+php~');
                foreach ($iter as $fp) {
                    $from = substr($fp, $len);
                    $adata->extractTo($p, $from, true);
                }
                $p .= DIRECTORY_SEPARATOR.'lib/smarty';
            }
            else { // !in_phar()
                $sess['sourceball'] = $src_archive;

                // extract installer-Smarty from the archive
                $p = dirname(__DIR__).DIRECTORY_SEPARATOR.'smarty';
                $sp = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'data/smarty.tar.gz';
                if ($this->_have_phar) {
                    $adata = new PharData($sp);
                    $adata->extractTo($p, null, true);
                }
                else {
                    @mkdir($p, 0771, true);
                    $adata = new Tar();
                    $adata->open($sp);
                    $adata->extract($p);
                }
            }
        }
        elseif ($this->in_phar()) {
            $p = $this->get_tmpdir().DIRECTORY_SEPARATOR.'lib/smarty';
        } else {
            $p = dirname(__DIR__).DIRECTORY_SEPARATOR.'smarty';
        }
        // 'external' autoloading init after our Smarty is populated
        $p .= DIRECTORY_SEPARATOR.'Autoloader.php';
        require_once $p;
        Smarty_Autoloader::register();
        require_once __DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

        $this->_archive = $sess['sourceball'];

        // get details of the version we are installing, save them in the session,
        // if not already there
        if (isset($sess['CMSMS:version'])) {
            $ver = $sess['CMSMS:version'];
            $this->_dest_version = $ver['version'];
            $this->_dest_name = $ver['version_name'];
            $this->_dest_schema = $ver['schema_version'];
        } else {
            $verfile = dirname($src_archive).DIRECTORY_SEPARATOR.'version.php';
            if (!is_file($verfile)) {
                throw new Exception('Could not find version file');
            }
            include_once $verfile;
            $ver = ['version' => CMS_VERSION, 'version_name' => CMS_VERSION_NAME, 'schema_version' => CMS_SCHEMA_VERSION];
            $sess['CMSMS:version'] = $ver;
            $this->_dest_version = CMS_VERSION;
            $this->_dest_name = CMS_VERSION_NAME;
            $this->_dest_schema = CMS_SCHEMA_VERSION;
        }

//        register_shutdown_function ([$this, 'endit']);
    }
} // class
