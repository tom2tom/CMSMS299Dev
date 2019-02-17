<?php

namespace cms_installer;

use cms_installer\installer_base;
use cms_installer\request;
use cms_installer\session;
use cms_installer\wizard\wizard;
use function cms_installer\CMSMS\smarty;
use function cms_installer\CMSMS\translator;

require_once __DIR__.DIRECTORY_SEPARATOR.'class.installer_base.php';

class gui_install extends installer_base
{
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
} // class
