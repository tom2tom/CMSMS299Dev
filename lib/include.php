<?php
/*
Set up infrastructure for processing a request
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 2 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//use CMSMS\AuditOperations;
use CMSMS\App;
use CMSMS\AppConfig;
use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Database\DatabaseConnectionException;
use CMSMS\Events;
use CMSMS\internal\ModulePluginOperations;
use CMSMS\NlsOperations;
use CMSMS\RequestParameters;
use CMSMS\SysDataCacheDriver;

/**
 * This file is intended for, and supported for use in, core CMSMS operations only.
 * It is not intended for use by applications to set up access to CMSMS API's.
 *
 * This file is included in every page. It does all setup functions including
 * importing additional functions/classes, setting up sessions and nls, and
 * construction of various important variables like $gCms.
 * In many cases, variable $CMS_APP_STATE should be set locally, before this file
 * is included. In such cases, the AppState class would need to be loaded there.
 *
 * @package CMS
 */

$dirpath = __DIR__.DIRECTORY_SEPARATOR;
if (isset($CMS_APP_STATE)) { //i.e. AppState class was included elsewhere
    AppState::add_state($CMS_APP_STATE);
} else {
    require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
}
$installing = AppState::test_state(AppState::STATE_INSTALL);
define('CONFIG_FILE_LOCATION', dirname(__DIR__).DIRECTORY_SEPARATOR.'config.php');
if (!$installing && (!is_file(CONFIG_FILE_LOCATION) || filesize(CONFIG_FILE_LOCATION) < 100)) {
    die('FATAL ERROR: config.php file not found or invalid');
}

require_once $dirpath.'misc.functions.php'; // system-independent methods
// DEBUG require_once $dirpath.'version.php'; // some defines
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.AppConfig.php'; // used in defines setup
require_once $dirpath.'version.php'; // some defines
require_once $dirpath.'defines.php'; // populate relevant defines (uses AppConfig instance)
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.App.php'; // used in autoloader
require_once $dirpath.'module.functions.php'; // used in autoloader
require_once $dirpath.'autoloader.php';  //uses defines, modulefuncs and (for module-class loads) CmsApp::get_instance()
require_once $dirpath.'vendor'.DIRECTORY_SEPARATOR.'autoload.php'; // Composer's autoloader makes light work of 'foreign' classes
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.AppSingle.php'; // uses cms_autoloader()
// begin to populate the singletons cache
$_app = App::get_instance(); // for use in this file | upstream, not downstream
AppSingle::insert('App', $_app); // cache this singleton like all others
AppSingle::insert('CmsApp', $_app); // an alias for the oldies
$config = AppConfig::get_instance(); // this object was already used during defines-processing, above
//AppSingle::insert('AppConfig', $config); // now we can cache it with other singletons
AppSingle::insert('Config', $config); // and an alias
//AppSingle::insert('cms_config', $config); // and another
//AppSingle::insert('AuditOperations', AuditOperations::get_instance()); //audit() needs direct access to this class

// check for valid inclusion
$includer = $_SERVER['SCRIPT_FILENAME'] ?? '';
if (!$includer) {
    $includer = reset(get_included_files());
}
switch (basename($includer, '.php')) {
    case 'index':
    case 'moduleinterface':
        break;
    default: // handle admin(however named)/*, installer/*, tests/*
        if (dirname($includer) == CMS_ROOT_PATH.DIRECTORY_SEPARATOR.$config['admin_dir']) { break; }
        if ($installing || strpos($includer, 'phar_installer'.DIRECTORY_SEPARATOR !== false)) { break; }
//        if (0) { break; } //TODO valid others == tests etc
        exit;
}

require_once $dirpath.'page.functions.php'; // system-dependent methods
$db = $_app->GetDb();
AppSingle::insert('Db', $db); // easier retrieval
require_once $dirpath.'compat.functions.php'; // old function and/or class aliases
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.CmsException.php'; // bundle of exception-classes in 1 file

$params = RequestParameters::get_request_values(
    [CMS_JOB_KEY,'showtemplate','suppressoutput']
);
//if (!$params) {
//    return; //CHECKME async job ok?
//}

if ($params[CMS_JOB_KEY] !== null) {
    $CMS_JOB_TYPE = min(max((int)$params[CMS_JOB_KEY], 0), 2);
} elseif ($params['showtemplate'] == 'false' || $params['suppressoutput'] !== null) {
    // undocumented, deprecated, output-suppressors
    $CMS_JOB_TYPE = 1;
} else {
    // normal output
    $CMS_JOB_TYPE = 0;
}
// since 2.99 value 0|1|2 indicates the type of request, hence appropriate inclusions
$_app->JOBTYPE = $CMS_JOB_TYPE;

if ($CMS_JOB_TYPE < 2) {
    if ($CMS_JOB_TYPE == 0) {
        require_once $dirpath.'placement.functions.php';
    }
    require_once $dirpath.'translation.functions.php';
}

debug_buffer('Finished loading basic files');

if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['QUERY_STRING'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
}

//AuditOperations::get_instance()->init();

// Set the timezone
if ($config['timezone']) @date_default_timezone_set(trim($config['timezone']));

