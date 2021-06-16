<?php

namespace cms_installer;

//use splitbrain\PHPArchive\Tar;
//use PharData;
use cms_installer\request;
use Exception;
use FilesystemIterator;
use FilterIterator;
use Iterator;
use Phar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Smarty_Autoloader;
use function cms_installer\endswith;
use function cms_installer\get_server_permissions;
use function cms_installer\joinpath;
use function cms_installer\lang;
use function cms_installer\rrmdir;
use function file_put_contents;
use function random_bytes;
use function random_int;

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
    const UPLOADFILESDIR = ['uploadfiles']; //ditto (interim, contents will be migrated by installer from there to .../data/uploads
    const CUSTOMFILESDIR = ['workfiles']; //ditto, for non-db-stored templates, stylesheets, user-plugins etc (interim, contents will be migrated by installer from there to relevant place in sources tree

    /**
     * @var string property which can be checked externally (notably in
     * the AppState class) for confirmation whether the installer is running
     */
    public $signature;

    private static $_instance;
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
     * Provided only (if ever) when commencing the first step.
     * (That folder will also be interrogated for *.xml initial content during installation.)
     *
     * This method will be called TWICE after each step: after action-button
     * click to initiate the step's process method, and after that method has
     * initiated a redirect.
     * @throws Exception
     */
    protected function __construct(string $configfile = '')
    {
        if (is_object(self::$_instance)) {
            throw new Exception('Cannot create another '.self::class.' object');
        }
        $this->signature = hash('fnv132', self::class); //ATM only existence is checked, so any value would do
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

        // and the other auto's
//        $p = joinpath(__DIR__, 'smarty', 'libs', 'Autoloader.php');
        //composer-installed place, plus trailing separator (Smarty path-defines always assume the latter)
        $p = joinpath(dirname(__DIR__, 2), 'sources', 'lib', 'vendor', 'smarty', 'smarty', 'libs', '') ;
        define('SMARTY_DIR', $p);
        $p = joinpath(dirname(__DIR__), 'BackupSmartyAutoloader.php');
        require_once $p;
        Smarty_Autoloader::register();
        $p = joinpath(dirname(__DIR__), 'vendor', 'autoload.php');
        require_once $p;

        // get the session
        $sess = session::get_instance();

        // get the request
        $request = request::get_instance();
        if (isset($request['clear'])) {
            $sess->reset();
        }

        // if details of the CMSMS version we are processing are not already cached,
        // do so now
        if (isset($sess['CMSMS:version'])) {
            $ver = $sess['CMSMS:version'];
        }
        else {
//            $p = $config['archive'] ?? 'data/data.tar.gz';
//            $p = strtr($p, '/\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR);
//            $p = str_replace(basename($p), 'version.php', $p);
//            $verfile = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.$p;
            $verfile = joinpath(dirname(__DIR__, 2), 'sources', 'lib', 'version.php');
            if (!is_file($verfile)) {
                throw new Exception('Could not find intallation version file');
            }
// TODO preserve current global $CMS_VERSION, $CMS_VERSION_NAME, $CMS_SCHEMA_VERSION if any c.f. create.manifest.php::get_version()
            include_once $verfile;
            $ver = ['version' => $CMS_VERSION, 'version_name' => $CMS_VERSION_NAME, 'schema_version' => $CMS_SCHEMA_VERSION];
            $sess['CMSMS:version'] = $ver;
        }
        $this->_dest_version = $ver['version'];
        $this->_dest_name = $ver['version_name'];
        $this->_dest_schema = $ver['schema_version'];

//      register_shutdown_function ([$this, 'whatever']);
//      register throwable hanlder, session-end-handler ([$this, 'endit']);
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
     * Merge config data arrays (e.g. from .ini files)
     * Unlike array_merge_recusive(), this does some filtering of values
     * and is limited to 2-D arrays.
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
            $fp = get_sys_tmpdir().DIRECTORY_SEPARATOR.chr(random_int(97,122)).bin2hex(random_bytes(10));
            $dirmode = get_server_permissions()[3]; // read+write
            if (mkdir($fp, $dirmode, true)) {
                $this->_custom_tmpdir = $fp;
                $config['tmpdir'] = $fp;
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
        $fp = realpath(getcwd());

        if (!empty($config['debug'])) {
            $tmp = joinpath($fp, 'lib', 'assets', 'initial.xml');
            $msg = (is_file($tmp)) ? 'XML EXISTS' : 'NO XML at '.$tmp;
            file_put_contents($config['tmpdir'].DIRECTORY_SEPARATOR.'guiinstaller-cwd.txt', $msg);
        }
        if (endswith($fp, 'phar_installer') || endswith($fp, 'installer')) {
            $fp = dirname($fp);
        }
        $config['dest'] = $fp;
        return $config;
    }

    protected function load_config() : array
    {
        // setup default config properties
        $config = $this->set_config_defaults();

        // supplement/override default config with custom config file if any
        $fp = realpath(getcwd());
        if (endswith($fp, 'phar_installer') || endswith($fp, 'installer')) {
            $fp = dirname($fp);
        }
        $config_file = joinpath($fp, 'installer.ini');
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
                $config['debug'] = to_bool($val);
                break;
            case 'nobase':
                $config['nobase'] = to_bool($val);
                break;
            case 'nofiles':
            case 'no_files':
                $config['nofiles'] = to_bool($val);
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
//            cases 'admin_path': 'assets_path': 'usertags_path':
//            case 'extramodules':
//            case 'debug':
//            case 'nofiles':
//            case 'nobase':
//            case 'timezone':
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

    public function merge_config_vals(array $config)
    {
        if ($config) {
            $current = $this->get_config();
            $new = $this->merge_config($current, $config);

            $sess = session::get_instance();
            $sess['config'] = $new;
        }
    }

    public function remove_config_val(string $key)
    {
        $config = $this->get_config();
        unset($config[trim($key)]);

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
        $config = $this->get_config();
        return $config['tmpdir'];
    }

    public function get_destdir() : string
    {
        $config = $this->get_config();
        return $config['dest'] ?? 'MISSING_FOLDERPATH';
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
        $config = $this->get_config();
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

    public function get_phar($asurl = false) : string
    {
        return ($this->_have_phar) ? Phar::running($asurl) : '';
    }

    public function in_phar() : bool
    {
        return $this->_have_phar && Phar::running() != '';
    }

    /**
     * Setup to interrogate the source files
     * @param string $pattern Optional regex to be matched for wanted-item paths
     * @return 2-member array:
     *  [0] = files iterator
     *  [1] = root|base path of each file that the iterator will report
     */
    public function setup_sources_scan(string $pattern = '') : array
    {
        $path = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'sources';
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $path,
                FilesystemIterator::KEY_AS_FILENAME |
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS |
                FilesystemIterator::UNIX_PATHS
            ),
            RecursiveIteratorIterator::SELF_FIRST);

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

        list($iter, $topdir) = $this->setup_sources_scan('~[\\/]lib[\\/]nls[\\/].+\.nls\.php$~');

        $nls = [];
        foreach ($iter as $fp) {
            include $fp; //populates $nls[]
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
            new RecursiveDirectoryIterator($dir) //LEAVES_ONLY i.e. won't find empty dirs
        );
        foreach ($iter as $file => $info) {
            if ($info->isFile()) {
                @unlink($info->getPathInfo());
            }
        }

        if ($do_index_html) {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir) //LEAVES_ONLY i.e. won't find empty dirs
            );
            foreach ($iter as $file => $info) {
                if ($info->isFile()) { //WHAT? some attempted workaround for empty dirs??
                    @touch($info->getPathInfo().'/index.html');
                }
            }
        }
    }

    public function cleanup()
    {
        $fp = $this->get_tmpdir();
        if (is_dir($fp)) {
            rrmdir($fp);
        }
        $config = $this->get_config();
        $clear = empty($config['debug']);
        if ($clear) {
            $fp = $config['dest'].DIRECTORY_SEPARATOR.'installer.ini';
            if (is_file($fp)) {
                @unlink($fp);
            }
        }
        $sess = session::get_instance();
        if ($this->in_phar()) {
            // in case it's somewhere outside tmpdir ...
/*
            if (!empty($sess['sourceball'])) {
                $fp = dirname($sess['sourceball']);
                if (is_dir($fp)) {
                    rrmdir($fp);
                }
            }
*/
            if ($clear) {
                $fp = $this->get_phar();
                $sess->clear();
                @unlink($fp);
                exit;
            }
        } elseif ($clear) {
             $fp = $this->get_rootdir();
             $sess->clear();
             rrmdir($fp);
             exit;
        }
        $sess->clear();
    }

//TODO register this as an exception/error/session-end handler, NOT request-end handler
/*    public function endit()
    {
        $sess = session::get_instance();
//TODO      if not in debug mode $this->cleanup();
        $sess->clear();
    }
*/
} // class
