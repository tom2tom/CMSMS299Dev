<?php
/*
Class of methods for dealing with language/encoding/locale
Copyright (C) 2015-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace CMSMS;

use CMSMS\AppParams;
use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\LanguageDetector;
use CMSMS\Nls;
use CMSMS\UserParams;
use const CMS_ROOT_PATH;
use function cleanString;
use function cms_join_path;
use function get_userid;

/**
 * A singleton class to provide simple, generic mechanism for dealing with
 * languages encodings, and locales.
 * This class does not handle translation strings.
 *
 * @author Robert Campbell
 * @since 1.11
 * @package CMS
 * @license GPL
 */
final class NlsOperations
{
	// static properties here >> StaticProperties class ?
	/**
	 * @ignore
	 */
	private static $_nls;

	/**
	 * @ignore
	 */
	private static $_cur_lang; // each request is either for the admin or for the frontend.

	/**
	 * @ignore
	 */
	private static $_encoding;

	/**
	 * @ignore
	 */
	private static $_locale;

	/**
	 * @ignore
	 */
	private static $_fe_language_detector;

	/**
	 * @ignore
	 */
	private static $_stored_dflt_language;

	/**
	 * @ignore
	 */
	private function __construct() {}
	private function __clone() {}

	/**
	 * @ignore
	 */
	private static function _load_nls()
	{
		if( !is_array(self::$_nls) ) {
			self::$_nls = [];
			$config = AppSingle::Config();
			$nlsdir = cms_join_path(CMS_ROOT_PATH,'lib','nls');
			$langdir = cms_join_path(CMS_ROOT_PATH,$config['admin_dir'],'lang');
			$files = glob($nlsdir.DIRECTORY_SEPARATOR.'*nls.php');
			if( $files ) {
				for( $i = 0, $n = count($files); $i < $n; $i++ ) {
					if( !is_file($files[$i]) ) continue;
					$fn = basename($files[$i]);
					$tlang = substr($fn,0,strpos($fn,'.'));
					if( $tlang != 'en_US' && !is_file(cms_join_path($langdir,'ext',$tlang.'.php')) ) continue;

					unset($nls);
					include($files[$i]);
					if( isset($nls) ) {
						$obj = Nls::from_array($nls);
						unset($CmsNlsnls);
						self::$_nls[$obj->key()] = $obj;
					}
				}
			}
		}
	}

	/**
	 * Get an array of all languages that are known (installed).
	 * Uses the NLS files to handle this
	 *
	 * @return mixed Array of language names, or null
	 */
	public static function get_installed_languages()
	{
		self::_load_nls();
		if( is_array(self::$_nls) ) {
			return array_keys(self::$_nls);
		}
	}

	/**
	 * Get language info about a particular language.
	 *
	 * @param string $lang language name.
	 * @return mixed Nls object representing the named language, or null.
	 */
	public static function get_language_info(string $lang)
	{
		self::_load_nls();
		if( isset(self::$_nls[$lang]) ) {
			return self::$_nls[$lang];
		}
	}

	/**
	 * Get indicator whether current lang is ltr or rtl
	 *
	 * @since 2.99
	 * @return string 'ltr' or 'rtl'
	 */
	public static function get_language_direction() : string
	{
		$lang = self::get_language_info(self::get_current_language());
		if( is_object($lang) && $lang->direction() == 'rtl' ) { return 'rtl'; }
		return 'ltr';
	}

	/**
	 * Set a current language.
	 * The language specified may be an empty string, which will assume that the system
	 * should try to detect an appropriate language.  If no default can be found for
	 * some reason, en_US will be assumed.
	 *
	 * When a language is found, the system will automatically set the locale for the request.
	 *
	 * Note: CMSMS 1.11 and above will not support multiple languages per request.
	 * therefore, it should be assumed that this function can only be called once per request.
	 *
	 * @internal
	 * @see set_locale
	 * @param string The desired language.
	 * @return bool
	 */
	public static function set_language(string $lang = '') : bool
	{
		$curlang = ( self::$_cur_lang != '' ) ? self::$_cur_lang : '';

		if( $lang == '' && AppSingle::App()->is_frontend_request() && is_object(self::$_fe_language_detector) ) {
			$lang = self::$_fe_language_detector->find_language();
		}
		if( $lang != '' ) {
			$lang = self::find_nls_match($lang); // resolve input string
		}
		if( $lang == '' ) {
			$lang = self::get_default_language();
		}
		if( $curlang == $lang ) return TRUE; // nothing to do.

		self::_load_nls();
		if( isset(self::$_nls[$lang]) ) {
			// lang is ok... now we can set it.
			self::$_cur_lang = $lang;
			// and set the locale along with this language.
			self::set_locale();
			return TRUE;
		}
		return FALSE;
	}

