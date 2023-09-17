<?php
namespace cms_installer\wizard;

//use CMSMS\Crypto; cryption is too messy for installer use!
//use function cms_installer\GetDb;
//use cms_installer\wizard;
//use RuntimeException;
use cms_installer\wizard\wizard_step;
use CMSMS\AppConfig;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\Database\Connection;
use CMSMS\Lone;
use Exception;
use Throwable;
use const CMS_DB_PREFIX;
use function cms_installer\get_app;
use function cms_installer\lang;
use function cms_installer\redirect;
use function cms_installer\smarty;

class wizard_step8 extends wizard_step
{
    protected function display()
    {
        $wiz = $this->get_wizard();
        $action = $wiz->get_data('action');
        $smarty = smarty();
        if ($action != 'freshen') {
            parent::display();
            $smarty->assign('next_url', $wiz->next_url());
            $smarty->display('wizard_step8.tpl');
        }

        try {
            switch ($action) {
                case 'install':
                    $this->do_install();
                    break;
                case 'upgrade':
                    $tmp = $wiz->get_data('version_info');
                    if (!$tmp) {
                        throw new Exception(lang('error_internal', 810));
                    }
                    // finally, all relevant 'upgrade.php' scripts
                    $this->do_upgrade($tmp);
                    break;
                case 'freshen':
                    $this->do_freshen();
                    $url = $wiz->next_url();
                    redirect($url);
                    break;
                default:
                    throw new Exception(lang('error_internal', 811));
            }
        } catch (Throwable $t) {
            $s = $this->forge_url;
            if ($s) {
                $s = '<br>'.lang('error_notify', $s);
            }
            $smarty->assign('error', $t->GetMessage().$s);
        }

        $this->finish();
    }

    /* *
     * Get a handle to the system database.
     * Without using App::GetDb(), which doesn't accept $config data
     * and effectively assumes $config (including db connection params)
     * is already set. But here it aint so!
     *
     * @param array $destconfig parameters for db connection
     * @return mixed Connection-class object | error message string
     */
/*    private function db_connect(array $destconfig)
    {
//      if (!defined('CMS_DB_PREFIX')) define('CMS_DB_PREFIX', $destconfig['db_prefix']);
        require_once dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'dbaccessor.functions.php';
        try {
            $db = GetDb($destconfig);
        }
        catch (Throwable $t) {
            return $t->getMessage();
        }
        Lone::insert('Db', $db)
        return $db;
    }
*/
    /**
     * Setup and initialize the CMSMS API's
     * @param string $destdir filepath of root/top-directory of the installation
     */
    private function connect_to_cmsms(string $destdir)
    {
        require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
        AppState::add(AppState::INSTALL);

        $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
        if (is_file($fp)) {
            include_once $fp;
        } else {
            require_once $destdir.DIRECTORY_SEPARATOR.'include.php';
        }
    }

    private function conform_langs(wizard $wiz, Connection $db)
    {
        $choices = $wiz->get_data('sessionchoices');
        if (!$choices) {
            throw new Exception(lang('error_internal', 815));
        }
        $havelangs = [-3 => '', -2 => 'en_US'] + $choices['extlanguages'];
        $filler = str_repeat('?,', count($havelangs) - 1);
        $pref = CMS_DB_PREFIX;
        $sql = "UPDATE {$pref}siteprefs SET sitepref_value='' WHERE sitepref_name='frontendlang' AND sitepref_value NOT IN({$filler}?)";
        $db->execute($sql, $havelangs);
        $sql = "UPDATE {$pref}userprefs SET value='' WHERE preference='default_cms_language' AND value NOT IN({$filler}?)";
        $db->execute($sql, $havelangs);
    }

