<?php
/*
System utilities class.
Copyright (C) 2010-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

//use CMSMS\DeprecationNotice;
use CMSMS\AdminTheme;
use CMSMS\AppConfig;
use CMSMS\CoreCapabilities;
use CMSMS\Database\Connection;
use CMSMS\internal\Smarty;
use CMSMS\SingleItem;
use Throwable;
//use const CMS_DEPREC;

/**
 * A class of static utility/convenience methods.
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 *
 * @final
 * @since 2.99
 * @since 1.9 as global-namespace cms_utils
 */
final class Utils
{
	// static properties here >> SingleItem property|ies ?
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
	 * @since 1.9
	 *
	 * @param string $key The key to get.
	 * @return mixed The stored data, or null
	 */
	public static function get_app_data(string $key)
	{
		if( is_array( self::$_vars ) && isset(self::$_vars[$key]) ) return self::$_vars[$key];
	}

	/**
	 * Cache intra-request data.
	 * The data only exists during the current request.
	 * This method is typically used to store data for later use by
	 * another part of the system.
	 * @since 1.9
	 *
	 * @param string $key The name of this data.
	 * @param mixed  $value The data to store.
	 */
	public static function set_app_data(string $key, $value)
	{
		if( $key == '' ) return;
		if( !is_array(self::$_vars) ) self::$_vars = [];
		self::$_vars[$key] = $value;
	}

	/**
	 * Return the database connection singleton.
	 * @see SingleItem::Db()
	 * @since 1.9
	 *
	 * @return mixed CMSMS\Database\Connection object | null
	 */
	public static function get_db() : Connection
	{
//		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\SingleItem::Db()'));
		return SingleItem::Db();
	}

	/**
	 * Return the application config singleton.
	 * @see SingleItem::Config();
	 * @since 1.9
	 *
	 * @return AppConfig The global configuration object.
	 */
	public static function get_config() : AppConfig
	{
//		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\SingleItem::Config()'));
		return SingleItem::Config();
	}

	/**
	 * Return the application Smarty singleton.
	 * @see SingleItem::Smarty()
	 * @since 1.9
	 *
	 * @return Smarty handle to the Smarty object
	 */
	public static function get_smarty() : Smarty
	{
//		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\SingleItem::Smarty()'));
		return SingleItem::Smarty();
	}

	/**
	 * Return the current content object.
	 * This function will return NULL if called from an admin action
	 * @since 1.9
	 *
	 * @return mixed Content The current content object | null
	 */
	public static function get_current_content()
	{
		return SingleItem::App()->get_content_object();
	}

	/**
	 * Return the alias of the current page.
	 * This function will return NULL if called from an admin action
	 * @since 1.9
	 *
	 * @return mixed string|null
	 */
	public static function get_current_alias()
	{
		$obj = SingleItem::App()->get_content_object();
		if( $obj ) return $obj->Alias();
	}

	/**
	 * Return the id of the current page
	 * This function will return NULL if called from an admin action
	 * @since 1.9
	 *
	 * @return mixed int|null
	 */
	public static function get_current_pageid()
	{
		return SingleItem::App()->get_content_id();
	}

	/**
	 * Report whether a module is available.
	 * @see get_module(), SingleItem::LoadedData()->get('modules')
	 * @since 1.11
	 *
	 * @param string $name The module name
	 * @return bool
	 */
	public static function module_available(string $name) : bool
	{
		return SingleItem::ModuleOperations()->IsModuleActive($name);
	}

	/**
	 * Return an installed module object.
	 * If a version string is passed, a matching object will only be returned
	 * if the installed version is greater than or equal to the specified version.
	 * @see version_compare()
	 * @see ModuleOperations::get_module_instance()
	 * @since 1.9
	 *
	 * @param string $name The module name
	 * @param string $version An optional version string
	 * @return mixed CMSModule The matching module object or null
	 */
	public static function get_module(string $name, string $version = '')
	{
		return SingleItem::ModuleOperations()->get_module_instance($name, $version);
	}