	/**
	 * Get the current language.
	 * If not explicitly set this method will try to detect the current language.
	 * different detection mechanisms are used for admin requests vs. frontend requests.
	 * if no match could be found in any way, en_US is returned.
	 *
	 * @return string Language name.
	 */
	public static function get_current_language() : string
	{
		if( isset(self::$_cur_lang) ) {
			return self::$_cur_lang;
		}
		if( is_object(self::$_fe_language_detector) && AppSingle::App()->is_frontend_request() ) {
			return self::$_fe_language_detector->find_language();
		}
		return self::get_default_language();
	}

	/**
	 * Get a default language.
	 * This method will behave differently for admin or frontend requests.
	 *
	 * For admin requests first the preference is checked.  Secondly,
	 * an attempt is made to find a language understood by the browser that is
	 * compatible with what is available.  If no match can be found, en_US is assumed.
	 *
	 * For frontend requests if a language detector has been set into this object it will
	 * be called to attempt to find a language.  If that fails, then the frontend language preference
	 * is used.  Thirdly, if no match is found en_US is assumed.
	 *
	 * @see set_language_detector
	 * @return string
	 */
	public static function get_default_language() : string
	{
		if( self::$_stored_dflt_language ) {
			return self::$_stored_dflt_language;
		}
		self::_load_nls();

		if( AppState::test_any_state(AppState::STATE_ADMIN_PAGE | AppState::STATE_STYLESHEET | AppState::STATE_INSTALL) ) {
			$lang = self::get_admin_language();
		}
		else {
			$lang = self::get_frontend_language();
		}
		if( !$lang ) {
			$lang = 'en_US';
		}
		self::$_stored_dflt_language = $lang;
		return $lang;
	}

	/**
	 * Use detection mechanisms to find a suitable frontend language.
	 * the language returned must be available as specified by the
	 * available NLS Files.
	 *
	 * @return string language name.
	 */
	protected static function get_frontend_language() : string
	{
		$lang = trim(AppParams::get('frontendlang'));
		if( !$lang ) $lang = 'en_US';
		return $lang;
	}

	/**
	 * Use detection mechanisms to find a suitable language for an admin request.
	 * The language returned must be an available language as specified by the
	 * available NLS Files.  If no suitable language can be detected this function
	 * will return en_US
	 *
	 * @return string The language name
	 */
	protected static function get_admin_language() : string
	{
		$uid = $lang = null;
		if( !AppState::test_state(AppState::STATE_LOGIN_PAGE) ) {
			$uid = get_userid(false);
			if( $uid ) {
				$lang = UserParams::get_for_user($uid,'default_cms_language');
				if( $lang ) {
					self::_load_nls();
					if( !isset(self::$_nls[$lang]) ) {
						$lang = null;
					}
				}
			}
		}

		if( $uid && isset($_POST['default_cms_language']) ) {
			// a hack to handle the editpref case of the user changing his language
			// this is needed because the lang stuff is included before the preference may
			// actually be set.
			self::_load_nls();
			$a2 = cleanString($_POST['default_cms_language']);
			if( $a2 && isset(self::$_nls[$a2]) ) {
				$lang = $a2;
			}
		}

		if( !$lang ) {
			$lang = self::detect_browser_language();
		}

		if( !$lang ) {
			$lang = 'en_US';
		}
		return $lang;
	}

	/**
	 * Cross-reference the browser preferred language with those
	 * that are available (via NLS Files). To find the first
	 * suitable language.
	 *
	 * @return mixed string (first suitable lang identifier) | null
	 */
	public static function detect_browser_language()
	{
		$langs = self::get_browser_languages();
		if( !$langs || !is_array($langs) ) {
			return;
		}
		self::_load_nls();
		// check for exact match
		foreach( $langs as $lang => $qf ) {
			if( isset(self::$_nls[$lang]) ) {
				return $lang;
			}
		}
		// check for approximate match (in self::$_nls[] order)
		foreach( $langs as $lang => $qf ) {
			foreach( self::$_nls as $obj ) {
				if( $obj->matches($lang) ) {
					return $obj->name();
				}
			}
		}
	}

