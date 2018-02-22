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
	 * Get the complete URL to an admin icon
	 *
	 * @param string $icon the basename of the desired icon
	 * @return string
	 */
	public static function get_icon($icon)
	{
		$theme = cms_utils::get_theme_object();
		if( !is_object($theme) ) return;

		$smarty = \CMSMS\internal\Smarty::get_instance();
		$module = $smarty->get_template_vars('actionmodule');

		$dirs = [];
		if( $module ) {
			$obj = cms_utils::get_module($module);
			if( is_object($obj) ) {
				$img = basename($icon);
				$path = $obj->GetModulePath();
				$dirs[] = [cms_join_path($path,'icons',"{$img}"), $path."/icons/{$img}"];
				$dirs[] = [cms_join_path($path,'images',"{$img}"), $path."/images/{$img}"];
			}
		}
		if( basename($icon) == $icon ) $icon = "icons/system/{$icon}";
		$config = \cms_config::get_instance();
		$dirs[] = array(cms_join_path($config['root_path'],$config['admin_dir'],"themes/{$theme->themeName}/images/{$icon}"),
						$config['admin_url']."/themes/{$theme->themeName}/images/{$icon}");

		$fnd = null;
		foreach( $dirs as $one ) {
			if( file_exists($one[0]) ) {
				$fnd = $one[1];
				break;
			}
		}
		return $fnd;
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

		$icon = self::get_icon('info.gif');
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

		return '<span class="cms_help" data-cmshelp-key="'.$key1.'" data-cmshelp-title="'.$title.'"><img class="cms_helpicon" src="'.$icon.'" alt="'.$icon.'" /></span>';
	}
} // end of class
