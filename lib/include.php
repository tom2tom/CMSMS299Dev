<?php
/*
Set up infrastructure for processing a request
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the
Free Software Foundation; either version 3 of that license, or (at your option)
any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//use CMSMS\LogOperations;
use CMSMS\AdminTheme;
use CMSMS\App;
use CMSMS\AppConfig;
use CMSMS\AppParams;
use CMSMS\AppState;
use CMSMS\Database\Connection;
use CMSMS\Database\DatabaseConnectionException;
use CMSMS\Events;
use CMSMS\internal\LoadedMetadata;
use CMSMS\internal\ModulePluginOperations;
use CMSMS\LoadedDataType;
use CMSMS\Lone;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;
use CMSMS\RequestParameters;
use CMSMS\RouteOperations;
use CMSMS\TreeOperations;
use function CMSMS\add_shutdown;
use function CMSMS\do_template_processing;
use function CMSMS\get_installed_schema_version;

/**
 * This file is intended for, and supported for use in, core CMSMS operations only.
 * It is not intended for use by applications to set up access to CMSMS API's.
 *
 * This file is included in every page. It does all setup functions including
 * importing additional functions/classes, setting up sessions and nls, and
 * construction of various important variables like $gCms.
 *
 * @package CMS
 */

$dirpath = __DIR__.DIRECTORY_SEPARATOR;
if (class_exists('CMSMS\AppState', false)) {
    $installing = AppState::test(AppState::INSTALL);
} else {
    require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
    $installing = false;
}

define('CONFIG_FILE_LOCATION', $dirpath.'config.php');
if (!$installing && (!is_file(CONFIG_FILE_LOCATION) || filesize(CONFIG_FILE_LOCATION) < 100)) {
    //TODO throw new RuntimeException(' message below ');
    exit('FATAL ERROR: config.php file not found or invalid');
}

require_once $dirpath.'misc.functions.php'; // system-independent methods
// DEBUG require_once $dirpath.'version.php'; // some defines
// TODO circularity: this might need various defines and/or DeprecationNotice
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.AppConfig.php'; // used in defines setup
// DEBUG require_once $dirpath.'version.php'; // some defines
require_once $dirpath.'defines.php'; // populate relevant defines (uses AppConfig instance)
if (CMS_DEPREC) {
    @ini_set('assert.exception', 1);
/*} else {
    @ini_set('zend.assertions', -1); TODO probably useless. From PHP doc:
If a process is started in production mode, zend.assertions cannot be changed at runtime...
If a process is started in development mode, zend.assertions cannot be set to -1 at runtime.
*/
}
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.App.php'; // used in autoloader
require_once $dirpath.'module.functions.php'; // used in autoloader
require_once $dirpath.'autoloader.php';  //uses defines, modulefuncs and (for module-class loads) Lone::get('App')
require_once $dirpath.'vendor'.DIRECTORY_SEPARATOR.'autoload.php'; // Composer's autoloader makes light work of 'foreign' classes
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'class.Lone.php'; // uses cms_autoloader()
// begin to populate the singletons cache
$_app = new App(); // for use in this file | upstream, not downstream
Lone::insert('App', $_app); // cache this singleton like all others
Lone::insert('CmsApp', $_app); // an alias for the oldies
$config = AppConfig::get_instance(); // this object was already used during defines-processing, above
Lone::insert('Config', $config); // now we can cache it with other singletons
//Lone::insert('AppConfig', $config); // and an alias
//Lone::insert('cms_config', $config); // and another

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
register_shutdown_function('CMSMS\run_shutters');
add_shutdown(500, 'CSMS\dbshutdown');

$db = new Connection($config);
Lone::insert('Db', $db);
require_once $dirpath.'compat.functions.php'; // old function- and/or class-aliases
//deprecated since 3.0 (at most): make old stuff available
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'Database'.DIRECTORY_SEPARATOR.'class.compatibility.php';
require_once $dirpath.'classes'.DIRECTORY_SEPARATOR.'library.Exception.php'; // bundle of exception-classes in 1 file, not auto-loadable

