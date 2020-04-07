<?php

namespace cms_installer\wizard;

use cms_config;
use cms_installer\wizard\wizard_step;
use cms_siteprefs;
use cms_utils;
use CmsApp;
use CMSMS\AdminTheme;
use CMSMS\AppState;
use Exception;
use function cms_installer\get_app;
use function cms_installer\lang;
use function cms_installer\smarty;
use function GetDb;
use function verbose_msg;

class wizard_step8 extends wizard_step
{
    protected function process()
    {
        // nothing here
    }

    /**
     * @param array $destconfig parameters for db connection
     * @return mixed Connection-class object or error message string
     */
    private function db_connect(array $destconfig)
    {
        require_once dirname(__DIR__,2).DIRECTORY_SEPARATOR.'dbaccessor.functions.php';
        try {
            $db = GetDb($destconfig);
        }
        catch( Exception $e ) {
            return $e->getMessage();
        }
        if( !defined('CMS_DB_PREFIX') ) define('CMS_DB_PREFIX',$destconfig['db_prefix']);
        $db->Execute("SET NAMES 'utf8'");
        CmsApp::get_instance()->_setDb($db);
        return $db;
    }

    private function connect_to_cmsms(string $destdir)
    {
/* downstream included file sets this
        global $CMS_VERSION;

        $info = $this->get_wizard()->get_data('version_info'); // N/A during install
        if( $info && !empty($info['version']) ) {
            $CMS_VERSION = $info['version'];
        } else {
            $CMS_VERSION = '2.8.900'; //TODO include version.php file?
        }
*/
        require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        AppState::add_state(AppState::STATE_INSTALL);
        // setup and initialize the CMSMS API's
        $fp = $destdir.DIRECTORY_SEPARATOR.'include.php';
        if( is_file($fp) ) {
            include_once $fp;
        }
        else {
            include_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
        }
    }

    /**
     * @ignore
     * @throws Exception
     */
    private function do_install()
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',800));

        $wiz = $this->get_wizard();
        $adminaccount = $wiz->get_data('adminaccount');
        if( !$adminaccount ) throw new Exception(lang('error_internal',801));

        $destconfig = $wiz->get_data('config');
        if( !$destconfig ) throw new Exception(lang('error_internal',802));

        $siteinfo = $wiz->get_data('siteinfo');
        if( !$siteinfo ) throw new Exception(lang('error_internal',803));

        $cachtype = $wiz->get_data('cachemode');

        // create new config.php file to enable database connection
        $this->write_config();

        $this->connect_to_cmsms($destdir);

        // connect to the database, if possible
        $db = $this->db_connect($destconfig);