	/**
	 * Return a priority-sorted list of languages (if any) understood by the browser.
	 *
	 * @return mixed array of strings representing the languages the browser supports, or null
	 */
	public static function get_browser_languages()
	{
		if( !isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ) return;

		$in = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		preg_match_all('/([a-z]{1,8}(-[a-z]{1,8})*)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i', $in, $matches);

		if ($matches[1]) {
			// create a list, each member like "en" => 0.8
			$langs = array_combine($matches[1], $matches[4]);
			// convert '-' separator to '_' to match local format
			// set q-factor to 1 for any lang without one
			foreach( $langs as $lang => $qf ) {
				$t = strtr($lang, '-', '_');
				if( $t != $lang ) {
					unset($langs[$lang]);
					if( $qf === '' ) { $langs[$t] = 1.0; }
					else { $langs[$t] = 0 + $qf; }
				}
				elseif( $qf === '' ) {
					$langs[$lang] = 1.0;
				}
				else {
					$langs[$lang] = 0 + $qf;
				}
			}
			// sort langs by q-factor, descending
			arsort($langs, SORT_NUMERIC);
			return $langs;
		}
	}

	/**
	 * Return the currently active encoding.
	 * If an encoding has not been explicitly set, the default_encoding value from the config file will be used
	 * If that value is empty, the encoding associated with the current language will be used.
	 * If no suitable encoding can be found, UTF-8 will be assumed.
	 *
	 * @return string.
	 */
	public static function get_encoding() : string
	{
		// has it been explicity set somewhere?
		if( self::$_encoding ) {
			return self::$_encoding;
		}
		// is it specified in the config.php?
		$config = AppSingle::Config();
		if( !empty($config['default_encoding']) ) {
			return strtoupper($config['default_encoding']);
		}

		$lang = self::get_current_language();
		if( !$lang ) { return 'UTF-8'; } // no language.. weird.

		// get it from the nls cache
		return self::$_nls[$lang]->encoding();
	}

	/**
	 * Set the current encoding
	 *
	 * @param mixed $str The encoding (or comma-separated encodings), or false
	 */
	public static function set_encoding($str)
	{
		if( !$str ) {
			self::$_encoding = null;
			return;
		}
		self::$_encoding = strtoupper($str);
	}

	/**
	 * Set the locale for the current language.
	 * if language has not been set... does nothing.
	 * will use the locale from the nls information for the current locale
	 * if config entry is set... it will be used, but subsequent calls to this
	 * method will do nothing.
	 */
	protected static function set_locale()
	{
		static $_locale_set = FALSE;
		$config = AppSingle::Config();

		$locale = '';
		if( isset($config['locale']) && $config['locale'] != '' ) {
			if( $_locale_set ) return;

			$locale = $config['locale'];
		}
		else {
			if( self::$_cur_lang == '' ) return;

			self::_load_nls();
			$locale = self::$_nls[self::$_cur_lang]->locale();
		}

		if( $locale ) {
			if( !is_array($locale) ) $locale = explode(',',$locale);
			$res = setlocale(LC_ALL,$locale);
			$_locale_set = TRUE;
		}
	}

	/**
	 * Override the default language detection mechanism for frontend requests.
	 * One (and only one!) module may specify a detection-object derived from
	 * CMSMS\LanguageDetector.
	 *
	 * e.g. NlsOperations::set_language_detector(myLanguageDetector)
	 *
	 * Note: the detector must return a language for which there is an available NLS file.
	 *
	 * @param LanguageDetector $obj Object containing methods to detect a
	 *  compatible, desired language
	 */
	public static function set_language_detector(LanguageDetector $obj)
	{
		if( is_object(self::$_fe_language_detector) ) die('language detector already set');
		self::$_fe_language_detector = $obj;
	}

	/**
	 * Find a match for a specific language
	 * This method will try to find the NLS information closest to the language specified.
	 *
	 * @param string $str An approximate language specification (an alias match is done if possible).
	 * @return hash containing NLS information.
	 */
	protected static function find_nls_match($str)
	{
		self::_load_nls();
		foreach( self::$_nls as $key => $obj ) {
			if( $obj->matches($str) ) return $obj->name();
		}
	}
} // class
