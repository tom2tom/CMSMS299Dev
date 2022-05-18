<?php
/*
Define system constants
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppConfig;

/**
 * System identifiers (from vars in version.php)
 */
//define('CMS_VERSION', $CMS_VERSION);
//define('CMS_VERSION_NAME', $CMS_VERSION_NAME);
//define('CMS_SCHEMA_VERSION', $CMS_SCHEMA_VERSION);

$config = AppConfig::get_instance(); // Lone etc not yet set up

// These 2 are before other $config accesses, in case CMS_DEPREC is needed there
/**
 * Whether CMSMS is in debug mode.
 */
define('CMS_DEBUG',$config['debug']);

/**
 * Whether to throw upon use of deprecated stuff.
 * @since 3.0
 */
define('CMS_DEPREC',CMS_DEBUG && $config['deprecations']);

/**
 * Where cachable system files can be written.
 */
define('TMP_CACHE_LOCATION',$config['tmp_cache_location']);

/**
 * The smarty template compile directory.
 */
define('TMP_TEMPLATES_C_LOCATION',$config['tmp_templates_c_location']);

/**
 * Where cachable non-system files can be written.
 * Distinct from TMP_CACHE_LOCATION to support distinct
 * ACL/permissions e.g. module-specific or user-specific
 */
define('PUBLIC_CACHE_LOCATION',$config['public_cache_location']);

/**
 * The URL for public cachable files.
 */
define('PUBLIC_CACHE_URL',$config['public_cache_url']);

/**
 * Where CMSMS is installed.
 */
define('CMS_ROOT_PATH',$config['root_path']);

/**
 * Where admin stuff is stored.
 */
define('CMS_ADMIN_PATH',$config['root_path'].DIRECTORY_SEPARATOR.$config['admin_dir']);

/**
 * Where non-core assets are stored.
 */
define('CMS_ASSETS_PATH',$config['assets_path']);

/**
 * Where user-plugin files are stored.
 * @since 3.0
 */
define('CMS_FILETAGS_PATH',$config['usertags_path']);

/**
 * The 'top' directory where javascript files are stored.
 * @since 3.0
 */
define('CMS_SCRIPTS_PATH',$config['root_path'].DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'js');

/**
 * Where theme data files are stored.
 * @since 3.0
 */
define('CMS_THEMES_PATH',$config['assets_path'].DIRECTORY_SEPARATOR.'themes');

/**
 * The site root URL.
 */
define('CMS_ROOT_URL',$config['root_url']);

/**
 * The site assets URL.
 * @since 3.0
 */
define('CMS_ASSETS_URL',$config['assets_url']);

/**
 * The 'top' URL where javascript files are stored.
 * @since 3.0
 */
define('CMS_SCRIPTS_URL',$config['root_url'].'/lib/js');

/**
 * The 'top' URL where theme data files are stored.
 * @since 3.0
 */
define('CMS_THEMES_URL',$config['assets_url'].'/themes');

/**
 * The site uploads URL.
 */
define('CMS_UPLOADS_URL',$config['uploads_url']);

/**
 * The database-table prefix.
 */
define('CMS_DB_PREFIX',$config['db_prefix']);

if( CMS_DEBUG ) {
    //for async DEBUG since 3.0
    define('ASYNCLOG', TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'debug.log');
}

const CMS_DEFAULT_VERSIONCHECK_URL = 'https://www.cmsmadesimple.org/latest_version.php';

/*
 * Something short, URL-compatible and never used as a 'real' URL-parameter
 * From CMSMS 1.5 (or before) to 3.0, this const has been '_s_','sp_','_sx_','_sk_','__c','_k_'
 * but changing its value is minimally useful
*/
const CMS_SECURE_PARAM_NAME = '_sk_';
const CMS_JOB_KEY = '_sk_jobtype'; // i.e. CMS_SECURE_PARAM_NAME.'jobtype'

const CMS_USER_KEY = '_userkey_';

/**
 * Preview-page identifiers.
 */
const CMS_PREVIEW = '__cms_preview__';
const CMS_PREVIEW_TYPE = '__cms_preview_type__';
const CMS_PREVIEW_PAGEID = -100;
const __CMS_PREVIEW_PAGE__ = -100; //deprecated since 3.0
