<?php

namespace __installer;

use __installer\wizard\wizard;
use Exception;
use FilesystemIterator;
use FilterIterator;
use Iterator;
use Phar;
use PHPArchive\Tar;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use function __installer\CMSMS\endswith;
use function __installer\CMSMS\joinpath;
use function __installer\CMSMS\lang;
use function __installer\CMSMS\smarty;
use function __installer\CMSMS\startswith;
use function __installer\CMSMS\translator;
use function file_put_contents;

require_once __DIR__.DIRECTORY_SEPARATOR.'class.installer_base.php';

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

class gui_install extends installer_base
{
    private $_archive;
    private $_dest_version;
    private $_dest_name;
    private $_dest_schema;
    private $_destdir;
    private $_custom_destdir;
    private $_custom_tmpdir;
    private $_nls;
    private $_orig_tz;
    private $_orig_error_level;
    private $_have_phar;

    /**
     * @param string $configfile Optional filepath of a non-default 'config.ini'
     *  containing build settings. Default ''.
     * @throws Exception
     */
    public function __construct(string $configfile = '')
    {
        parent::__construct($configfile);

        // for every request we're gonna make sure it's not cached.
        session_cache_limiter('private');
        // and make sure we are in UTF-8
        header('Content-Type:text/html; charset=UTF-8');

        // initialize the session.
        $sess = session::get();
        $p = $sess[__CLASS__]; // trigger session start.

        // get the request
        $request = request::get();
        if( isset($request['clear']) ) {
            $sess->reset();
        }

        $this->_have_phar = extension_loaded('phar');

        $config = $this->get_config(); // generic config data

        $this->fixup_tmpdir_environment();

        // setup smarty
        $smarty = smarty();
        $smarty->assign('APPNAME','cms_installer');
        $smarty->assign('config',$config);
        $smarty->assign('installer_version',$config['installer_version']);
        if( isset($config['build_time']) ) $smarty->assign('build_time',$config['build_time']);

        // handle debug mode
        if( $config['debug'] ) {
            @ini_set('display_errors',1);
            @error_reporting(E_ALL);
        }

        if( $this->in_phar() && !$config['nobase'] ) {
            $base_href = $_SERVER['SCRIPT_NAME'];
            if( endswith($base_href,'.php') ) {
                $base_href = $base_href . '/';
                $smarty->assign('BASE_HREF',$base_href);
            }
        }

        // find our archive, copy it... and rename it securely.
        // we do this because phar data cannot read from a .tar.gz file that is already embedded within a phar
        // (some environments)
        $p = $config['archive'] ?? 'data/data.tar.gz';
        $src_archive = dirname(__DIR__,2).DIRECTORY_SEPARATOR.$p;
        if( !file_exists($src_archive) ) throw new Exception('Could not find installation archive at '.$src_archive);
        $src_md5 = md5_file($src_archive);
        $tmpdir = $this->get_tmpdir().DIRECTORY_SEPARATOR.'m'.md5(__FILE__.session_id());
        $dest_archive = $tmpdir.DIRECTORY_SEPARATOR.'f'.md5($src_archive.session_id()).'.tgz';

        for( $i = 0; $i < 2; $i++ ) {
            if( !file_exists($dest_archive) ) {
                @mkdir($tmpdir,0771,TRUE);
                @copy($src_archive,$dest_archive);
            }
            $dest_md5 = md5_file($dest_archive);
            if( is_readable($dest_archive) && $src_md5 == $dest_md5 ) break;
            @unlink($dest_archive);
        }
        if( $i == 2 ) throw new Exception('Checksum of temporary archive does not match... copying/permissions problem');
        $this->_archive = $dest_archive;

        // get version details (version we are installing)
        // if not in the session, save them there.
        if( isset($sess[__CLASS__.'version']) ) {
            $ver = $sess[__CLASS__.'version'];
            $this->_dest_version = $ver['version'];
            $this->_dest_name = $ver['version_name'];
            $this->_dest_schema = $ver['schema_version'];
        }
        else {
            global $CMS_VERSION, $CMS_VERSION_NAME, $CMS_SCHEMA_VERSION;
            $verfile = dirname($src_archive).DIRECTORY_SEPARATOR.'version.php';
            if( !is_file($verfile) ) throw new Exception('Could not find version file');
            include_once $verfile;
            $ver = ['version' => $CMS_VERSION, 'version_name' => $CMS_VERSION_NAME, 'schema_version' => $CMS_SCHEMA_VERSION];
            $sess[__CLASS__.'version'] = $ver;
            $this->_dest_version = $CMS_VERSION;
            $this->_dest_name = $CMS_VERSION_NAME;
            $this->_dest_schema = $CMS_SCHEMA_VERSION;
        }
    }

    private function fixup_tmpdir_environment()
    {
        // if the system temporary directory is not the same as the config temporary directory
        // then we attempt to putenv the TMPDIR environment variable
        // so that tmpfile() will work as it uses the system temporary directory which can read from environment variables
        $sys_tmpdir = null;
        if( function_exists('sys_get_temp_dir') ) $sys_tmpdir = rtrim(sys_get_temp_dir(),'\\/');
        $config = $this->get_config();
        if( (!$sys_tmpdir || !is_dir($sys_tmpdir) || !is_writable($sys_tmpdir)) && $sys_tmpdir != $config['tmpdir'] ) {
            @putenv('TMPDIR='.$config['tmpdir']);
            $try1 = getenv('TMPDIR');
            if( $try1 != $config['tmpdir'] ) throw new RuntimeException('Sorry, putenv does not work on this system, and your system temporary directory is not set properly.');
        }
    }