    private function write_config(array $destconfig, bool $install = true)
    {
        $app = get_app();
        $destdir = $app->get_destdir();
        if (!$destdir) {
            throw new Exception(lang('error_internal', 818));
        }
        if ($install) {
            $this->message(lang('install_createconfig'));

            $app_config = $app->get_config();
            $admin = (!empty($app_config['admin_path']) && $app_config['admin_path'] != 'admin') ? $app_config['admin_path'] : '';
            //TODO ensure site-root-relative path (no leading separator) for these
            $assets = (!empty($app_config['assets_path']) && $app_config['assets_path'] != 'assets') ? $app_config['assets_path'] : '';
            $tags = (!empty($app_config['usertags_path']) && $app_config['usertags_path'] != 'assets/user_plugins') ? $app_config['usertags_path'] : ''; //TODO any separator and/or just 'user_plugins'
        } else {
            $admin = null; // filter it out
            $assets = null;
            $tags = null;
        }

        $host = trim($destconfig['db_hostname']);
        $name = trim($destconfig['db_name']);
        if (empty($destconfig['db_port'])) {
            $port = '';
        } else {
            $port = (int)$destconfig['db_port'];
            if ($port < 1) {
                $port = '';
            }
        }
        $user = trim($destconfig['db_username']);
        $pass = trim($destconfig['db_password']);
        $props = [
            'db_hostname' => $host,
            'db_username' => $user,
            'db_password' => $pass,
            'db_name' => $name
        ];
        if ($port || is_numeric($port)) {
            $props['db_port'] = (int)$port;
        }
/*        $enc = json_encode($props, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        if (!defined('CMS_ROOT_PATH')) {
            define('CMS_ROOT_PATH', $destdir); // for the crypter
        }
        $raw = Crypto::encrypt_string($enc, '', 'internal');
        $creds = base64_encode($raw);
*/
        if (empty($destconfig['db_prefix'])) {
            $pref = 'cms_';
        } else {
            $pref = trim($destconfig['db_prefix']);
        }

        $qvar = (!empty($destconfig['query_var'])) ? trim($destconfig['query_var']) : '';
        $zone = (!empty($destconfig['timezone'])) ? trim($destconfig['timezone']) : ''; //possibly-changed zone
        $set = $zone ? true : '';

        $params = array_filter([
            'admin_path' => "$admin",
            'assets_path' => "$assets",
//          'db_credentials' => "$creds", // cryption from inside installer is too complicated
            'db_hostname' => "$host", //maybe omit
            'db_name' => "$name", //maybe
            'db_port' => $port, //maybe
            'db_username' => "$user", //maybe
            'db_password' => "$pass", //maybe
            'db_prefix' => "$pref",
            'query_var' => "$qvar",
            'usertags_path' => "$tags",
            'timezone' => "$zone",
            'set_db_timezone' => $set,
            'set_names' => true,
        ], function($v) { return $v !== ''; });
//DEBUG $params += ['debug' => true, 'deprecations' => true]; //DEBUG

        if (!$install && $set) {
            $wizard = $this->get_wizard();
            $config = $wizard->get_data('version_info');
            if ($zone == $config['config']['timezone']) {
                $set = false;
            }
        }
        if ($install || $set) {
            $fp = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
            require_once $fp.'misc.functions.php'; // careful re replication of installer-methods same-name and namespace, or ANY CONSTS!
            require_once $fp.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
            AppState::add(AppState::INSTALL); //enable $config property-setting
            require_once $fp.'classes'.DIRECTORY_SEPARATOR.'class.AppConfig.php';
            $config = AppConfig::get_instance();
            $config->merge($params); // record the changes for the rest of this request
            $config->save(); // and for later ones
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
//        try {
        if (!$destdir) {
            throw new Exception(lang('error_internal', 820));
        }
        $adminaccount = $wiz->get_data('adminaccount');
        if (!$adminaccount) {
            throw new Exception(lang('error_internal', 821));
        }
        $destconfig = $wiz->get_data('config');
        if (!$destconfig) {
            throw new Exception(lang('error_internal', 822));
        }
        $choices = $wiz->get_data('sessionchoices');
        if (!$choices) {
            throw new Exception(lang('error_internal', 823));
        }
/*
            // create temporary dummy config file, to enable database connection
            // during CMSMS init
            $dmycfg = $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'config.php'; OR $app->get_config()['config_file'] OR $wiz->get_data('version_info')[''config_file']

            $app_config = $app->get_config();
            $admin = (!empty($app_config['admin_path']) && $app_config['admin_path'] != 'admin') ? $app_config['admin_path'] : '';
            //TODO ensure absolute paths e.g. prepend destdir if needed
            $assets = (!empty($app_config['assets_path']) && $app_config['assets_path'] != 'assets') ? $app_config['assets_path'] : '';
            $tags = (!empty($app_config['usertags_path']) && $app_config['usertags_path'] != 'assets/user_plugins') ? $app_config['usertags_path'] : '';  //TODO any separator and/or just 'user_plugins'
            $host = trim($destconfig['db_hostname']);
            $name = trim($destconfig['db_name']);
            if (empty($destconfig['db_port'])) {
                $port = "''"; // filter it out
            }
            else {
                $port = (int)$destconfig['db_port'];
                if ($port < 1) $port = "''";
            }
            $user = trim($destconfig['db_username']);
            $pass = trim($destconfig['db_password']);
            if (empty($destconfig['db_prefix'])) {
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
'set_names' => true
], function(\$v){ return \$v !== ''; }) + (\$config ?? []);

EOS
            , LOCK_EX);

            $this->connect_to_cmsms($destdir);

            // now we can save the 'real' config file
            $this->write_config($destconfig);
            $fp = str_replace('config.php', 'bak.config.php', $dmycfg);
            @unlink($fp);
*/
        // workaround circularity: autoloading N/A until after connection after config saved
//            require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.Crypto.php';

        $this->write_config($destconfig);

        $this->connect_to_cmsms($destdir);
        // connect to the database, if possible
        //$db = $this->db_connect($destconfig);
        $db = Lone::get('Db');
        if (!is_object($db)) {
            throw new Exception($db);
        }
        $dir = dirname(__DIR__, 2); // 'lib'-relative
        // install main tables
        $fp = $dir.DIRECTORY_SEPARATOR.'method.schema.php';
        if (!is_file($fp)) {
            throw new Exception(lang('error_internal', 825));
        }
        require_once $fp;

        // install sequence tables
        $fp = $dir.DIRECTORY_SEPARATOR.'method.sequence.php';
        if (!is_file($fp)) {
            throw new Exception(lang('error_internal', 826));
        }
        require_once $fp;

        // site-places, properties, user, groups, permisssions etc
        $fp = $dir.DIRECTORY_SEPARATOR.'method.settings.php';
        if (!is_file($fp)) {
            throw new Exception(lang('error_internal', 827));
        }
        require_once $fp;
//        }
//        catch (Throwable $t) {
//            throw new Exception($t->GetMessage()); // re-throw for upstream
//        }
    }

    /**
     * Run all relevant 'last-step' (upgrade.php) scripts
     * Such scripts may if needed use capabilities from the perhaps newly-upgraded
     * CMSMS API from file-changes in step 7.
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
        $dir = dirname(__DIR__, 2).DIRECTORY_SEPARATOR.'upgrade';
        if (!is_dir($dir)) {
            throw new Exception(lang('error_internal', 850));
        }
        $destdir = $app->get_destdir();
        if (!$destdir) {
            throw new Exception(lang('error_internal', 851));
        }
        // workaround: no glob() if phar is running
        $dh = opendir($dir);
        if (!$dh) {
            throw new Exception(lang('error_internal', 852));
        }
        $versions = [];
        while (($name = readdir($dh)) !== false) {
            if ($name == '.' || $name == '..') {
                continue;
            }
            if (is_dir($dir.DIRECTORY_SEPARATOR.$name)) {
                $bp = $dir.DIRECTORY_SEPARATOR.$name.DIRECTORY_SEPARATOR.'MANIFEST.DAT';
                if (
                    is_file($bp) ||
                    is_file($bp.'.gz') ||
                    is_file($bp.'.bzip2') ||
                    is_file($bp.'.zip')
                ) {
                    $versions[] = $name;
                }
            }
        }
        closedir($dh);
        if ($versions) {
            usort($versions, 'version_compare');
        }

        $wiz = $this->get_wizard();
        $destconfig = $wiz->get_data('config');
        if (!$destconfig) {
            throw new Exception(lang('error_internal', 860));
        }
        $choices = $wiz->get_data('sessionchoices');
        if (!$choices) {
            throw new Exception(lang('error_internal', 861));
        }
        // workaround circularity: autoloading N/A until after connection after config saved
//        require_once $destdir.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.Crypto.php';
        $this->write_config($destconfig, false);

        // setup and initialize the currently-known CMSMS API's
        // perhaps newly upgraded (step 7), but not yet finalised cuz' upgrade-php's are about to be processed
        $this->connect_to_cmsms($destdir);
        // database connection for use by included scripts
//        $db = $this->db_connect($destconfig);
        $db = Lone::get('Db');

        $this->conform_langs($wiz, $db);

        // also in-scope for inclusions: the installer-smarty-instance for say error message assignment
        $smarty = smarty();

        try {
            // do upgrades for the versions known to the installer and
            // that are greater than the installed version
            $current_version = $version_info['version'];
            $pre = $dir.DIRECTORY_SEPARATOR;
            $post = DIRECTORY_SEPARATOR.'upgrade.php';
            foreach ($versions as $ver) {
                if (version_compare($current_version, $ver) >= 0) {
                    continue;
                }
                $fp = $pre.$ver.$post;
                if (is_file($fp)) {
                    include_once $fp;
                }
            }
        } catch (Throwable $t) {
            $s = $this->forge_url;
            if ($s) {
                $s = '<br>'.lang('error_notify', $s);
            }
            $this->error($t->GetMessage().$s); // TODO 'real' error report with button-affect etc
        }

        // some settings should be automatically updated after every upgrade
        $corenames = $app->get_config()['coremodules'];
        $cores = implode(',', $corenames);
        $schema = $app->get_dest_schema();
        $arr = [
            'coremodules' => $cores, // aka ModuleOperations::CORENAMES_PREF
            'schema_version' => $schema
        ];
        if (!empty($choices['supporturl'])) {
            $arr['site_help_url'] = $choices['supporturl'];
        }
        foreach ($arr as $name => $val) {
            AppParams::set($name, $val);
        }
    }

    private function do_freshen()
    {
        $destdir = get_app()->get_destdir();
        if (!$destdir) throw new Exception(lang('error_internal', 868));
        $wiz = $this->get_wizard();
        $destconfig = $wiz->get_data('config');
        if (!$destconfig) throw new Exception(lang('error_internal', 869));
        $config = $wiz->get_data('version_info');
        if ($config['config']['timezone'] != $destconfig['timezone']) {
            $this->write_config($destconfig, false);
            // cleanup any message-creation scripts
            for ($i = 0, $n = count(ob_list_handlers()); $i < $n; $i++) {
                ob_end_clean();
            }
        }
        $this->connect_to_cmsms($destdir);
//      $db = $this->db_connect($destconfig);
        $db = Lone::get('Db');
        $this->conform_langs($wiz, $db);
    }
} // class
