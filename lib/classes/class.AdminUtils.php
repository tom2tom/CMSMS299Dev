<?php
#A class of convenience functions for admin console requests
#Copyright (C) 2010-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

use cms_http_request;
use cms_siteprefs;
use cms_utils;
use CmsApp;
use CMSMS\internal\Smarty;
use const CMS_DEFAULT_VERSIONCHECK_URL;
use const CMS_ROOT_PATH;
use const CMS_ROOT_URL;
use const CMS_SECURE_PARAM_NAME;
use const CMS_USER_KEY;
use const CMS_VERSION;
use function cms_join_path;
use function cms_module_places;
use function endswith;
use function startswith;

//this is also used during content installation i.e. STATE_INSTALL_PAGE, or nothing
//if( !CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE) )
//    throw new CmsLogicException('Attempt to use CMSMS\AdminUtils class from an invalid request');

/**
 * A Simple static class providing various convenience utilities for admin requests.
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 * @copyright Copyright (c) 2012, Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since 2.0
 */
final class AdminUtils
{
	/**
	 * A regular expression to use when testing if an item has a valid name.
	 */
	const ITEMNAME_REGEX = '<^[a-zA-Z0-9_\x7f-\xff][a-zA-Z0-9_\ \/\+\-\,\.\x7f-\xff]*$>';

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * Test if a string is suitable for use as a name of an item in CMSMS.
	 * For use by various modules and the core.
	 * The name must begin with an alphanumeric character (but some extended characters are allowed).  And must be followed by the same alphanumeric characters
	 * note the name is not necessarily guaranteed to be usable in smarty without backticks.
	 *
	 * @param string $str The string to test
	 * @return bool|string FALSE on error or the validated string.
	 */
	public static function is_valid_itemname($str)
	{
		if( !is_string($str) ) return FALSE;
		$t_str = trim($str);
		if( !$t_str ) return FALSE;
		if( !preg_match(self::ITEMNAME_REGEX,$t_str) ) return FALSE;
		return $str;
	}

	/**
	 * Convert an admin request URL to a generic form that is suitable for saving to a database.
	 * This is useful for things like bookmarks and homepages.
	 *
	 * @param string $in_url The input URL that has the session key in it.
	 * @return string A URL that is converted to a generic form.
	 */
	public static function get_generic_url($in_url)
	{
		if( !defined('CMS_USER_KEY') ) throw new LogicException('This method can only be called for admin requests');
		if( !isset($_SESSION[CMS_USER_KEY]) || !$_SESSION[CMS_USER_KEY] ) throw new LogicException('This method can only be called for admin requests');

		$len = strlen($_SESSION[CMS_USER_KEY]);
		$in_p = '+'.CMS_SECURE_PARAM_NAME.'\=[A-Za-z0-9]{'.$len.'}&?(amp;)?+';
//		$out_p = '_CMSKEY_='.str_repeat('X',$len); totally unused during login !!
//		$out = preg_replace($in_p,$out_p,$in_url);
		$out = preg_replace($in_p,'',$in_url);
		if( endswith($out,'&amp;') ) $out = substr($out, 0, -5);
		if( startswith($out,CMS_ROOT_URL) ) $out = str_replace(CMS_ROOT_URL,'',$out);
		return $out;
	}

	/**
	 * Convert a generic URL into something that is suitable for this user's session.
	 *
	 * @param string $in_url The generic url.  usually retrieved from a preference or from the database
	 * @return string A URL that has a session key in it.
	 */
	public static function get_session_url($in_url)
	{
		if( !defined('CMS_USER_KEY') ) throw new LogicException('This method can only be called for admin requests');
		IF( !isset($_SESSION[CMS_USER_KEY]) || !$_SESSION[CMS_USER_KEY] ) throw new LogicException('This method can only be called for admin requests');

		$len = strlen($_SESSION[CMS_USER_KEY]);
		$in_p = '+_CMSKEY_=[X]{'.$len.'}+';
		$out_p = CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
		return preg_replace($in_p,$out_p,$in_url);
	}

	/**
	 * Get the latest available CMSMS version.
	 * This method does a remote request to the version check URL at most once per day.
	 *
	 * @return string
	 */
	public static function fetch_latest_cmsms_ver()
	{
		$last_fetch = (int) cms_siteprefs::get('last_remotever_check');
		$remote_ver = cms_siteprefs::get('last_remotever');
		if( $last_fetch < (time() - 24 * 3600) ) {
			$req = new cms_http_request();
			$req->setTimeout(3);
			$req->execute(CMS_DEFAULT_VERSIONCHECK_URL);
			if( $req->getStatus() == 200 ) {
				$remote_ver = trim($req->getResult());
				if( strpos($remote_ver,':') !== FALSE ) {
					list($tmp,$remote_ver) = explode(':',$remote_ver,2);
					$remote_ver = trim($remote_ver);
				}
				cms_siteprefs::set('last_remotever',$remote_ver);
				cms_siteprefs::set('last_remotever_check',time());
			}
		}
		return $remote_ver;
	}

	/**
	 * Test if the current site is in need of upgrading (a new version of CMSMS is available)
	 *
	 * @return bool
	 */
	public static function site_needs_updating()
	{
		$remote_ver = self::fetch_latest_cmsms_ver();
		if( version_compare(CMS_VERSION,$remote_ver) < 0 ) {
			return TRUE;
		}
		else {
			return FALSE;
		}
	}

