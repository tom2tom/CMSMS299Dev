<?php
#setup classes, includes etc for request processing
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

/**
 * This file is included in every page.  It does all setup functions including
 * importing additional functions/classes, setting up sessions and nls, and
 * construction of various important variables like $gCms.
 *
 * This file cannot be included by third party applications to create access to CMSMS API's.
 * It is intended for and supported for use in CMSMS applications only.
 *
 * @package CMS
 */

/*
 * Special variables that may be set before this file is included which will influence its behavior.
 *
 * DONT_LOAD_DB = Indicates that the database should not be initialized and any database related functions should not be called
 * DONT_LOAD_SMARTY = Indicates that smarty should not be initialized, and no smarty related variables assigned.
 * CMS_INSTALL_PAGE - Indicates that the file was included from the CMSMS Installation/Upgrade process
 * CMS_PHAR_INSTALLER - Indicates that the file was included from the CMSMS PHAR based installer (note: CMS_INSTALL_PAGE will also be set).
 * CMS_ADMIN_PAGE - Indicates that the file was included from an admin side request.
 * CMS_LOGIN_PAGE - Indicates that the file was included from the admin login form.
 * CMS_JOB_TYPE - Since 2.3 Value 0|1|2 indicates the type of request, hence appropriate inclusions
 */
//use CMSMS\internal\ModulePluginOperations;

use CMSMS\AuditOperations;
use CMSMS\ContentOperations;
use CMSMS\Database\DatabaseConnectionException;
use CMSMS\Events;
use CMSMS\HookManager;
use CMSMS\internal\global_cachable;
use CMSMS\internal\global_cache;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;

global $CMS_INSTALL_PAGE, $CMS_ADMIN_PAGE, $DONT_LOAD_DB, $DONT_LOAD_SMARTY;

define('CONFIG_FILE_LOCATION', dirname(__DIR__).DIRECTORY_SEPARATOR.'config.php');

if (!isset($CMS_INSTALL_PAGE) && (!is_file(CONFIG_FILE_LOCATION) || filesize(CONFIG_FILE_LOCATION) < 100)) {
    die('FATAL ERROR: config.php file not found or invalid');
}

if (!isset($CMS_JOB_TYPE)) $CMS_JOB_TYPE = 0;

const CMS_DEFAULT_VERSIONCHECK_URL = 'https://www.cmsmadesimple.org/latest_version.php';
const CMS_SECURE_PARAM_NAME = '_sk_';
const CMS_USER_KEY = '_userkey_';

$dirname = __DIR__.DIRECTORY_SEPARATOR;
// include some stuff
require_once $dirname.'version.php'; // some defines
require_once $dirname.'classes'.DIRECTORY_SEPARATOR.'class.cms_config.php';
require_once $dirname.'classes'.DIRECTORY_SEPARATOR.'class.CmsException.php';
require_once $dirname.'misc.functions.php'; //some used in defines setup
require_once $dirname.'defines.php'; //populate relevant defines
require_once $dirname.'classes'.DIRECTORY_SEPARATOR.'class.CmsApp.php'; //used in autoloader
require_once $dirname.'module.functions.php'; //some used in autoloader
require_once $dirname.'autoloader.php';
require_once $dirname.'vendor'.DIRECTORY_SEPARATOR.'autoload.php'; //CHECKME Composer support on production system ?
require_once $dirname.'compat.functions.php';
require_once $dirname.'page.functions.php';
if ($CMS_JOB_TYPE < 2) {
    require_once $dirname.'translation.functions.php';
}

debug_buffer('Finished loading basic files');