if ($config['debug']) {
    @ini_set('display_errors',1);
    @error_reporting(E_ALL);
}

$administering = AppState::test_state(AppState::STATE_ADMIN_PAGE);
if ($administering) {
    setup_session();
}

cms_siteprefs::setup();

$cache = AppSingle::SysDataCache();
// deprecated since 2.99 useless, saves no time | effort
$obj = new SysDataCacheDriver('schema_version', function()
    {
        $out = @constant('CMS_SCHEMA_VERSION'); //null during installation - E_WARNING error if not defined
        return ($out) ? $out : (int) AppParams::get('schema_version');
    });
$cache->add_cachable($obj);
$obj = new SysDataCacheDriver('modules', function()
    {
        $db = AppSingle::Db();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'modules';
        return $db->GetAssoc($query); // Keyed by module_name
     });
$cache->add_cachable($obj);
$obj = new SysDataCacheDriver('module_deps', function()
    {
        $db = AppSingle::Db();
        $query = 'SELECT parent_module,child_module,minimum_version FROM '.CMS_DB_PREFIX.'module_deps ORDER BY parent_module';
        $tmp = $db->GetArray($query);
        if (!is_array($tmp) || !$tmp) return '-';  // special value so that we actually return something to cache.
        $out = [];
        foreach ($tmp as $row) {
            $out[$row['child_module']][$row['parent_module']] = $row['minimum_version'];
        }
        return $out;
    });
$cache->add_cachable($obj);

if ($CMS_JOB_TYPE < 2) {
    $obj = new SysDataCacheDriver('latest_content_modification', function()
        {
            $db = AppSingle::Db();
            $query = 'SELECT modified_date FROM '.CMS_DB_PREFIX.'content ORDER BY IF(modified_date, modified_date, create_date) DESC';
            $tmp = $db->GetOne($query);
            return $db->UnixTimeStamp($tmp);
        });
    $cache->add_cachable($obj);
    $obj = new SysDataCacheDriver('default_content', function()
        {
            $db = AppSingle::Db();
            $query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE default_content = 1';
            return $db->GetOne($query);
        });
    $cache->add_cachable($obj);

    // the pages flat list
    $obj = new SysDataCacheDriver('content_flatlist', function()
        {
            $query = 'SELECT content_id,parent_id,item_order,content_alias,active FROM '.CMS_DB_PREFIX.'content ORDER BY hierarchy';
            $db = AppSingle::Db();
            return $db->GetArray($query);
        });
    $cache->add_cachable($obj);

    // hence the tree
    $obj = new SysDataCacheDriver('content_tree', function()
        {
            $flatlist = AppSingle::SysDataCache()->get('content_flatlist');
            $tree = cms_tree_operations::load_from_list($flatlist);
            return $tree;
        });
    $cache->add_cachable($obj);

    // hence the flat/quick list
    $obj = new SysDataCacheDriver('content_quicklist', function()
        {
            $tree = AppSingle::SysDataCache()->get('content_tree');
            return $tree->getFlatList();
        });
    $cache->add_cachable($obj);
}

// other SysDataCache contents
Events::setup();
ModulePluginOperations::setup();

// Attempt to override the php memory limit
if ($config['php_memory_limit']) ini_set('memory_limit',trim($config['php_memory_limit']));

// Load them into the usual variables.  This'll go away a little later on.
if (!$installing) {
    try {
        debug_buffer('Initialize database');
        $_app->GetDb();
        debug_buffer('Finished initializing database');
    }
    catch (DatabaseConnectionException $e) {
        die('Sorry, something has gone wrong.  Please contact a site administrator. <em>('.get_class($e).')</em>');
    }
}

// Fix for IIS (and others) to make sure REQUEST_URI is filled in
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
    if (isset($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
}

if (!$installing) {
    $str_mask = AppParams::get('global_umask');
    if ($str_mask) {
        // Set a umask
        if ($str_mask[0] == '0') { umask(octdec($str_mask)); }
        else { umask((int)$str_mask); }
    }

/*  $modops = AppSingle::ModuleOperations();
    // After autoloader & modules
    $tmp = $modops->GetCapableModules(CoreCapabilities::JOBS_MODULE);
    if ($tmp) {
        $mod_obj = $modops->get_module_instance($tmp[0]); //NOTE not $modinst !
        $_app->jobmgrinstance = $mod_obj; //cache it
        if ($CMS_JOB_TYPE == 0) {
            $callback = $tmp[0].'::begin_async_work';
            Events::AddDynamicHandler('Core', 'PostRequest', $callback);
        }
    }
*/
//    Events::AddDynamicHandler('Core', 'PostRequest', '\\CMSMS\\internal\\JobOperations::begin_async_work'); // TODO >> static event
}

if ($CMS_JOB_TYPE < 2) {
    if ($administering) {
        // Setup language stuff.... will auto-detect languages (launch only to admin at this point)
        NlsOperations::set_language();
    }

    if (!$installing) {
        debug_buffer('Initialize Smarty');
        $smarty = AppSingle::Smarty();
        debug_buffer('Finished initializing Smarty');
//      $smarty->assignGlobal('sitename', cms_siteprefs::get('sitename', 'CMSMS Site'));
    }
}
