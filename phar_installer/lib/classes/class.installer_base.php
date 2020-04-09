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
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Smarty_Autoloader;
use splitbrain\PHPArchive\Tar;
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
    const CONTENTXML = ['lib','assets','democontent.xml']; //path segments rel to top phardir
    const CONTENTFILESDIR = ['uploadfiles']; //ditto

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
     * @param string $configfile Optional filepath of a 'installer.ini' file
     * containing parameters to be used during installer operation, and
	 * implicitly defining the 'installer-assets' folder to be used. Default ''.
	 * (That folder will also be interrogated for *.xml initial content during installation.)
     * @throws Exception
     */
    protected function __construct(string $configfile = '')
    {
        if (is_object(self::$_instance)) {
            throw new Exception('Cannot create another '.self::class.' object');
        }
        self::$_instance = $this; //used during init()
        $this->init($configfile);
    }

    /**
     * Once-per-request initialization
     * @ignore
	 * @see __construct()
     * @param string $configfile Optional filepath
     * @throws Exception
     */
    private function init(string $configfile = '')
    {
        $p = dirname(__DIR__).DIRECTORY_SEPARATOR;
        if (!$configfile) {
            $this->_assetdir = $p.'assets';
            $fp = $this->_assetdir.DIRECTORY_SEPARATOR.'installer.ini';
        }
        else {
            $this->_assetdir = dirname($configfile);
            $fp = $configfile;
        }
        $config = (is_file($fp)) ? parse_ini_file($fp, false, INI_SCANNER_TYPED) : [];
        $this->_config = ($config) ? $config : [];

        // handle debug mode
        if (!empty($config['debug'])) {
            @ini_set('display_errors', 1);
            @error_reporting(E_ALL);
        }

        $this->_have_phar = extension_loaded('phar');

        // setup core autoloading
        spl_autoload_register([installer_base::class,'autoload']);

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
            // TODO different check: standalone smarty autoloader might have been omitted,
            // not needed with composer-style autoloading
            $p = joinpath($this->get_tmpdir(), 'lib', 'vendor', 'smarty', 'smarty', 'libs', 'Autoloader.php');
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
                // copy and rename it securely, because in some environments,
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
                    '~[\\/]lib[\\/]vendor[\\/]smarty[\\/]smarty[\\/].+~');
                foreach ($iter as $fp) {
                    $from = substr($fp, $len);
                    $adata->extractTo($p, $from, true);
                }
                $p = joinpath($p, 'lib', 'vendor', 'smarty', 'smarty');
            }
            else { // !in_phar()
                $sess['sourceball'] = $src_archive;

                // extract installer-smarty from the archive into a local folder
                $p = __DIR__.DIRECTORY_SEPARATOR.'smarty';
                $sp = joinpath(dirname(__DIR__, 2), 'data', 'smarty.tar.gz');
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
            $p = joinpath($this->get_tmpdir(), 'lib', 'vendor', 'smarty', 'smarty');
        }
        else {
            $p = __DIR__.DIRECTORY_SEPARATOR.'smarty';
        }

        $p .= DIRECTORY_SEPARATOR.'libs'.DIRECTORY_SEPARATOR.'Autoloader.php';
        // smarty's autoloader may be absent when composer handles smarty autoloading
        if (is_file($p)) {
            require_once $p;
        }
        else {
            // revert to our local backup
            define('SMARTY_DIR', dirname($p, 2));
            require_once __DIR__.DIRECTORY_SEPARATOR.'smarty'.DIRECTORY_SEPARATOR.'BackupAutoloader.php';
        }
        Smarty_Autoloader::register();
        // 'foreign-class' autoloading init after our Smarty-loading is in place
        require_once __DIR__.DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';
        $this->_archive = $sess['sourceball'];

        // get details of the version we are installing, save them in the session,
        // if not already there
        if (isset($sess['CMSMS:version'])) {
            $ver = $sess['CMSMS:version'];
            $this->_dest_version = $ver['version'];
            $this->_dest_name = $ver['version_name'];
            $this->_dest_schema = $ver['schema_version'];
        }
        else {
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

	/**
     * Return the singleton object of this class
     * @return installer_base
     * @throws Exception
     */
    public static function get_instance() : self
    {
        if (!is_object(self::$_instance)) {
            throw new Exception('No instance of '.self::class.' is registered');
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
lib/classes/PHPArchive/*                  stet PHPArchive replaced by composer autoloader
lib/classes/smarty/*                      no namespace
*/
                $sroot = dirname(__DIR__).DIRECTORY_SEPARATOR; //top 'lib' dir
                $path = str_replace('\\', DIRECTORY_SEPARATOR, substr($classname, $p + 1));
                $classname = basename($path);
                $path = dirname($path);
                if ($path != '.') {
                    $sroot .= 'classes'.DIRECTORY_SEPARATOR.$path.DIRECTORY_SEPARATOR;
                }
                else {
                    $sroot .= 'classes'.DIRECTORY_SEPARATOR;
                }
                foreach (['class.', ''] as $test) {
                    $fp = $sroot.$test.$classname.'.php';
                    if (is_file($fp)) {
                        require_once $fp;
                        return;
                    }
                }
/* now composer-autoloaded
            }
            elseif ($space == 'PHPArchive') { //files-archive classes
                $path = str_replace('\\', DIRECTORY_SEPARATOR, substr($classname, $o));
                $fp = __DIR__.DIRECTORY_SEPARATOR.$path.'.php';
                if (is_file($fp)) {
                    require_once $fp;
                }
*/
            }
        }
    }

    abstract public function run();

    /**
     * @throws Exception
     */
    protected function set_config_defaults() : array
    {
        $config = $this->merge_config(
        [
            'coremodules' => [], // core module names
            'debug' => false,
            'dest' => null, // top filepath for installation
            'extramodules' => [], // non-core module names
            'lang' => null, // installer translation
            'nobase' => false,
            'nofiles' => false,
            'selectlangs' => [], // translations to be selected for installation
            'selectmodules' => [], // member(s) of extramodules to be selected for installation
            'timezone' => null, // site timezone
            'tmpdir' => null, // working directory
            'verbose' => false, // verbose operation of installer
        ], $this->_config);

        if ($config['tmpdir']) {
            if (is_dir($config['tmpdir']) && is_writable($config['tmpdir'])) {
                $this->_custom_tmpdir = $config['tmpdir'];
            }
            else {
                throw new Exception('Invalid temporary/working directory specified');
            }
        }
        else {
            $tmp = utils::get_sys_tmpdir().DIRECTORY_SEPARATOR.chr(random_int(97,122)).bin2hex(random_bytes(10));
            if (mkdir($tmp, 0771, true)) {
                $this->_custom_tmpdir = $tmp;
                $config['tmpdir'] = $tmp;
            }
            else {
                throw new Exception('No temporary/working directory is available');
            }
        }

        $tmp = @date_default_timezone_get();
        if (!$tmp) {
            $tmp = 'UTC';
        }
        $this->_orig_tz = $config['timezone'] = $tmp;
        $tmp = realpath(getcwd());

        $adbg = joinpath($tmp, 'lib', 'assets', 'initial.xml');
        $msg = (is_file($adbg)) ? 'XML EXISTS' : 'NO XML at '.$adbg;
        file_put_contents('/tmp/guiinstaller-cwd.txt', $msg); //DEBUG

        if (endswith($tmp, 'phar_installer') || endswith($tmp, 'installer')) {
            $tmp = dirname($tmp);
        }
        $config['dest'] = $tmp;
        return $config;
    }
    /**
     * Merge .ini file contents
     * @internal
     * @param array $config1 Merge into this
     * @param array $config2 Merge from this (if not empty)
     * @return array
     */
    private function merge_config(array $config1, array $config2) : array
    {
        if ($config2) {
            foreach ($config2 as $k =>$v) {
                if (is_array($v)) {
                    if (!isset($config1[$k]) || $config1[$k] === '') {
                        $config1[$k] = [];
                    }
                    elseif (!is_array ($v)) {
                        $config1[$k] = [$config1[$k]];
                    }
                    $config1[$k] = array_unique(array_merge($config1[$k] , $v), SORT_STRING);
                }
                elseif (isset($config1[$k]) && is_array($config1[$k]) ) {
                    $config1[$k] = array_unique(array_merge($config1[$k] , [$v]), SORT_STRING);
                }
                elseif ($v) {
                    $config1[$k] = $v;
                }
                elseif (is_numeric($v)) {
                    $config1[$k] = $v + 0;
                }
            }
        }
        return $config1;
    }

    protected function load_config() : array
    {
        // setup default config properties
        $config = $this->set_config_defaults();

        // supplement/override default config with custom config file if any
        $tmp = realpath(getcwd());
        if (endswith($tmp, 'phar_installer') || endswith($tmp, 'installer')) {
            $tmp = dirname($tmp);
        }
        $config_file = joinpath($tmp, 'installer.ini');
        if (is_file($config_file) && is_readable($config_file)) {
            $xconfig = parse_ini_file($config_file, false, INI_SCANNER_TYPED);
            if ($xconfig) {
                $config = $this->merge_config($config, $xconfig);
                if (!empty($xconfig['dest'])) {
                    $this->_custom_destdir = $xconfig['dest'];
                }
            }
        }

        // override current config with URL params if any
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
                if ($val && (!is_dir($val) || !is_writable($val))) {
                    throw new RuntimeException('Invalid config value for '.$key.' - not a directory, or not writable');
                }
                break;
            case 'coremodules':
                if (!$val || !is_array($val) || count($val) < 10) {
                    throw new RuntimeException('Invalid config value for '.$key.' - not an array, or not enough names');
                }
                sort($val, SORT_STRING);
                $config[$key] = $val;
                break;
            default:
//            case 'extramodules':
//            case 'timezone':
//            case 'debug':
//            case 'nofiles':
//            case 'nobase':
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
            // already set once... so close and re-open the browser to reset it.
            return $sess['config'];
        }

        // gotta load the config, then cache it in the session
        $config = $this->load_config();
        $config = $this->check_config($config);

        $buildconfig = $this->_config ?? false;
        if ($buildconfig) {
            $config = $this->merge_config($buildconfig, $config);
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
        return self::class;
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

    public function get_dest_schema() : int
    {
        return (int)($this->_dest_schema ?? 0);
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
        }
        else {
            $config = self::get_instance()->get_config();
            if (empty($config['archdir'])) {
                $path = tempnam($this->get_tmpdir(), 'CMSfiles');
                unlink($path); //we will use this as a dir, not file
                $config['archdir'] = $path;
                $sess = session::get_instance();
                $sess['config'] = $config;
            }
            else {
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
        $config = $this->get_config();
        $names = $config['extramodules'] ?? [];
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
} // class