if (!isset($_SERVER['REQUEST_URI']) && isset($_SERVER['QUERY_STRING'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
}
// sanitize $_SERVER and $_GET
cleanArray($_SERVER);
cleanArray($_GET);

// Grab the current configuration & some define's
$_app = CmsApp::get_instance(); // for use in this file only.
$config = $_app->GetConfig();
AuditOperations::init();

// Set the timezone
if ($config['timezone']) @date_default_timezone_set(trim($config['timezone']));

if ($config['debug']) {
    @ini_set('display_errors',1);
    @error_reporting(E_ALL);
}

if (cms_to_bool(ini_get('register_globals'))) {
    die('FATAL ERROR: For security reasons register_globals must not be enabled for any CMSMS install.  Please adjust your PHP configuration settings to disable this feature.');
}

if (isset($CMS_ADMIN_PAGE)) {
    setup_session();

// TODO is this $CMS_JOB_TYPE-dependant ?
    function cms_admin_sendheaders($content_type = 'text/html',$charset = '')
    {
        // Language shizzle
        if (!$charset) $charset = NlsOperations::get_encoding();
        header("Content-Type: $content_type; charset=$charset");
    }
}

// some of these caches could be omitted per $CMS_JOB_TYPE, but probably won't be used anyway
$obj = new global_cachable('schema_version', function()
    {
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT version FROM '.CMS_DB_PREFIX.'version';
        return $db->GetOne($query);
    });
global_cache::add_cachable($obj);
$obj = new global_cachable('latest_content_modification', function()
    {
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT modified_date FROM '.CMS_DB_PREFIX.'content ORDER BY modified_date DESC';
        $tmp = $db->GetOne($query);
        return $db->UnixTimeStamp($tmp);
    });
global_cache::add_cachable($obj);
$obj = new global_cachable('default_content', function()
    {
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT content_id FROM '.CMS_DB_PREFIX.'content WHERE default_content = 1';
        return $db->GetOne($query);
    });
global_cache::add_cachable($obj);
$obj = new global_cachable('modules', function()
    {
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT * FROM '.CMS_DB_PREFIX.'modules ORDER BY module_name';
        return $db->GetArray($query);
     });
global_cache::add_cachable($obj);
$obj = new global_cachable('module_deps', function()
    {
        $db = CmsApp::get_instance()->GetDb();
        $query = 'SELECT parent_module,child_module,minimum_version FROM '.CMS_DB_PREFIX.'module_deps ORDER BY parent_module';
        $tmp = $db->GetArray($query);
        if (!is_array($tmp) || !$tmp) return '-';  // special value so that we actually return something to cache.
        $out = [];
        foreach( $tmp as $row) {
            $out[$row['child_module']][$row['parent_module']] = $row['minimum_version'];
        }
        return $out;
    });
global_cache::add_cachable($obj);
// other global caches
cms_siteprefs::setup();
Events::setup();

if ($CMS_JOB_TYPE < 2) {
    ContentOperations::setup_cache(); // various content-related global caches
}
// Attempt to override the php memory limit
if (isset($config['php_memory_limit']) && !empty($config['php_memory_limit'])) ini_set('memory_limit',trim($config['php_memory_limit']));

// Load them into the usual variables.  This'll go away a little later on.
if (!isset($DONT_LOAD_DB)) {
    try {
        debug_buffer('Initialize database');
        $_app->GetDb();
        debug_buffer('Finished initializing database');
    }
    catch( DatabaseConnectionException $e) {
        die('Sorry, something has gone wrong.  Please contact a site administrator. <em>('.get_class($e).')</em>');
    }
}

// Fix for IIS (and others) to make sure REQUEST_URI is filled in
if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'];
    if (isset($_SERVER['QUERY_STRING'])) $_SERVER['REQUEST_URI'] .= '?'.$_SERVER['QUERY_STRING'];
}

if (!isset($CMS_INSTALL_PAGE)) {
    // Set a umask
    $global_umask = cms_siteprefs::get('global_umask','');
    if ($global_umask != '') umask( octdec($global_umask));

    $modops = ModuleOperations::get_instance();
    // Load all non-lazy modules
//    $modops->LoadImmediateModules();
    $modops->InitModules(); //DEBUG
    // After autoloader & modules
    $tmp = $modops->get_modules_with_capability(CmsCoreCapabilities::JOBS_MODULE);
    if( $tmp ) {
        $mod_obj = $modops->get_module_instance($tmp[0]); //NOTE not $modinst !
        $_app->jobmgrinstance = $mod_obj; //cache it
        if ($CMS_JOB_TYPE == 0) {
            HookManager::add_hook('PostRequest', [$tmp[0], 'trigger_async_hook'], HookManager::PRIORITY_LOW);
        }
    }
}

if ($CMS_JOB_TYPE < 2) {
    // In case module lazy-loading is malformed, pre-register all module-plugins which are not recorded in the database
//    ModulePluginOperations::get_instance()->RegisterSessionPlugins();

    // Setup language stuff.... will auto-detect languages (launch only to admin at this point)
    if (isset($CMS_ADMIN_PAGE)) NlsOperations::set_language();

    if (!isset($DONT_LOAD_SMARTY)) {
        debug_buffer('Initialize Smarty');
        $smarty = $_app->GetSmarty();
        debug_buffer('Finished initializing Smarty');
        $smarty->assignGlobal('sitename', cms_siteprefs::get('sitename', 'CMSMS Site'));
    }
}

if (!isset($CMS_INSTALL_PAGE)) {
    require_once($dirname.'classes'.DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'class_compatibility.php');
}
