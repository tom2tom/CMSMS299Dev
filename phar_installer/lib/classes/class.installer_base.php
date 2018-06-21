<?php

namespace __installer;

use __installer\request;
use __installer\utils;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Smarty_Autoloader;
use function __installer\CMSMS\startswith;

abstract class installer_base
{
    const CONFIG_ROOT_URL = 'root_url';
    const CONTENTXML = ['assets','install','democontent.xml']; //path segments rel to top phardir
    const CONTENTFILESDIR = ['assets','install','uploadfiles']; //ditto

    private static $_instance;
    private $_config; //array or false
    private $_assetdir;

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

        require_once $p.'compat.functions.php';
        require_once $p.'msg_functions.php';
        $p .= 'CMSMS'.DIRECTORY_SEPARATOR;
        require_once $p.'misc.functions.php';
        require_once $p.'accessor.functions.php';

        if (!$configfile) {
            $configfile = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'X';
        }
        $this->_assetdir = dirname($configfile);
        $p = $this->_assetdir.DIRECTORY_SEPARATOR.'config.ini';
        $this->_config = (file_exists($p)) ? parse_ini_file($p) : false;
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
        // not modifiable, yet
        return utils::get_sys_tmpdir();
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
        $config = self::$_instance->config();
        if ($config && isset($config[self::CONFIG_ROOT_URL])) {
            return $config[self::CONFIG_ROOT_URL];
        }

        $request = request::get();
        $dir = dirname($request['SCRIPT_FILENAME']);
        return $dir;
    }

    public function get_config()
    {
        return $this->_config;
    }

    public static function clear_cache(bool $do_index_html = true)
    {
        $rdi = new RecursiveDirectoryIterator($this->get_tmpdir());
        $rii = new RecursiveIteratorIterator($rdi);
        foreach ($rii as $file => $info) {
            if ($info->isFile()) {
                @unlink($info->getPathInfo());
            }
        }

        if ($do_index_html) {
            $rdi = new RecursiveDirectoryIterator($this->get_tmpdir());
            $rii = new RecursiveIteratorIterator($rdi);
            foreach ($rii as $file => $info) {
                if ($info->isFile()) {
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
lib/CMSMS/classes/class.http_request.php  __installer\CMSMS   >> 'CMSMS 'then 'classes'
lib/CMSMS/classes/nls/class.nl_NL.nls.php __installer\CMSMS\nls (not autoloaded) >> 'CMSMS 'then 'classes'
lib/classes/class.utils.php               __installer          >> prepend 'classes'
lib/classes/wizard/class.wizard_step1.php __installer\wizard   >> prepend 'classes'
lib/classes/tests/class.boolean_test.php  __installer\tests    >> prepend 'classes'
lib/Smarty/*           no namespace
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
