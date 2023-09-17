<?php
/*
Translation functions
Copyright (C) 2013-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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
namespace CMSMS;

use CMSMS\AppState;
use CMSMS\NlsOperations;
use const CMS_ADMIN_PATH;
use const CMS_ASSETS_PATH;
use const CMS_ROOT_PATH;
use function cms_module_path;
//use function debug_output;
use function debug_to_log;

/**
 * A singleton class to provide simple, generic mechanism for retrieving
 * translated (locale specific) strings, with fallback to english defaults.
 * The (quite-hefty) admin domain is loaded when needed during admin requests
 * and not so during frontend requests. In both cases subject to the then-
 * current value of class property $_allow_nonadmin_lang
 *
 * @package CMS
 * @license GPL
 * @since 1.11
 */
final class LangOperations
{
	/**
	 * The admin domain name
	 */
	const CMSMS_ADMIN_REALM = 'admin';

	// static properties here >> Lone property|ies ?
	/**
	 * In-memory cache of loaded translations, a 2-D array keyed by [locale][domain]
	 * 'locale' is a recorded or inferred frontend|backend locale-identifier e.g. 'fr_FR'.
	 * It is very unlikely there would be > 1 of those within the current request.
	 * 'domain' is effectively a namespace for the translated strings.
	 * @ignore
	 */
	private static $_langdata;

	/**
	 * Unused
	 * @ignore
	 */
	private static $_do_conversions;

	/**
	 * Domain-override flag. Default false.
	 * @ignore
	 */
	private static $_allow_nonadmin_lang = false; //false value is irrelevant during admin requests

	/**
	 * @ignore
	 */
	private static $_default_domain = self::CMSMS_ADMIN_REALM;

	/**
	 * @ignore
	 */
	private function __construct() {}
	private function __clone(): void {}