	/**
	 * Return the url corresponding to a provided site-path
	 *
	 * @since 2.3
	 * @param string $in The input path, absolute or relative
	 * @param string $relative_to Optional absolute path which (relative) $in is relative to
	 * @return string
	 */
	public static function path_to_url(string $in, string $relative_to = '') : string
	{
		$in = trim($in);
		if( $relative_to ) {
			$in = realpath(cms_join_path($relative_to, $in));
			return str_replace([CMS_ROOT_PATH, DIRECTORY_SEPARATOR], [CMS_ROOT_URL, '/'], $in);
		} elseif( preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $in) ) {
			// $in is absolute
			$in = realpath($in);
			return str_replace([CMS_ROOT_PATH, DIRECTORY_SEPARATOR], [CMS_ROOT_URL, '/'], $in);
		} else {
			return strtr($in, DIRECTORY_SEPARATOR, '/');
		}
	}

	/**
	 * Get a tag representing a module icon
	 *
	 * @since 2.3
	 * @param string $module Name of the module
	 * @param array $attrs Optional assoc array of attributes for the created img tag
	 * @return string
	 */
	public static function get_module_icon(string $module, array $attrs = []) : string
	{
		$dirs = cms_module_places($module);
		if ($dirs) {
			$appends = [
				['images','icon.svg'],
				['icons','icon.svg'],
				['images','icon.png'],
				['icons','icon.png'],
				['images','icon.gif'],
				['icons','icon.gif'],
			];
			foreach ($dirs as $base) {
				foreach ($appends as $one) {
					$path = cms_join_path($base, ...$one);
					if (is_file($path)) {
						$path = self::path_to_url($path);
						if (endswith($path, '.svg')) {
							// see https://css-tricks.com/using-svg
							$alt = str_replace('svg','png',$path);
							$out = '<img src="'.$path.'" onerror="this.onerror=null;this.src=\''.$alt.'\';"';
						} else {
							$out = '<img src="'.$path.'"';
						}
						$extras = array_merge(['alt'=>$module, 'title'=>$module], $attrs);
						foreach( $extras as $key => $value ) {
							if ($value !== '' || $key == 'title') {
								$out .= " $key=\"$value\"";
							}
						}
						$out .= ' />';
						return $out;
					}
				}
			}
		}
		return '';
	}

	/**
	 * Get a tag representing a themed icon or module icon
	 *
	 * @param string $icon the basename of the desired icon file, may include theme-dir-relative path,
	 *  may omit file type/suffix, ignored if smarty variable $actionmodule is currently set
	 * @param array $attrs Since 2.3 Optional assoc array of attributes for the created img tag
	 * @return string
	 */
	public static function get_icon($icon, $attrs = [])
	{
		$smarty = Smarty::get_instance();
		$module = $smarty->getTemplateVars('actionmodule');

		if ($module) {
			return self::get_module_icon($module, attrs);
		} else {
			$theme = cms_utils::get_theme_object();
			if( is_object($theme) ) {
				if( basename($icon) == $icon ) $icon = 'icons'.DIRECTORY_SEPARATOR.'system'.DIRECTORY_SEPARATOR.$icon;
				return $theme->DisplayImage($icon,'','','',null,$attrs);
			}
		}
	}

	/**
	 * Get a help tag for displaying inline popup help.
	 *
	 * This method accepts variable arguments. Recognized keys are
	 " 'key1'/'realm','key'/'key2','title'/'titlekey'
	 * If neither 'key1'/'realm' is provided, the fallback will be action-module
	 * name, or else 'help'
	 * If neither 'title'/'titlekey' is provided, the fallback will be 'key'/'key2'
	 *
	 * @param strings array
	 * @return string HTML content of the help tag, or null
	 */
	public static function get_help_tag(...$args)
	{
		if( !CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE) ) return;

		$theme = cms_utils::get_theme_object();
		if( !is_object($theme) ) return;

		$icon = self::get_icon('info.png', ['class'=>'cms_helpicon']);
		if( !$icon ) return;

		$params = [];
		if( count($args) >= 2 && is_string($args[0]) && is_string($args[1]) ) {
			$params['key1'] = $args[0];
			$params['key2'] = $args[1];
			if( isset($args[2]) ) $params['title'] = $args[2];
		}
		else if( count($args) == 1 && is_string($args[0]) ) {
			$params['key2'] = $args[0];
		}
		else {
			$params = $args[0];
		}

		$key1 = '';
		$key2 = '';
		$title = '';
		foreach( $params as $key => $value ) {
			switch( $key ) {
			case 'key1':
			case 'realm':
				$key1 = trim($value);
				break;
			case 'key':
			case 'key2':
				$key2 = trim($value);
				break;
			case 'title':
			case 'titlekey':
				$title = trim($value); //TODO ensure $value including e.g. &quot; works
			}
		}

		if( !$key1 ) {
			$smarty = Smarty::get_instance();
			$module = $smarty->getTemplateVars('actionmodule');
			if( $module ) {
				$key1 = $module;
			}
			else {
				$key1 = 'help'; //default realm for lang
			}
		}

		if( !$key1 ) return;

		if( $key2 !== '' ) { $key1 .= '__'.$key2; }
		if( $title === '' ) { $title = ($key2) ? $key2 : 'for this'; } //TODO lang

		return '<span class="cms_help" data-cmshelp-key="'.$key1.'" data-cmshelp-title="'.$title.'">'.$icon.'</span>';
	}
} // class
