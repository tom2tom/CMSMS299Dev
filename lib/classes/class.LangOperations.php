<?php
#Translation functions/classesulp <ted@cmsmadesimple.org>
#Copyright (C) 2013-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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

namespace CMSMS;

use CMSMS\NlsOperations;
use const CMS_ADMIN_PATH;
use const CMS_ASSETS_PATH;
use const CMS_ROOT_PATH;
use function cms_module_path;
use function debug_to_log;

/**
 * A singleton class to provide simple, generic mechanism for dealing with languages
 * encodings, and locales.  This class does not handle translation strings.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell
 * @since 1.11
 */
final class LangOperations
{
	/**
	 * A constant for the core admin realm.
	 */
	const CMSMS_ADMIN_REALM = 'admin';

	/**
	 * @ignore
	 */
	private static $_langdata;


	/**
	 * @ignore
	 */
	private static $_do_conversions;

	/**
	 * @ignore
	 */
	private static $_allow_nonadmin_lang;

	/**
	 * @ignore
	 */
	private static $_current_realm = self::CMSMS_ADMIN_REALM;

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * NOTE this is a non-trivial contributor to request-duration, hence optimized for speed
	 * @ignore
	 */
	private static function _load_realm($realm)
	{
		$curlang = NlsOperations::get_current_language(); //CHECKME cached?
		if( !$realm ) $realm = self::$_curent_realm;

		if( isset(self::$_langdata[$curlang][$realm]) ) return;
		if( !is_array(self::$_langdata) ) self::$_langdata = [];
		if( !isset(self::$_langdata[$curlang]) ) self::$_langdata[$curlang] = [];

		// load relevant english translations first
		$files = [];
		$is_module = false;
		if( $realm == self::CMSMS_ADMIN_REALM ) {
			$files[] = CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
		}
		else {
			$dir = cms_module_path($realm,true);
			if( $dir ) {
				$is_module = true;
				$files[] = $dir.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
			}
			$files[] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$realm.DIRECTORY_SEPARATOR.'en_US.php'; //for a module-related plugin?
		}

		// now handle other lang files
		if( $curlang != 'en_US' ) {
			if( $realm == self::CMSMS_ADMIN_REALM ) {
				$files[] = CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$curlang.'.php';
			}
			elseif( $is_module ) {
				$files[] = $dir.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$curlang.'.php';
			}
			else {
				$files[] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$realm.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$curlang.'.php';
			}
		}

		// now load the custom stuff
		if( $realm == self::CMSMS_ADMIN_REALM ) {
			$files[] = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'admin_custom'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$curlang.'.php';
		}
		elseif( $is_module ) {
			$files[] = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'module_custom'.DIRECTORY_SEPARATOR.$realm.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
			$files[] = $dir.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
			if( $curlang != 'en_US' ) {
				$files[] = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'module_custom'.DIRECTORY_SEPARATOR.$realm.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$curlang.'.php';
				$files[] = $dir.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$curlang.'.php';
			}
		}

		foreach( $files as $fn ) {
			if( !is_file($fn) ) continue;

			$lang = [];
			include($fn);
			if( !isset(self::$_langdata[$curlang][$realm]) ) {
				self::$_langdata[$curlang][$realm] = [];
			}
			self::$_langdata[$curlang][$realm] = array_merge(self::$_langdata[$curlang][$realm],$lang);
			unset($lang);
		}
	}

	/**
	 * @ignore
	 */
	private static function _convert_encoding($str)
	{
		return $str;
	}

	/**
	 * Given a realm name and a key, test if the language string exists in the realm.
	 * @see also LangOperations::key_exists()
	 *
	 * @since 2.2
	 * @param string $realm The realm name (required)
	 * @param string $key The language key (required)
	 * @return bool
	 */
	public static function lang_key_exists(...$args)
	{
		if( count($args) == 1 && is_array($args[0]) ) $args = $args[0];
		if( count($args) < 2 ) return;

		$realm  = $args[0];
		$key    = $args[1];
		if( !$realm || !$key ) return;

		global $CMS_ADMIN_PAGE;
		global $CMS_STYLESHEET;
		global $CMS_INSTALL_PAGE;
		if (self::CMSMS_ADMIN_REALM == $realm && !isset($CMS_ADMIN_PAGE) &&
			!isset($CMS_STYLESHEET) && !isset($CMS_INSTALL_PAGE) &&
			!self::$_allow_nonadmin_lang ) {
			trigger_error('Attempt to load admin realm from non admin action');
			return '';
		}

		$curlang = NlsOperations::get_current_language();
		self::_load_realm($realm);
		if( isset(self::$_langdata[$curlang][$realm][$key]) ) return TRUE;
		return FALSE;
	}