	/**
	 * Return the appropriate WYSIWYG module.
	 *
	 * For frontend requests this method will return the currently selected
	 * frontend WYSIWYG or null if none is selected.
	 * For admin requests this method will return the user's selected
	 * WYSIWYG module, or null.
	 * @since 1.10
	 *
	 * @param mixed $module_name Optional module name | null Default null
	 * @return mixed CMSModule | null
	 */
	public static function get_wysiwyg_module($module_name = null)
	{
		return SingleItem::ModuleOperations()->GetWYSIWYGModule($module_name);
	}

	/**
	 * Return the currently-selected syntax highlight module.
	 * @since 1.10
	 *
	 * @return mixed CMSModule | null
	 */
	public static function get_syntax_highlighter_module()
	{
		return SingleItem::ModuleOperations()->GetSyntaxHighlighter();
	}

	/**
	 * Return the currently selected search module.
	 * @since 1.10
	 *
	 * @return mixed CMSModule | null
	 */
	public static function get_search_module()
	{
		return SingleItem::ModuleOperations()->GetSearchModule();
	}

	/**
	 * Return the currently-selected filepicker module.
	 * @since 2.2
	 *
	 * @return mixed CMSModule | null
	 */
	public static function get_filepicker_module()
	{
		return SingleItem::ModuleOperations()->GetFilePickerModule();
	}

	/**
	 * Return the first-detected email-sender module (if any).
	 * @since 2.99
	 *
	 * @return mixed CMSModule | null
	 */
	public static function get_email_module()
	{
		$modnames = SingleItem::LoadedMetadata()->get('capable_modules', false, CoreCapabilities::EMAIL_MODULE);
		if ($modnames) {
			return SingleItem::ModuleOperations()->get_module_instance($modnames[0]);
		}
		return null;
	}

	/**
	 * Send email message(s) using the selected system email-sender,
	 *  if any, or else by PHP mail()
	 * @see https://www.php.net/manual/en/function.mail
	 * @since 2.99
	 *
	 * @param string $to one or more (in which case comma-separated)
	 *  destination(s), each like emailaddress or Name <emailaddress>
	 * @param string $subject plaintext subject
	 * @param string $message plaintext or html message body-content
	 * @param mixed array|string $additional_headers optional header(s)
	 *  for the message
	 * @param string $additional_params optional extra params for sendmail
	 *  if that's the currently-selected backend processor
	 * @return bool indicating message was accepted for delivery
	 */
	public static function send_email(string $to, string $subject, string $message,
	   $additional_headers = [], string $additional_params = '') : bool
	{
		$mod = self::get_email_module();
		if ($mod) {
			$classname = $mod->GetName().'\Mailer';
			try {
				$mailer = new $classname();
				return $mailer->send_simple($to, $subject, $message, $additional_headers, $additional_params);
			} catch (Throwable $t) {
				return false;
			}
		} else {
			return mail($to, $subject, $message, $additional_headers, $additional_params);
		}
	}

	/**
	 * Attempt to retrieve the IP address of the connected user.
	 * This function attempts to compensate for proxy servers.
	 * @since 1.10
	 *
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

		if( filter_var($ip, FILTER_VALIDATE_IP) ) return $ip;

		return null;
	}

	/**
	 * Return the current admin theme object or a named theme object.
	 * Only valid during an admin request.
	 * @since 1.11
	 *
	 * @param mixed $name Since 2.99 Optional theme name. Default ''
	 * @return mixed AdminTheme derived object, or null
	 */
	public static function get_theme_object($name = '')
	{
//		assert(empty(CMS_DEPREC), new DeprecationNotice('method', 'CMSMS\AdminTheme::get_instance($name)'));
		return AdminTheme::get_instance($name);
	}
} // class