        $dir = dirname(__DIR__,2).DIRECTORY_SEPARATOR.'install';
/*
        $fn = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'install'.DIRECTORY_SEPARATOR;
        require_once $fn.'base.php';
        require_once $fn.'schema.php';
        require_once $fn.'createseq.php';
*/
        try {
            if( !is_object($db) ) {
                throw new Exception($db); //report the error
            }

            // install main tables
            $fn = $dir.DIRECTORY_SEPARATOR.'schema.php';
            if( !is_file($fn) ) throw new Exception(lang('error_internal',805));
            require_once $fn;

            // install sequence tables
            require_once $dir.DIRECTORY_SEPARATOR.'createseq.php';

            // create tmp directories
            $this->verbose(lang('install_createtmpdirs'));
            $fp = constant('TMP_CACHE_LOCATION');
            if( !$fp ) $fp = $destdir.DIRECTORY_SEPARATOR.'tmp/cache';
            @mkdir($fp,0771,TRUE);
            touch($fp.DIRECTORY_SEPARATOR.'index.html');
            $fp = constant('PUBLIC_CACHE_LOCATION');
            if( !$fp ) $fp = $destdir.DIRECTORY_SEPARATOR.'tmp/cache/public';
            @mkdir($fp,0771,TRUE);
            touch($fp.DIRECTORY_SEPARATOR.'index.html');
            $fp = constant('TMP_TEMPLATES_C_LOCATION');
            if( !$fp ) $fp = $destdir.DIRECTORY_SEPARATOR.'tmp/templates_c';
            @mkdir($fp,0771,TRUE);
            touch($fp.DIRECTORY_SEPARATOR.'index.html');

            // init some of the system-wide default settings
            verbose_msg(lang('install_initsiteprefs'));
            $corenames = $app->get_config()['coremodules'];
            $cores = implode(',',$corenames);
            $theme = reset(AdminTheme::GetAvailableThemes());
            $uuid = cms_utils::random_string(32);
            $ultras = json_encode(['Modify Restricted Files','Modify DataBase Direct','Remote Administration']);

            foreach ([
             'adminlog_lifetime' => 3600*24*31, // admin log entries live for 60 days TODO AdminLog module setting
             'allow_browser_cache' => 1, // allow browser to cache cachable pages
             'auto_clear_cache_age' => 60, // tasks-parameter: cache files for 60 days by default (see also cache_lifetime)
             'browser_cache_expiry' => 60, // browser can cache pages for 60 minutes
             'cache_autocleaning' => 1,
             'cache_driver' => $cachtype, //'auto', or 'file' if no supported cache-extension was detected
             'cache_file_blocking' => 0,
             'cache_file_locking' => 1,
             'cache_lifetime' => 3600, // cache entries live for 1 hr
             'cdn_url' => 'https://cdnjs.cloudflare.com', // or e.g. https://cdn.jsdelivr.net, https://cdnjs.com/libraries
             'content_autocreate_urls' => 0,
             'content_imagefield_path' => '',
             'contentimage_path' => '',
             'content_thumbnailfield_path' => '',
             'coremodules' => $cores, // aka ModuleOperations::CORENAMES_PREF
             'defaultdateformat' => '%e %B %Y',
             'enablesitedownmessage' => 0,
             'frontendlang' => 'en_US',
             'global_umask' => '022',
             'lock_refresh' => 120,
             'lock_timeout' => 60,
             'loginmodule' => '',  // login processing by current theme
             'logintheme' => $theme,
             'metadata' => '<meta name="Generator" content="CMS Made Simple - Copyright (C) 2004-' . date('Y') . '. All rights reserved." />'."\n".'<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />'."\n",
             'site_supporturl' => $siteinfo['supporturl'],
             'site_uuid' => $uuid, // almost-certainly-unique signature of this site
//           'sitemask' => '', // salt for old (md5-hashed) admin-user passwords - useless in new installs
             'sitename' => $siteinfo['sitename'],
             'smarty_cachelife' => -1, // smarty default
             'ultraroles' => $ultras,
             'use_smartycompilecheck' => 1,
            ] as $name=>$val) {
                cms_siteprefs::set($name, $val);
            }

            // permisssions etc
            require_once $dir.DIRECTORY_SEPARATOR.'base.php';
        }
        catch( Exception $e ) {
            die('exception: '.$e->GetMessage());
//            $this->error($e->GetMessage());
        }
    }

    /**
     * @ignore
     * @param array $version_info
     * @throws Exception
     */
    private function do_upgrade(array $version_info)
    {
        global $CMS_VERSION;

        $info = $this->get_wizard()->get_data('version_info');
        $CMS_VERSION = $info['version'];

        // get the list of all available versions that this upgrader knows about
        $app = get_app();
        $dir = $app->get_assetsdir().DIRECTORY_SEPARATOR.'upgrade';
        if( !is_dir($dir) ) throw new Exception(lang('error_internal',810));
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',811));

        $dh = opendir($dir);
        if( !$dh ) throw new Exception(lang('error_internal',812));
        $versions = [];
        while( ($file = readdir($dh)) !== false ) {
            if( $file == '.' || $file == '..' ) continue;
            if( is_dir($dir.DIRECTORY_SEPARATOR.$file) && (is_file("$dir/$file/MANIFEST.DAT") || is_file("$dir/$file/MANIFEST.DAT.gz")) ) $versions[] = $file;
        }
        closedir($dh);
        if( $versions ) usort($versions,'version_compare');

        $destconfig = $this->get_wizard()->get_data('config');
        if( !$destconfig ) throw new Exception(lang('error_internal',820));

        $siteinfo = $this->get_wizard()->get_data('siteinfo');
        if( !$siteinfo ) throw new Exception(lang('error_internal',821));

        // setup and initialize the CMSMS API's
        require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        AppState::add_state(AppState::STATE_INSTALL);
        if( is_file("$destdir/include.php") ) {
            include_once $destdir.DIRECTORY_SEPARATOR.'include.php';
        }
        else {
            include_once $destdir.DIRECTORY_SEPARATOR.'lib/include.php';
        }

        // setup database connection
        $db = $this->db_connect($destconfig);

        $smarty = smarty(); //in scope for inclusions

        try {
            // ready to do the upgrading now (in a loop)
            // only perform upgrades for the versions known by the installer that are greater than what is installed.
            $current_version = $version_info['version'];
            foreach( $versions as $ver ) {
                if( version_compare($current_version,$ver) >= 0 ) continue;
                $fn = "$dir/$ver/upgrade.php";
                if( !is_file($fn) ) continue;
                include_once $fn;
            }

            $this->write_config();
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $corenames = $app->get_config()['coremodules'];
        $cores = implode(',',$corenames);
        $arr = [
            'coremodules' => $cores, // aka ModuleOperations::CORENAMES_PREF
            'ultraroles' => json_encode(['Modify Restricted Files','Modify DataBase Direct','Remote Administration']),
        ];
        if( issset($siteinfo['supporturl']) ) { //TODO only if verbose etc
            $arr['site_support'] = $siteinfo['supporturl'];
         }
        foreach ($arr as $name=>$val) {
            cms_siteprefs::set($name, $val);
        }
    }

    private function do_freshen()
    {
        // nothing here
    }

    private function write_config()
    {
        $destconfig = $this->get_wizard()->get_data('config');
        if( !$destconfig ) throw new Exception(lang('error_internal',830));

        $destdir = get_app()->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',831));

        $fn = $destdir.DIRECTORY_SEPARATOR.'config.php';
        if( is_file($fn) ) {
            $this->verbose(lang('install_backupconfig'));
            $destfn = $destdir.DIRECTORY_SEPARATOR.'bak.config.php';
            if( !copy($fn,$destfn) ) throw new Exception(lang('error_backupconfig'));
        }

        $this->message(lang('install_createconfig'));
        // get a 'real' config object
        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
        require_once $fp.'misc.functions.php';
        require_once $fp.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        AppState::add_state(AppState::STATE_INSTALL); //enable $config property-setting
        require_once $fp.'classes'.DIRECTORY_SEPARATOR.'class.cms_config.php';
        $newconfig = cms_config::get_instance();
//      $newconfig['dbms'] = 'mysqli'; //trim($destconfig['db_type']); redundant always mysqli
        $newconfig['db_hostname'] = trim($destconfig['db_hostname']);
        $newconfig['db_username'] = trim($destconfig['db_username']);
        $newconfig['db_password'] = trim($destconfig['db_password']);
        $newconfig['db_name'] = trim($destconfig['db_name']);
        $newconfig['db_prefix'] = trim($destconfig['db_prefix']);
        $newconfig['timezone'] = trim($destconfig['timezone']);
        if( $destconfig['query_var'] ) $newconfig['query_var'] = trim($destconfig['query_var']);
        if( isset($destconfig['db_port']) ) {
            $num = (int)$destconfig['db_port'];
            if( $num > 0 ) $newconfig['db_port'] = $num;
        }
        $newconfig->save(true,$fn);
    }

    protected function display()
    {
        parent::display();
        $smarty = smarty();
        $smarty->assign('next_url',$this->get_wizard()->next_url());
        $smarty->display('wizard_step8.tpl');

        // here, we do either the upgrade, or the install stuff.
        try {
            $action = $this->get_wizard()->get_data('action');
            $tmp = $this->get_wizard()->get_data('version_info');
            if( $action == 'upgrade' && $tmp ) {
                $this->do_upgrade($tmp);
            }
            elseif( $action == 'freshen' ) {
                $this->do_freshen();
            }
            elseif( $action == 'install' ) {
                $this->do_install();
            }
            else {
                throw new Exception(lang('error_internal',840));
            }
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $this->finish();
    }
} // class