    protected function set_config_defaults() : array
    {
        $list = [
            'debug' => false,
            'dest' => null,
            'lang' => null,
            'nobase' => false,
            'nofiles' => false,
            'timezone' => null,
            'tmpdir' => null,
            'verbose' => false,
        ];
        $config = array_merge($list, parent::get_config());

        $tmp = @date_default_timezone_get();
        if( !$tmp) $tmp = 'UTC';
        $this->_orig_tz = $config['timezone'] = $tmp;
        $tmp = realpath(getcwd());

        $adbg = $tmp.'/assets/install/initial.xml';
        $msg = (is_file($adbg)) ? 'XML EXISTS' : 'NO XML at '.$adbg;
        file_put_contents('/tmp/guiinstaller-cwd.txt', $msg); //DEBUG

        if( endswith($tmp, 'phar_installer') ) {
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
        if( endswith($tmp, 'phar_installer') ) {
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
                    $val = parent::get_tmpdir();
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

        // gotta load the config, then store it in the session
        $config = $this->load_config();
        $config = $this->check_config($config);

        $buildconfig = parent::get_config();
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

    public function get_tmpdir() : string
    {
        // because phar uses tmpfile() we need to set the TMPDIR environment variable
        // with whatever directory we find.
        $config = $this->get_config();
        return $config['tmpdir'];
    }

    public function get_archive() : string
	{
		return $this->_archive ?? '';
	}

    /**
     * Extract content from the source-files archive, with or without PHP's Phar extension
     * @param string $pattern Optional regex to be matched for returned item-paths
	 * @return 2-member array: [0] = files iterator [1] = root path of each file
     */
    public function unpack_archive(string $pattern = '') : array
    {
		if ($this->_have_phar) {
		//TODO if PharData is available
		}
        $config = parent::get_instance()->get_config();
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
            if( !file_exists($archive) ) {
                $archive = str_replace('\\','/',$archive);
                if( !file_exists($archive) ) throw new Exception(lang('error_noarchive'));
            }

            $adata = new Tar();
            $adata->open($archive);
            $adata->extract($path);
        }

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,
              FilesystemIterator::KEY_AS_FILENAME |
              FilesystemIterator::CURRENT_AS_PATHNAME |
              FilesystemIterator::SKIP_DOTS |
              FilesystemIterator::UNIX_PATHS),
            RecursiveIteratorIterator::SELF_FIRST);
        if ($pattern) {
            $iter = new FilePatternFilter($iter,$pattern);
        }

        return [$iter, $path];
    }

    public function get_dest_version() : string { return $this->_dest_version; }

    public function get_dest_name() : string { return $this->_dest_name; }

    public function get_dest_schema() : string { return $this->_dest_schema; }

    public function get_phar() : string { return $this->_have_phar ? Phar::running() : ''; }

    public function in_phar() : bool { return $this->_have_phar && Phar::running() != ''; }

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

    public function get_root_url() : string
    {
        $prefix = null;
        //if( isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off' ) $prefix = 'https';
        $prefix .= '//'.$_SERVER['HTTP_HOST'];

        // if we are putting files somewhere else, we cannot determine the root url of the site
        // via the $_SERVER variables.
        $b = $this->get_destdir();
        if( $b != getcwd() ) {
            if( startswith($b,$_SERVER['DOCUMENT_ROOT']) ) $b = substr($b,strlen($_SERVER['DOCUMENT_ROOT']));
            $b = str_replace('\\','/',$b); // cuz windows blows
            if( !endswith($prefix,'/') && !startswith($b,'/') ) $prefix .= '/';
            return $prefix.$b;
        }

        $b = dirname($_SERVER['PHP_SELF']);
        if( $this->in_phar() ) {
            $tmp = basename($_SERVER['SCRIPT_NAME']);
            if( ($p = strpos($b,$tmp)) !== FALSE ) $b = substr($b,0,$p);
        }

        $b = str_replace('\\','/',$b); // cuz windows blows.
        if( !endswith($prefix,'/') && !startswith($b,'/') ) $prefix .= '/';
        return $prefix.$b;
    }

    public function run()
    {
        // set the languages we're going to support.
        $list = translator()->get_available_languages();
        translator()->set_allowed_languages($list);

        // the default language.
        translator()->set_default_language('en_US');

        // get the language preferred by the user (either in the request, in a cookie, or in the session, or in custom config)
        $lang = translator()->get_selected_language();

        if( !$lang ) $lang = translator()->get_default_language(); // get a preferred language

        // set our selected language...
        translator()->set_selected_language($lang);

        // and do our stuff.
        try {
            $wizard = wizard::get_instance(__DIR__.DIRECTORY_SEPARATOR.'wizard',__NAMESPACE__.'\\wizard');
            $tmp = 'm'.substr(md5(realpath(getcwd()).session_id()),0,8);
            // this sets a custom step variable for each instance
            // which is just one more security measure.
            // nobody can guess an installer URL and jump to a specific step to
            // nuke anything (even though database creds are stored in the session
            // so are all the other parameters.
            $wizard->set_step_var($tmp);
            $res = $wizard->process();
        }
        catch( Exception $e ) {
            $smarty = smarty();
            $smarty->assign('error',$e->GetMessage());
            $smarty->display('error.tpl');
        }
    }

    public function cleanup()
    {
        if( $this->_custom_tmpdir ) {
            utils::rrmdir($this->_custom_tmpdir);
        }

        $config = parent::get_instance()->get_config();
        $tmp = $config['archdir'] ?? null;
        if( $tmp && is_dir($tmp) ) {
            utils::rrmdir($tmp);
        }
    }
} // class
