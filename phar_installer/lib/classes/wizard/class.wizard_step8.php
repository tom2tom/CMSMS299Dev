<?php

namespace cms_installer\wizard;

use cms_installer\wizard\wizard_step;
use CMSMS\App;
use CMSMS\AppConfig;
use CMSMS\AppState;
use Exception;
use Throwable;
use function cms_installer\get_app;
use function cms_installer\GetDb;
use function cms_installer\lang;
use function cms_installer\smarty;

class wizard_step8 extends wizard_step
{
    protected function process()
    {
        // nothing here
    }

    /**
     * Get a handle to the system database.
     * Without using App::GetDb(), which doesn't accept $config data
     * and effectively assumes $config (including db connection params)
     * is already set. But here it aint so!
     *
     * @param array $destconfig parameters for db connection
     * @return mixed Connection-class object or error message string
     */
    private function db_connect(array $destconfig)
    {
//      if( !defined('CMS_DB_PREFIX') ) define('CMS_DB_PREFIX',$destconfig['db_prefix']);
        require_once dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'dbaccessor.functions.php';
        try {
            $db = GetDb($destconfig);
        }
        catch( Throwable $t ) {
            return $t->getMessage();
        }
        AppState::add_state(AppState::STATE_INSTALL);
        App::get_instance()->_setDb($db);
        return $db;
    }

    /**
     * Setup and initialize the CMSMS API's
     * @param string $destdir filepath of top-directory of installation tree
     */
    private function connect_to_cmsms(string $destdir)
    {
        require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        AppState::add_state(AppState::STATE_INSTALL);

        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
        if( is_file($fp) ) {
            include_once $fp;
        }
        else {
            require_once $destdir.DIRECTORY_SEPARATOR.'include.php';
        }
    }

    /**
     * @ignore
     */
    private function do_install()
    {
        $app = get_app();
        $wiz = $this->get_wizard();

        $destdir = $app->get_destdir();
        try {
            if( !$destdir ) throw new Exception(lang('error_internal', 800));

            $adminaccount = $wiz->get_data('adminaccount');
            if( !$adminaccount ) throw new Exception(lang('error_internal', 801));

            $destconfig = $wiz->get_data('config');
            if( !$destconfig ) throw new Exception(lang('error_internal', 802));

            $choices = $wiz->get_data('sessionchoices');
            if( !$choices ) throw new Exception(lang('error_internal', 803));
/*
            // create temporary dummy config file, to enable database connection
            // during CMSMS init
            $dmycfg = $destdir.DIRECTORY_SEPARATOR.'config.php';

            $app_config = $app->get_config();
            $admin = ( !empty($app_config['admin_path']) && $app_config['admin_path'] != 'admin' ) ? $app_config['admin_path'] : '';
            //TODO ensure absolute paths e.g. prepend destdir if needed
            $assets = ( !empty($app_config['assets_path']) && $app_config['assets_path'] != 'assets' ) ? $app_config['assets_path'] : '';
            $tags = ( !empty($app_config['usertags_path']) && $app_config['usertags_path'] != 'assets/user_plugins' ) ? $app_config['usertags_path'] : '';  //TODO any separator and/or just 'user_plugins'
            $host = trim($destconfig['db_hostname']);
            $name = trim($destconfig['db_name']);
            if( empty($destconfig['db_port']) ) {
                $port = "''"; // filter it out
            }
            else {
                $port = (int)$destconfig['db_port'];
                if( $port < 1 ) $port = "''";
            }
            $user = trim($destconfig['db_username']);
            $pass = trim($destconfig['db_password']);
            if( empty($destconfig['db_prefix']) ) {
                $pref = 'cms_';
            }
            else {
                $pref = trim($destconfig['db_prefix']);
            }
            $qvar = (!empty($destconfig['query_var'])) ? trim($destconfig['query_var']) : '';
            $zone = (!empty($destconfig['timezone'])) ? trim($destconfig['timezone']) : '';
            $set = $zone ? true : "''";

            file_put_contents($dmycfg, <<<EOS
<?php
\$config = array_filter([
'admin_path' => "$admin",
'assets_path' => "$assets",
'db_hostname' => "$host",
'db_name' => "$name",
'db_port' => $port,
'db_username' => "$user",
'db_password' => "$pass",
'db_prefix' => "$pref",
'usertags_path' => "$tags",
'timezone' => "$zone",
'set_db_timezone' => $set,
'set_names' => true,
], function(\$v){ return \$v !== ''; }) + (\$config ?? []);

EOS
            , LOCK_EX);

            $this->connect_to_cmsms($destdir, $config); //TODO conform to new config API

            // now we can save the 'real' config file
            $this->write_config($destconfig);
            $fp = str_replace('config.php','bak.config.php', $dmycfg);
            @unlink($fp);
*/
            $this->write_config($destconfig, true);

            $this->connect_to_cmsms($destdir);
            // connect to the database, if possible
            $db = $this->db_connect($destconfig);
            if( !is_object($db) ) throw new Exception($db);

            $dir = dirname(__DIR__, 2);
            // install main tables
            $fp = $dir.DIRECTORY_SEPARATOR.'method.schema.php';
            if( !is_file($fp) ) throw new Exception(lang('error_internal', 805));
            require_once $fp;

            // install sequence tables
            $fp = $dir.DIRECTORY_SEPARATOR.'method.sequence.php';
            if( !is_file($fp) ) throw new Exception(lang('error_internal', 806));
            require_once $fp;

            // site-places, properties, user, groups, permisssions etc
            $fp = $dir.DIRECTORY_SEPARATOR.'method.settings.php';
            if( !is_file($fp) ) throw new Exception(lang('error_internal', 807));
            require_once $fp;
        }
        catch( Throwable $t ) {
            die('Error: '.$t->GetMessage());
        }
    }

