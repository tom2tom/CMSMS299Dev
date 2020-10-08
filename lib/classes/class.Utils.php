<?php
# Utilities class.
# Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;

use CMSMS\AdminTheme;
use CMSMS\AppConfig;
use CMSMS\AppSingle;
use CMSMS\Database\Connection;
use CMSMS\internal\Smarty;

/**
 * A class of static utility/convenience methods.
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 *
 * @final
 * @since 2.9
 * @since 1.9 as global-namespace cms_utils
 */
final class Utils
{
	// static properties here >> StaticProperties class ?
	/**
	 * @ignore
	 */
	private static $_vars;

	/**
	 * @ignore
	 */
	private function __construct() {}
	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get intra-request stored data.
	 *
	 * @since 1.9
	 * @param string $key The key to get.
	 * @return mixed The stored data, or null
	 */
	public static function get_app_data(string $key)
	{
		if( is_array( self::$_vars ) && isset(self::$_vars[$key]) ) return self::$_vars[$key];
	}

	/**
	 * Set data for later use.
	 *
	 * This method is typically used to store data for later use by another part of the application.
	 * This data is not stored in the session, so it only exists during the current request.
	 *
	 * @since 1.9
	 * @param string $key The name of this data.
	 * @param mixed  $value The data to store.
	 */
	public static function set_app_data(string $key,$value)
	{
		if( $key == '' ) return;
		if( !is_array(self::$_vars) ) self::$_vars = [];
		self::$_vars[$key] = $value;
	}

	/**
	 * Return the database connection singleton.
	 *
	 * @since 1.9
	 * @return mixed CMSMS\Database\Connection object or null
	 */
	public static function get_db() : Connection
	{
		return AppSingle::Db();
	}

	/**
	 * Return the application config singleton.
	 *
	 * @since 1.9
	 * @return AppConfig The global configuration object.
	 */
	public static function get_config() : AppConfig
	{
		return AppSingle::Config();
	}

	/**
	 * Return the application Smarty singleton.
	 *
	 * @see App::GetSmarty()
	 * @since 1.9
	 * @return Smarty handle to the Smarty object
	 */
	public static function get_smarty() : Smarty
	{
		return AppSingle::Smarty();
	}

	/**
	 * Return the current content object.
	 *
	 * This function will return NULL if called from an admin action
	 *
	 * @since 1.9
	 * @return mixed Content The current content object | null
	 */
	public static function get_current_content()
	{
		return AppSingle::App()->get_content_object();
	}

	/**
	 * Return the alias of the current page.
	 *
	 * This function will return NULL if called from an admin action
	 *
	 * @since 1.9
	 * @return mixed string|null
	 */
	public static function get_current_alias()
	{
		$obj = AppSingle::App()->get_content_object();
		if( $obj ) return $obj->Alias();
	}

	/**
	 * Return the id of the current page
	 *
	 * This function will return NULL if called from an admin action
	 *
	 * @since 1.9
	 * @return mixed int|null
	 */
	public static function get_current_pageid()
	{
		return AppSingle::App()->get_content_id();
	}

	/**
	 * Report whether a module is available.
	 *
	 * @see get_module(), SysDataCache::get_instance()->get('modules')
	 * @author calguy1000
	 * @since 1.11
	 * @param string $name The module name
	 * @return bool
	 */
	public static function module_available(string $name) : bool
	{
		return AppSingle::ModuleOperations()->IsModuleActive($name);
	}

	/**
	 * Return an installed module object.
	 *
	 * If a version string is passed, a matching object will only be returned IF
	 * the installed version is greater than or equal to the supplied version.
	 *
	 * @see version_compare()
	 * @see ModuleOperations::get_module_instance()
	 * @since 1.9
	 * @param string $name The module name
	 * @param string $version An optional version string
	 * @return mixed CMSModule The matching module object or null
	 */
	public static function get_module(string $name,string $version = '')
	{
		return AppSingle::ModuleOperations()->get_module_instance($name,$version);
	}

	/**
	 * Return the appropriate WYSIWYG module.
	 *
	 * For frontend requests this method will return the currently selected
	 * frontend WYSIWYG or null if none is selected.
	 * For admin requests this method will return the user's selected
	 * WYSIWYG module, or null.
	 *
	 * @since 1.10
	 * @param mixed $module_name Optional module name | null Default null
	 * @return mixed CMSModule | null
	 */
	public static function get_wysiwyg_module($module_name = null)
	{
		return AppSingle::ModuleOperations()->GetWYSIWYGModule($module_name);
	}

	/**
	 * Return the currently-selected syntax highlight module.
	 *
	 * @since 1.10
	 * @author calguy1000
	 * @return mixed CMSModule | null
	 */
	public static function get_syntax_highlighter_module()
	{
		return AppSingle::ModuleOperations()->GetSyntaxHighlighter();
	}

	/**
	 * Return the currently selected search module.
	 *
	 * @since 1.10
	 * @author calguy1000
	 * @return mixed CMSModule | null
	 */
	public static function get_search_module()
	{
		return AppSingle::ModuleOperations()->GetSearchModule();
	}

	/**
	 * Return the currently-selected filepicker module.
	 *
	 * @since 2.2
	 * @author calguy1000
	 * @return mixed CMSModule | null
	 */
	public static function get_filepicker_module()
	{
		return AppSingle::ModuleOperations()->GetFilePickerModule();
	}

	/**
	 * Attempt to retrieve the IP address of the connected user.
	 * This function attempts to compensate for proxy servers.
	 *
	 * @author calguy1000
	 * @since 1.10
	 * @return mixed IP address in dotted notation | null
	 */
	public static function get_real_ip()
	{
		$ip = $_SERVER['REMOTE_ADDR'];
		if (empty($ip) && !empty($_SERVER['HTTP_CLIENT_IP'])) {
			$ip = $_SERVER['HTTP_CLIENT_IP'];
		}
		elseif (empty($ip) && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
			$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
		}

		if( filter_var($ip,FILTER_VALIDATE_IP) ) return $ip;

		return null;
	}

	/**
	 * Return the current theme object or a named theme object.
	 * Only valid during an admin request.
	 *
	 * @author calguy1000
	 * @since 1.11
	 * @param mixed $name Since 2.3 Optional theme name. Default ''
	 * @return mixed AdminTheme derived object, or null
	 */
	public static function get_theme_object($name = '')
	{
		return AdminTheme::get_instance($name);
	}
} // class
