<?php
#Define session constants
#Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

$config = cms_config::get_instance();

/**
 * Where private cachable files can be written.
 */
define('TMP_CACHE_LOCATION',$config['tmp_cache_location']);

/**
 * The smarty template compile directory.
 */
define('TMP_TEMPLATES_C_LOCATION',$config['tmp_templates_c_location']);

/**
 * Where public (browsable) cachable files can be written.
 */
define('PUBLIC_CACHE_LOCATION',$config['public_cache_location']);

/**
 * The URL for public cachable files.
 */
define('PUBLIC_CACHE_URL',$config['public_cache_url']);

/**
 * Whether CMSMS is in debug mode.
 */
define('CMS_DEBUG',$config['debug']);

/**
 * Where CMSMS is installed.
 */
define('CMS_ROOT_PATH',$config['root_path']);

/**
 * Where admin stuff is stored.
 */
define('CMS_ADMIN_PATH',$config['root_path'] .DIRECTORY_SEPARATOR. $config['admin_dir']);

/**
 * Where non-core assets are stored.
 */
define('CMS_ASSETS_PATH',$config['assets_path']);

/**
 * The 'top' directory where javascript files are stored
 * @since 2.3
 */
define('CMS_SCRIPTS_PATH',$config['root_path'].DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'js');

/**
 * The site root URL.
 */
define('CMS_ROOT_URL',$config['root_url']);

/**
 * The site assets URL.
 * @since 2.3
 */
define('CMS_ASSETS_URL',$config['assets_url']);

/**
 * The 'top' URL where javascript files are stored
 * @since 2.3
 */
define('CMS_SCRIPTS_URL',$config['root_url'].'/lib/js');

/**
 * The site uploads URL.
 */
define('CMS_UPLOADS_URL',$config['uploads_url']);

/**
 * The database table prefix.
 */
if( !isset($CMS_INSTALL_PAGE) ) {
    define('CMS_DB_PREFIX',$config['db_prefix']);
}

if( CMS_DEBUG ) {
    //for async DEBUG
    define('ASYNCLOG', TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'debug.log');
}

/*
 * Preview-page indicators.
*/
const CMS_PREVIEW = '__cms_preview__';
const CMS_PREVIEW_TYPE = '__cms_preview_type__';
const CMS_PREVIEW_PAGEID = -100;
const __CMS_PREVIEW_PAGE__ = -100; //deprecated since 2.3

/**
 * popup-menus css class.
 * Context-menu class(es) with this name must be established in each admin theme's css file(s)
 * @since 2.3
 * TODO consider supporting a config file parameter
 */
const CMS_POPUPCLASS = 'ContextMenu';
