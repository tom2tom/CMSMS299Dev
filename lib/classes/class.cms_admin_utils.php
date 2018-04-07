<?php
# A class of convenience functions for admin console requests
# Copyright (C) 2010-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
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

/**
 * Static classes providing convenience functions for admin console requests.
 * @package CMS
 * @license GPL
 */

if( !CmsApp::get_instance()->test_state(CmsApp::STATE_ADMIN_PAGE) )
    throw new CmsLogicException('Attempt to use cms_admin_utils class from an invalid request');

/**
 * A Simple static class providing various convenience utilities for admin requests.
 *
 * @package CMS
 * @license GPL
 * @author  Robert Campbell
 * @copyright Copyright (c) 2012, Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since 2.0
 */
final class cms_admin_utils
{
	/**
	 * @ignore
	 */
	private function __construct() {}

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
	 * Module-directories lister. Checks for directories existence, including $modname if provided.
	 *
	 * @since 2.3
	 * @param string $modname Optional name of a module
	 * @return array of absolute filepaths, no trailing separators, or maybe empty.
	 *  Core-modules-path first.
	 */
	public static function module_places(string $modname = '') : array
	{
		$dirlist = [];
		$path = cms_join_path(CMS_ROOT_PATH,'lib','modules');
		if ($modname) {
			$path .= DIRECTORY_SEPARATOR . $modname;
		}
		if (is_dir($path)) {
			$dirlist[] = $path;
		}
		$path = cms_join_path(CMS_ASSETS_PATH,'modules');
		if ($modname) {
			$path .= DIRECTORY_SEPARATOR . $modname;
		}
		if (is_dir($path)) {
			$dirlist[] = $path;
		}
		// pre-2.3, deprecated
		$path = cms_join_path(CMS_ROOT_PATH,'modules');
		if ($modname) {
			$path .= DIRECTORY_SEPARATOR . $modname;
		}
		return $dirlist;
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
		$dirs = self::module_places($module);
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
		$smarty = \CMSMS\internal\Smarty::get_instance();
		$module = $smarty->get_template_vars('actionmodule');

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
	 * Get a help tag for displaying inlne, popup help.
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
			$smarty = \CMSMS\internal\Smarty::get_instance();
			$module = $smarty->get_template_vars('actionmodule');
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
} // end of class