	/**
	 * NOTE this is a non-trivial contributor to request-duration, hence optimized for speed
	 * Domain-relevance check(s) are not done here, any such must be performed upstream.
	 * @ignore
	 * @param mixed $domain string|null domain name
	 * @param string $locale locale identifier
	 */
	private static function _load_realm(?string $domain, string $locale): void
	{
		if( !$domain ) $domain = self::$_curent_realm;
		if( isset(self::$_langdata[$locale][$domain]) ) return;
//		debug_output($domain, 'LangOperations::_load_realm START');
		if( !is_array(self::$_langdata) ) self::$_langdata = [];
		if( !isset(self::$_langdata[$locale]) ) self::$_langdata[$locale] = [];
		self::$_langdata[$locale][$domain] = [];

		// akin to class autoloading, we figure out what to load from where
		// we load relevant english translations (default, often more-populated) before another specifed lang
		$files = [];
		$is_module = FALSE;
		$space_dir = '';
		if( $domain == self::CMSMS_ADMIN_REALM ) {
			$is_admin = TRUE;
			$files[] = CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
			// any custom replacements
			$files[] = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'admin_custom'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
		}
		else {
			$is_admin = FALSE;
			$dir = cms_module_path($domain, TRUE);
			if( $dir ) {
				$is_module = TRUE;
				$files[] = $dir.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
				// then any custom replacements
				$files[] = $dir.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
				$files[] = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'module_custom'.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'en_US.php';
			}
			elseif( strpos($domain, '\\') !== FALSE ) {
				$o = ($domain[0] != '\\') ? 0 : 1;
				$p = strpos($domain, '\\', $o + 1);
				if( $p !== FALSE ) {
					$space = substr($domain, $o, $p - $o);
					$path = trim(substr($domain, $p), ' \\');
				}
				else {
					$space = substr($domain, $o);
					$path = '';
				}
				if( $path ) {
					$path = strtr($path, '\\', DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
					$lp = $path.'lang';
				}
				else {
					$lp = 'lang';
				}
				switch( $space ) {
					case 'CMSMS':
						$space_dir = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.$lp;
						break;
					case 'CMSAsset':
						$space_dir = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.$lp;
						break;
					case 'CMSResource':
						$space_dir = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'resources'.DIRECTORY_SEPARATOR.$path.'lib'.DIRECTORY_SEPARATOR.'lang';
						break;
					default:
						$dir = cms_module_path($space, TRUE);
						if( $dir ) {
							$space_dir = $dir.DIRECTORY_SEPARATOR.$lp;
							//CHECKME also support custom replacements ??
						}
						break;
				}
				if( $space_dir ) {
					$files[] = $space_dir.DIRECTORY_SEPARATOR.'en_US.php';
				}
			}
			else {
				//here be 'functional' domains e.g. 'tags', typically for admin use
				$files[] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.'en_US.php';
			}
		}

		// now the other locale, if any
		if( $locale != 'en_US' ) {
			if( $is_admin ) {
				$files[] = CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$locale.'.php';
				// then any custom replacements
				$files[] = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'admin_custom'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$locale.'.php';
			}
			elseif( $is_module ) {
				$files[] = $dir.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$locale.'.php';
				$files[] = $dir.DIRECTORY_SEPARATOR.'custom'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$locale.'.php';
				$files[] = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'module_custom'.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$locale.'.php';
			}
			elseif( $space_dir ) {
				$files[] = $space_dir.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$locale.'.php';
			}
			else {
				$files[] = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'lang'.DIRECTORY_SEPARATOR.$domain.DIRECTORY_SEPARATOR.'ext'.DIRECTORY_SEPARATOR.$locale.'.php';
			}
		}

		$lang =& self::$_langdata[$locale][$domain]; //inclusions populate $lang[]
		foreach( $files as $fn ) {
			if( is_file($fn) ) {
				include_once $fn; // set new/different strings
			}
		}
		unset($lang);
//		debug_output($domain, 'LangOperations::_load_realm END');
	}

	//TODO consider a method to get only specified 'target' key(s) (if any) for the specified domain
	// e.g. for supplementary admin strings without the heft of the whole admin domain
	// array_intersect_key(...)

	/* *
	 * UNUSED, does nothing
	 * @ignore
	 */
/*	private static function _convert_encoding($str)
	{
		return $str;
	}
*/
	/**
	 * Determine whether the sepecified key exists in the specified domain.
	 * @see also LangOperations::key_exists()
	 *
	 * @since 2.2
	 * @param varargs $args, of which
	 *  1st = domain name (required non-falsy string) or array comprising all args
	 *  2nd = translated-string key (required non-falsy string unless 1st is merged array)
	 *  further argument(s) if any are unused here
	 * @since 3.0 the domain may be namespaced e.g. CMSAsset\somespace or Modname\somespace
	 * @return bool
	 */
	public static function lang_key_exists(...$args): bool
	{
		if( count($args) == 1 && is_array($args[0]) ) $args = $args[0]; //just in case
		if( count($args) < 2 ) return FALSE;

		$domain  = $args[0];
		$key = $args[1];
		if( !$domain || !$key ) return FALSE;

		if( $domain == self::CMSMS_ADMIN_REALM &&
			!(self::$_allow_nonadmin_lang ||
			  AppState::test_any(AppState::ADMIN_PAGE | AppState::STYLESHEET | AppState::INSTALL) //? LOGIN_PAGE
			 ) ) {
			trigger_error("Attempt to check for $key in disabled admin domain");
			return FALSE;
		}

		$locale = NlsOperations::get_current_language();
		self::_load_realm($domain, $locale);
		return isset(self::$_langdata[$locale][$domain][$key]);
	}

	/**
	 * Return the translated string for a domain name, key, and optional parameters, if possible.
	 * If no suitable string is found, then a string like -- Missing key -- will be
	 * returned, indicating that the specified key needs translating.
	 * This function uses the currently set locale, and will load the
	 * translations if necessary.
	 * @since 3.0 Replaces deprecated lang_from_realm()
	 *
	 * @param varargs $args, of which
	 *  1st = domain name (required non-falsy string) or array comprising all args
	 *  2nd = translated-string key (required non-falsy string unless 1st is merged array)
	 *  further argument(s) (optional string|number, generally, or array of same)
	 * @since 3.0 the domain may be namespaced e.g. CMSAsset\somespace or Modname\somespace
	 * @return string, possibly empty
	 */
	public static function domain_string(...$args): string
	{
		if( count($args) == 1 && is_array($args[0]) ) $args = $args[0];
		if( count($args) < 2 ) return '';

		$domain  = $args[0];
		$key = $args[1];
		if( !$domain || !$key ) return '';

		if( $domain == self::CMSMS_ADMIN_REALM &&
			!(self::$_allow_nonadmin_lang ||
			  AppState::test_any(AppState::ADMIN_PAGE | AppState::STYLESHEET | AppState::INSTALL) //? LOGIN_PAGE
			 ) ) {
			trigger_error("Attempt to get translation for $key from disabled admin domain");
			return '';
		}

		$locale = NlsOperations::get_current_language();
		self::_load_realm($domain, $locale);
		if( !isset(self::$_langdata[$locale][$domain][$key]) ) {
			// put mention into the admin log
			if( !AppState::test(AppState::LOGIN_PAGE) ) debug_to_log('Languagestring: "' . $key . '"', 'Is missing from the translations file: ' . $domain);
			return "-- Missing Language String: $key --";
		}

		if( count($args) > 2 ) {
			$params = array_slice($args, 2);
			if( count($params) == 1 && is_array($params[0]) ) {
				$params = $params[0];
			}
		}
		else {
			$params = [];
		}
		if( $params ) {
			$result = vsprintf(self::$_langdata[$locale][$domain][$key], $params);
		}
		else {
			$result = self::$_langdata[$locale][$domain][$key];
		}

		// conversion? e.g. to UTF-8
//		return self::_convert_encoding($result); //TODO useless
		return $result;
	}

	/**
	 * Return translated string if possible
	 * @deprecated since 3.0 instead use LangOperations::domain_string()
	 * @param varargs $args
	 */
	public static function lang_from_realm(...$args): string
	{
		return self::domain_string(...$args);
	}

	/**
	 * A wrapper around the domain_string method that applies the
	 * self::CMSMS_ADMIN_REALM domain.
	 * Note, under normal circumstances this will generate an error if
	 * called during a frontend request.
	 * @since 3.0
	 * @see LangOperations::domain_string()
	 *
	 * @param varargs $args, of which
	 *  1st = translated-string key (required non-falsy string) or array comprising all args
	 *  further argument(s) (optional string|number, generally, or array of same)
	 * @return string, possibly empty
	 */
	public static function admin_string(...$args): string
	{
		if( count($args) == 1 && is_array($args[0]) ) { $args = $args[0]; }
		return self::domain_string(self::CMSMS_ADMIN_REALM, ...$args);
	}

	/**
	 * A wrapper around the domain_string method that applies the
	 * self::$_default_domain (typically self::CMSMS_ADMIN_REALM) domain.
	 * Note, under normal circumstances this will generate an error if
	 * called during a frontend request.
	 * @since 3.0 Replaces deprecated lang()
	 * @see LangOperations::domain_string()
	 *
	 * @param varargs $args, of which
	 *  1st = translated-string key (required non-falsy string) or array comprising all args
	 *  further argument(s) (optional string|number, generally, or array of same)
	 * @return string, possibly empty
	 */
	public static function default_string(...$args): string
	{
		if( count($args) == 1 && is_array($args[0]) ) {
			$args = $args[0];
		}
		return self::domain_string(self::$_default_domain, ...$args);
	}

	/**
	 * Return translated admin-domain string if possible
	 * @deprecated since 3.0 instead use LangOperations::default_string()
	 * @param varargs $args
	 */
	public static function lang(...$args): string
	{
		return self::default_string(...$args);
	}

	/**
	 * [Dis]allow use of admin-domain strings, during the balance of the
	 * current request or until this method is called again with a
	 * different parameter.
	 * Normally, admin-domain strings are available only during a backend
	 * request. Ditto for the converse. This method may be used to
	 * disable (and re-enable) the default behavior.
	 *
	 * @internal
	 * @param bool Optional flag Default true
	 */
	public static function allow_nonadmin_lang($flag = TRUE)
	{
		self::$_allow_nonadmin_lang = $flag;
//		if( !$flag ) {
//TODO clear self::$_langdata[*]['admin] OR ? don't check in there if present
//		}
	}

	/**
	 * Report whether a translated-string key exists for the current locale
	 * and in the specified domain.
	 * @see also LangOperations::lang_key_exists()
	 *
	 * @param mixed $key string|null The wanted key
	 * @param string $domain Optional lang domain. Default '', hence the
	 *  currently-recorded default domain.
	 * @since 3.0 the domain may be namespaced e.g. CMSAsset\somespace or Modname\somespace
	 * @return bool
	 */
	public static function key_exists($key, string $domain = ''): bool
	{
		if( $domain === '') $domain = self::$_default_domain;
		$locale = NlsOperations::get_current_language();
		self::_load_realm($domain, $locale);
		return isset(self::$_langdata[$locale][$domain][''.$key]);
	}

	/**
	 * Set the default domain for subsequent 'un-realmed' default_string() calls.
	 *
	 * @since 2.0
	 * @param string $domain Optional domain name. Default self::CMSMS_ADMIN_REALM.
	 * @since 3.0 the domain may be namespaced e.g. CMSAsset\somespace or Modname\somespace
	 * @return string the previous/replaced domain-name
	 */
	public static function set_realm(string $domain = self::CMSMS_ADMIN_REALM): string
	{
		$old = self::$_default_domain;
		if( $domain === '' ) $domain = self::CMSMS_ADMIN_REALM;
		self::$_default_domain = $domain;
		return $old;
	}
} // class
