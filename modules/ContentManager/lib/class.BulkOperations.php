<?php
/*
Class for operations dealing with bulk content methods
Copyright (C) 2008-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

namespace ContentManager;

use function startswith;

/**
 * Singleton class of static methods for dealing with bulk content operations.
 *
 * @package CMS
 * @since 2.0
 * @since 1.7 as global-namespace core class
 * @license GPL
 **/
final class BulkOperations
{
    // static properties here >> SingleItem property|ies ?
	private static $_list = [];

	private function __construct() {}
	private function __clone() {}

	/**
	 * Register a function to show in the bulk content operations list used in
	 *  ContentManager actions ajax_get_content and admin_pages_tab.
	 *
	 * @param string $label Label to show to users
	 * @param string $name Name of the action to call
	 * @param string $module Optional name of module, or 'core'. Default 'core'.
	 * @return void
	 */
	public static function register_function(string $label, string $name, string $module='core')
	{
      if( !$name || !$label ) return;

      $name = $module.'::'.$name;
	  self::$_list[$name] = $label;
    }

	/**
	 * Get a list of the registered bulk operations.
	 *
	 * @param bool $separate_modules Optional flag whether to separate the actions
	 *  from various modules with a horizontal line. Default true.
	 * @return array The list of operations
	 */
	public static function get_operation_list(bool $separate_modules = true) : array
    {
		$tmpc = [];
		$tmpm = [];
		foreach( self::$_list as $name => $label ) {
			if( startswith($name,'core::') ) {
				$tmpc[$name] = $label;
			}
			else {
				$tmpm[$name] = $label;
			}
		}

		if( $separate_modules && count($tmpm) ) {
			$tmpc[-1] = '----------';
		}
		$tmpc = array_merge($tmpc,$tmpm);
		return $tmpc;
    }
} // class