$params = RequestParameters::get_request_values(
    [CMS_JOB_KEY, 'showtemplate', 'suppressoutput']
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
// since 3.0 value 0|1|2 indicates the type of request, hence appropriate inclusions
$_app->JOBTYPE = $CMS_JOB_TYPE;
do_template_processing($CMS_JOB_TYPE < 2);

if ($CMS_JOB_TYPE < 2) {
    require_once $dirpath.'translation.functions.php';
}

debug_buffer('Finished loading basic files');

if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['QUERY_STRING'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
}

//LogOperations::get_instance()->init();

// Set the timezone
if ($config['timezone']) @date_default_timezone_set(trim($config['timezone']));

if ($config['debug']) {
    @ini_set('display_errors',1);
    @error_reporting(E_ALL);
}

// parameter used in cache construction
$val = AppParams::getraw('site_uuid');
Lone::set('app.site_uuid', $val);

AppParams::load_setup();

// setup CMS_VERSION, using the cache if relevant
$val = AppParams::get('cms_version');
if (!$val) {
    require_once $dirpath.'version.php'; // some defines
    $val = $CMS_VERSION ?? '0.0.0';
} else {
    $CMS_VERSION = $val; // some things rely on this global
}
define('CMS_VERSION', $val);

$administering = AppState::test(AppState::ADMIN_PAGE);
if ($administering) {
    setup_session();
}

$cache = Lone::get('LoadedData');
// deprecated since 3.0 useless, caching saves minimal time | effort
$obj = new LoadedDataType('schema_version', function() {
    return get_installed_schema_version();
});
$cache->add_type($obj);
if ($CMS_JOB_TYPE < 2) {
    $obj = new LoadedDataType('latest_content_modification', function() {
        $db = Lone::get('Db');
        $query = 'SELECT modified_date FROM '.CMS_DB_PREFIX.'content ORDER BY IF(modified_date, modified_date, create_date) DESC';
        $tmp = $db->getOne($query);
        return $db->UnixTimeStamp($tmp);
    });
    $cache->add_type($obj);
    $obj = new LoadedDataType('default_content', function() {
        $db = Lone::get('Db');
        $query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE default_content = 1';
        return $db->getOne($query);
    });
    $cache->add_type($obj);
/*
    // the pages flat list (depth-first array)
    $obj = new LoadedDataType('content_flatlist', function() {
        debug_buffer('Start loading content flatlist');
        $query = 'SELECT content_id,parent_id,content_alias,active FROM '.CMS_DB_PREFIX.'content ORDER BY hierarchy';
        $db = Lone::get('Db');
/*      if (constant('CMS_DEBUG')) {
            $tmp = $db->getArray($query);
            debug_buffer('End loading content flatlist');
            return $tmp;
        } else {
* /
            return $db->getArray($query);
//      }
    });
    $cache->add_type($obj);

    // hence the tree of ContentTree node-objects
    $obj = new LoadedDataType('content_tree', function(bool $force) {
        debug_buffer('Start loading content tree');
        $flatlist = Lone::get('LoadedData')->get('content_flatlist', $force);
/*        if (constant('CMS_DEBUG')) {
            $tmp = TreeOperations::load_from_list($flatlist);
            debug_buffer('End loading content tree');
            return $tmp;
        } else{
* /
            return TreeOperations::load_from_list($flatlist);
//      }
    });
    $cache->add_type($obj);

    // hence a flat array, each member like conntent_id => ContentTree object
    $obj = new LoadedDataType('content_quicklist', function(bool $force) {
        debug_buffer('Start loading content quicklist');
        $tree = Lone::get('LoadedData')->get('content_tree', $force);
        if (constant('CMS_DEBUG')) {
            $tmp = $tree->getFlatList(); // c.f. LoadedDataType('content_flatlist') ??
            debug_buffer('End loading content quicklist');
            return $tmp;
        } else {
            return $tree->getFlatList(); // c.f. LoadedDataType('content_flatlist') ??
        }
    });
    $cache->add_type($obj);
*/
}

// other LoadedData populators
Events::load_setup();
ModuleOperations::load_setup();
LoadedMetadata::load_setup();
RouteOperations::load_setup(); // only for f/e requests?
ModulePluginOperations::load_setup(); // sometimes needed for admin requests

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
        exit('Failed database connection. Please contact a site administrator. <em>('.get_class($e).')</em>');
    }
}

// Fix for IIS (and others) to ensure REQUEST_URI is present
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

/*  // After autoloader & modules
    $modnames = Lone::get('LoadedMetadata')->get('capable_modules', false, CoreCapabilities::JOBS_MODULE);
    if ($modnames) {
        $mod = Lone::get('ModuleOperations')->get_module_instance($modnames[0]);
        $_app->jobmgrinstance = $mod; //cache it
        if ($CMS_JOB_TYPE == 0) {
            $callable = $modnames[0].'::begin_async_work';
            Events::AddDynamicHandler('Core', 'PostRequest', $callable);
        }
    }
*/
//    Events::AddDynamicHandler('Core', 'PostRequest', 'CMSMS\internal\JobOperations::begin_async_work'); // TODO >> static event
}

if ($CMS_JOB_TYPE < 2) {
    if ($administering) {
        // Setup language stuff.... will auto-detect languages (launch only to admin at this point)
        NlsOperations::set_language();
        Lone::insert('Theme', AdminTheme::get_instance());
    }

    if (!$installing) {
        debug_buffer('Initialize Smarty');
        $smarty = Lone::get('Smarty');
        debug_buffer('Finished initializing Smarty');
//      $smarty->assignGlobal('sitename', AppParams::get('sitename', 'CMSMS Site'));
    }
}