    /**
     * @ignore
     * @param array $version_info
     * @throws Exception
     */
    private function do_upgrade(array $version_info)
    {
//        global $CMS_VERSION; don't pollute globals

//        $info = $this->get_wizard()->get_data('version_info');
//        $CMS_VERSION = $info['version'];

        // get the list of all available versions that this upgrader knows about
        $app = get_app();
        $dir = dirname(__DIR__2).DIRECTORY_SEPARATOR.'upgrade';
        if( !is_dir($dir) ) throw new Exception(lang('error_internal', 810));
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal', 811));

        $dh = opendir($dir);
        if( !$dh ) throw new Exception(lang('error_internal', 812));
        $versions = [];
        while( ($file = readdir($dh)) !== false ) {
            if( $file == '.' || $file == '..' ) continue;
            if( is_dir($dir.DIRECTORY_SEPARATOR.$file) && (is_file("$dir/$file/MANIFEST.DAT") || is_file("$dir/$file/MANIFEST.DAT.gz")) ) $versions[] = $file;
        }
        closedir($dh);
        if( $versions ) usort($versions,'version_compare');

        $destconfig = $this->get_wizard()->get_data('config');
        if( !$destconfig ) throw new Exception(lang('error_internal', 820));

        $choices = $this->get_wizard()->get_data('sessionchoices');
        if( !$choices ) throw new Exception(lang('error_internal', 821));

        $this->write_config($destconfig, false);

        // setup and initialize the CMSMS API's
        require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        AppState::add_state(AppState::STATE_INSTALL);
        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
        if( is_file($fp) ) {
            include_once $fp;
        }
        else {
            require_once $destdir.DIRECTORY_SEPARATOR.'include.php';
        }

        // setup database connection
        $db = $this->db_connect($destconfig);
        $smarty = smarty(); // this too in-scope for inclusions

        try {
            // ready to do the upgrading now (in a loop)
            // only perform upgrades for the versions known by the installer that are greater than what is installed.
            $current_version = $version_info['version'];
            foreach( $versions as $ver ) {
                if( version_compare($current_version,$ver) >= 0 ) continue;
                $fp = "$dir/$ver/upgrade.php";
                if( !is_file($fp) ) continue;
                include_once $fp;
            }
        }
        catch( Throwable $t ) {
            $this->error($t->GetMessage());
        }

        // some settings should be automatically updated after every upgrade
        $corenames = $app->get_config()['coremodules'];
        $cores = implode(',',$corenames);
        $schema = $app->get_dest_schema();
        $arr = [
            'coremodules' => $cores, // aka ModuleOperations::CORENAMES_PREF
            'schema_version' => $schema,
        ];
        if( !empty($choices['supporturl']) ) {
            $arr['site_help_url'] = $choices['supporturl'];
        }
        foreach ($arr as $name=>$val) {
            cms_siteprefs::set($name, $val);
        }
    }

    private function do_freshen()
    {
        // nothing here
    }

    private function write_config(array $destconfig, bool $install = true)
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if( !$destdir ) throw new Exception(lang('error_internal', 831));

        if( $install ) {
            $this->message(lang('install_createconfig'));

            $app_config = $app->get_config();
            $admin = ( !empty($app_config['admin_path']) && $app_config['admin_path'] != 'admin' ) ? $app_config['admin_path'] : '';
            //TODO ensure site-root-relative path (no leading separator) for these
            $assets = ( !empty($app_config['assets_path']) && $app_config['assets_path'] != 'assets' ) ? $app_config['assets_path'] : '';
            $tags = ( !empty($app_config['usertags_path']) && $app_config['usertags_path'] != 'assets/user_plugins' ) ? $app_config['usertags_path'] : ''; //TODO any separator and/or just 'user_plugins'
        }
        else {
            $admin = null; // filter it out
            $assets = null;
            $tags = null;
        }

        $host = trim($destconfig['db_hostname']);
        $name = trim($destconfig['db_name']);
        if( empty($destconfig['db_port']) ) {
            $port = "";
        }
        else {
            $port = (int)$destconfig['db_port'];
            if( $port < 1 ) $port = "";
        }
        $user = trim($destconfig['db_username']);
        $pass = trim($destconfig['db_password']);
        if( empty($destconfig['db_prefix']) ) {
            $pref = 'cms_';
        }
        else {
            $pref = trim($destconfig['db_prefix']);
        }

        $qvar = (!empty($destconfig['query_var'])) ? trim($destconfig['query_var']) : '';
        $zone = (!empty($destconfig['timezone'])) ? trim($destconfig['timezone']) : '';
        $set = $zone ? true : "";

        $params = array_filter([
            'admin_path' => "$admin",
            'assets_path' => "$assets",
            'db_hostname' => "$host",
            'db_name' => "$name",
            'db_port' => $port,
            'db_username' => "$user",
            'db_password' => "$pass",
            'db_prefix' => "$pref",
            'query_var' => "$qvar",
            'usertags_path' => "$tags",
            'timezone' => "$zone",
            'set_db_timezone' => $set,
            'set_names' => true,
        ], function($v){ return $v !== ""; });

        // get the system config instance
        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
        require_once $fp.'misc.functions.php';
        require_once $fp.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        require_once $fp.'classes'.DIRECTORY_SEPARATOR.'class.AppConfig.php';
        $config = AppConfig::get_instance();

        AppState::add_state(AppState::STATE_INSTALL); //enable $config property-setting
        $config->merge($params);
        if( $install ) {
            $config->save();
        }
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
                throw new Exception(lang('error_internal', 840));
            }
        }
        catch( Throwable $t ) {
            $this->error($t->GetMessage());
        }

        $this->finish();
    }
} // class
