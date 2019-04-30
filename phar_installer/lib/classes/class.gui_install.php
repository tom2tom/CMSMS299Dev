<?php

namespace cms_installer;

use cms_installer\installer_base;
use cms_installer\wizard\wizard;
use Exception;
use RuntimeException;
use function cms_installer\endswith;
use function cms_installer\smarty;
use function cms_installer\startswith;
use function cms_installer\translator;

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

        // make sure we are in UTF-8
        header('Content-Type:text/html; charset=UTF-8');

        $config = $this->get_config(); // generic config data

        $this->fixup_tmpdir_environment();

        // setup smarty
        $smarty = smarty();
        $smarty->assign('APPNAME','cms_installer')
          ->assign('config',$config)
          ->assign('installer_version',$config['installer_version']);
        if( isset($config['build_time']) ) $smarty->assign('build_time',$config['build_time']);

        if( $this->in_phar() && !$config['nobase'] ) {
            $base_href = $_SERVER['SCRIPT_NAME'];
            if( endswith($base_href,'.php') ) {
                $base_href = $base_href . '/';
                $smarty->assign('BASE_HREF',$base_href);
            }
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
        if( isset($_SERVER['HTTPS']) && !empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) != 'off' ) $prefix = 'https';
        else $prefix = 'http';
        $prefix .= '://'.$_SERVER['HTTP_HOST'];

        // if we are putting files somewhere else, we cannot determine
        // the root url of the site via $_SERVER variables.
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
            // security measure : a session-specific step-variable.
            $wizard = wizard::get_instance(__DIR__.DIRECTORY_SEPARATOR.'wizard',__NAMESPACE__.'\\wizard');
            $tmp = 'm'.substr(md5(realpath(getcwd()).session_id()),0,8);
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