	/**
	 * Given a realm name, a key, and optional parameters return a translated string
	 * This function accepts variable arguments.  If no key/realm combination can be found
	 * then an -- Add-Me string will be returned indicating that this key needs translating.
	 * This function uses the currently set language, and will load the translations from disk
	 * if necessary.
	 *
	 * @param string The realm name (required)
	 * @param string The language string key (required)
	 * @param mixed  Further arguments to this function are passed to vsprintf
	 * @return mixed string | null
	 */
	public static function lang_from_realm(...$args)
	{
		global $CMS_ADMIN_PAGE, $CMS_STYLESHEET, $CMS_INSTALL_PAGE;

		if( count($args) == 1 && is_array($args[0]) ) $args = $args[0];
		if( count($args) < 2 ) return;

		$realm  = $args[0];
		$key    = $args[1];
		if( !$realm || !$key ) return;

		if( self::CMSMS_ADMIN_REALM == $realm &&
			empty($CMS_ADMIN_PAGE) &&
			!isset($CMS_STYLESHEET) &&
			empty($CMS_INSTALL_PAGE) &&
			!self::$_allow_nonadmin_lang ) {
			trigger_error('Attempt to load admin realm from non admin action');
			return '';
		}

		self::_load_realm($realm);
		$curlang = NlsOperations::get_current_language();
		if( !isset(self::$_langdata[$curlang][$realm][$key]) ) {
			// put mention into the admin log
			global $CMS_LOGIN_PAGE;
			if( !isset($CMS_LOGIN_PAGE) ) debug_to_log('Languagestring: "' . $key . '"', 'Is missing in the languagefile: '.  $realm);
			return "-- Missing Language String: $key --";
		}

		if( count($args) > 2 ) {
			$params = array_slice($args,2);
		    if( count($params) == 1 && is_array($params[0]) ) {
				$params = $params[0];
			}
		}
		else {
			$params = [];
		}
		if( $params ) {
			$result = vsprintf(self::$_langdata[$curlang][$realm][$key], $params);
		}
		else {
			$result = self::$_langdata[$curlang][$realm][$key];
		}

		// conversion?
		return self::_convert_encoding($result);
	}

	/**
	 * A simple wrapper around the lang_from_realm method that assumes the self::CMSMS_ADMIN_REALM realm.
	 * Note, under normal circumstances this will generate an error if called from a frontend action.
	 * This function accepts variable arguments.
	 * @see LangOperations::lang_from_realm()
	 *
	 * @param string Key (required) the language string key
	 * @param mixed  Optional further arguments.
	 * @return string
	 */
	public static function lang(...$args)
	{
		if( count($args) == 1 && is_array($args[0]) ) $args = $args[0];

		array_unshift($args,self::$_current_realm);
		return self::lang_from_realm($args);
	}


	/**
	 * Allow non-admin requests to call lang functions.
	 * Normally, an error would be generated if calling core lang functions from
	 * a frontend action. This method will disable or enable that check.
	 *
	 * @internal
	 * @param bool flag
	 */
	public static function allow_nonadmin_lang($flag = TRUE)
	{
		self::$_allow_nonadmin_lang = $flag;
	}


	/**
	 * Test to see if a language key exists in the current lang file.
	 * This function uses the current language.
	 * @see also LangOperations::lang_key_exists()
	 *
	 * @param string $key The language key
	 * @param string $realm The language realm
	 * @return bool
	 */
	public static function key_exists($key,$realm = null)
	{
		if( $realm == null ) $realm = self::$_current_realm;
		self::_load_realm($realm);
		$curlang = NlsOperations::get_current_language();
		if( isset(self::$_langdata[$curlang][$realm][$key]) ) return TRUE;
		return FALSE;
	}

	/**
	 * Set the realm for further lang calls.
	 *
	 * @since 2.0
	 * @author Robert Campbell
	 * @param string $realm The realm name.  If no name specified, self::CMSMS_ADMIN_REALM is assumed'
	 * @return string the old realm name.
	 */
	public static function set_realm($realm = self::CMSMS_ADMIN_REALM)
	{
		$old = self::$_current_realm;
		if( $realm == '' ) $realm = self::CMSMS_ADMIN_REALM;
		self::$_current_realm = $realm;
		return $old;
	}
} // class
