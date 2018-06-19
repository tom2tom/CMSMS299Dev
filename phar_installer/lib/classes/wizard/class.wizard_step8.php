<?php

namespace __installer\wizard;

use __installer\installer_base;
use cms_config;
use cms_siteprefs;
use CmsApp;
use Exception;
use const CMS_ADMIN_PATH;
use const CMS_DB_PREFIX;
use function __installer\CMSMS\lang;
use function __installer\CMSMS\smarty;
use function __installer\get_app;
use function cmsms;
use function GetDb;
use function import_content;

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
    private function db_connect($destconfig)
    {
        require_once dirname(__DIR__,2).DIRECTORY_SEPARATOR.'CMSMS'.DIRECTORY_SEPARATOR.'dbaccessor.functions.php';
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

    private function connect_to_cmsms($destdir)
    {
        global $DONT_LOAD_DB, $DONT_LOAD_SMARTY, $CMS_VERSION, $CMS_PHAR_INSTALLER;
        $DONT_LOAD_DB = 1;
        $DONT_LOAD_SMARTY = 1;
        $CMS_PHAR_INSTALLER = 1;
        $CMS_VERSION = $this->get_wizard()->get_data('destversion');

        // setup and initialize the CMSMS API's
        // note DONT_LOAD_DB and DONT_LOAD_SMARTY are true
        if( is_file("$destdir/include.php") ) {
            include_once $destdir.'/include.php';
        }
        else {
            include_once $destdir.'/lib/include.php';
        }

    }

    private function do_install()
    {
        $destdir = get_app()->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',700));

        $adminaccount = $this->get_wizard()->get_data('adminaccount');
        if( !$adminaccount ) throw new Exception(lang('error_internal',701));

        $destconfig = $this->get_wizard()->get_data('config');
        if( !$destconfig ) throw new Exception(lang('error_internal',703));

        $siteinfo = $this->get_wizard()->get_data('siteinfo');
        if( !$siteinfo ) throw new Exception(lang('error_internal',704));

        $this->connect_to_cmsms($destdir);

        // connect to the database, if possible
        $db = $this->db_connect($destconfig);

        $dir = get_app()->get_assetsdir().'/install';

        include_once dirname(__DIR__,2).'/msg_functions.php';
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
            // create some variables that the sub functions need.
            if( !defined('CMS_ADODB_DT') ) define('CMS_ADODB_DT','DT');
            $admin_user = null;
            $db_prefix = CMS_DB_PREFIX;

            // install the schema
            $this->message(lang('install_schema'));
            $fn = $dir.'/schema.php';
            if( !file_exists($fn) ) throw new Exception(lang('error_internal',705));

            global $CMS_INSTALL_DROP_TABLES, $CMS_INSTALL_CREATE_TABLES;
            $CMS_INSTALL_DROP_TABLES=1;
            $CMS_INSTALL_CREATE_TABLES=1;
            include_once $fn;

            $this->verbose(lang('install_setsequence'));
            include_once $dir.'/createseq.php';

            // create tmp directories
            $this->verbose(lang('install_createtmpdirs'));
            @mkdir($destdir.'/tmp/cache',0771,TRUE);
            @mkdir($destdir.'/tmp/templates_c',0771,TRUE);

            // permisssions
            include_once $dir.'/base.php';

            $this->verbose(lang('install_setsitename'));
            cms_siteprefs::set('sitename',$siteinfo['sitename']);

            $this->write_config();

            // todo: install default preferences
            cms_siteprefs::set('global_umask','022');

            // site content
            if( $destconfig['samplecontent'] ) {
                $fn = installer_base::CONTENTXML;
            } else {
                $fn = 'initial.xml';
            }
            $xmlfile = $dir . DIRECTORY_SEPARATOR . $fn;
            if( is_file($xmlfile) ) {
                if( $destconfig['samplecontent'] ) {
                    $this->message(lang('install_defaultcontent'));
                }
                $fp = CMS_ADMIN_PATH . DIRECTORY_SEPARATOR . 'function.contentoperation.php';
                require_once $fp;
                if( ($res = import_content($xmlfile)) ) {
                    $this->error($res);
                }
            } else {
                $this->error(lang('error_nocontent',$fn));
            }

            // update pages hierarchy
            $this->verbose(lang('install_updatehierarchy'));
            $contentops = cmsms()->GetContentOperations();
            $contentops->SetAllHierarchyPositions();
        }
        catch( Exception $e ) {
            die('exception: '.$e->GetMessage());
//            $this->error($e->GetMessage());
        }
    }

    private function do_upgrade($version_info)
    {
        global $DONT_LOAD_DB, $DONT_LOAD_SMARTY, $CMS_VERSION, $CMS_PHAR_INSTALLER;
        $CMS_PHAR_INSTALLER = 1;
        $DONT_LOAD_DB = 1;
        $DONT_LOAD_SMARTY = 1;
        $CMS_VERSION = $this->get_wizard()->get_data('destversion');

        // get the list of all available versions that this upgrader knows about
        $app = get_app();
        $dir =  $app->get_assetsdir().'/upgrade';
        if( !is_dir($dir) ) throw new Exception(lang('error_internal',710));
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',711));

        $dh = opendir($dir);
        $versions = array();
        if( !$dh ) throw new Exception(lang('error_internal',712));
        while( ($file = readdir($dh)) !== false ) {
            if( $file == '.' || $file == '..' ) continue;
            if( is_dir($dir.'/'.$file) && (is_file("$dir/$file/MANIFEST.DAT") || is_file("$dir/$file/MANIFEST.DAT.gz")) ) $versions[] = $file;
        }
        closedir($dh);
        if( count($versions) ) usort($versions,'version_compare');

        $destconfig = $this->get_wizard()->get_data('config');
        if( !$destconfig ) throw new Exception(lang('error_internal',703));

        // setup and initialize the cmsms API's
        if( is_file("$destdir/include.php") ) {
            include_once $destdir.'/include.php';
        }
        else {
            include_once $destdir.'/lib/include.php';
        }

        // setup database connection
        $db = $this->db_connect($destconfig);

        include_once __DIR__.'/msg_functions.php';

        try {
            // ready to do the upgrading now (in a loop)
            // only perform upgrades for the versions known by the installer that are greater than what is instaled.
            $current_version = $version_info['version'];
            foreach( $versions as $ver ) {
                $fn = "$dir/$ver/upgrade.php";
                if( version_compare($current_version,$ver) < 0 && is_file($fn) ) {
                    include_once $fn;
                }
            }

            $this->write_config();

            $this->message(lang('done'));
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }
    }

    private function do_freshen()
    {
        try {
            $this->write_config();
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }
    }

    private function write_config()
    {
        $destconfig = $this->get_wizard()->get_data('config');
        if( !$destconfig ) throw new Exception(lang('error_internal',703));

        $destdir = get_app()->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal',700));

        // create new config file.
        // this step has to go here.... as config file has to exist in step9
        // so that CMSMS can connect to the database.
        $fn = $destdir."/config.php";
        if( is_file($fn) ) {
            $this->verbose(lang('install_backupconfig'));
            $destfn = $destdir.'/bak.config.php';
            if( !copy($fn,$destfn) ) throw new Exception(lang('error_backupconfig'));
        }

        $this->connect_to_cmsms($destdir);

        $this->message(lang('install_createconfig'));
        $newconfig = cms_config::get_instance();
        $newconfig['dbms'] = 'mysqli'; //trim($destconfig['db_type']);
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
        $newconfig->save();
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
            if( $action == 'upgrade' && is_array($tmp) && count($tmp) ) {
                $this->do_upgrade($tmp);
            }
            elseif( $action == 'freshen' ) {
                $this->do_freshen();
            }
            elseif( $action == 'install' ) {
                $this->do_install();
            }
            else {
                throw new Exception(lang('error_internal',705));
            }
        }
        catch( Exception $e ) {
            $this->error($e->GetMessage());
        }

        $this->finish();
    }
} // class

