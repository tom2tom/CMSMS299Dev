<?php
# Classes, functions and utilities for managing CMSMS routes
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//namespace CMSMS; future use

use CMSMS\internal\global_cachable;
use CMSMS\internal\global_cache;
use CMSMS\ModuleOperations;

if( !function_exists('__internal_cmp_routes') ) {
	/**
	 * @internal
	 * @ignore
	 */
	function __internal_cmp_routes($a,$b) {
		return strcmp($a['term'],$b['term']);
	}
}

/**
 * A class to manage all recognized routes in the system.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since  1.9
 */
final class cms_route_manager
{
	/**
	 * Flag whether the 'static' routes property has been populated
	 * @ignore
	 */
	private static $_routes_loaded = FALSE;

	/**
	 * Local cache of 'static' (i.e. recorded in database 'routes' table) routes
	 * @ignore
	 */
	private static $_routes;

	/**
	 * @ignore
	 */
	private static $_dynamic_routes;

	/**
	 * @ignore
	 */
	private function __construct() {
		$obj = new global_cachable('routes', function()
			{
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'routes';
				$db = CmsApp::get_instance()->GetDb();
				return $db->GetArray($query);
			});
		global_cache::add_cachable($obj);
	}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * @ignore
	 */
	private static function _find_match($needle,array $haystack,bool $exact)
	{
		// split the haystack into an array of 'absolute' or 'regex' matches
		$absolute = [];
		$regex = [];
        foreach( $haystack as $rec ) {
			if( $exact || (isset($rec['absolute']) && $rec['absolute']) ) {
				$absolute[$rec['term']] = $rec;
			}
			else {
				$regex[] = $rec;
			}
		}

        if( isset( $absolute[$needle] ) ) return $absolute[$needle];

		// do the linear regex thing.
		for( $i = 0, $n = count($regex); $i < $n; $i++ ) {
			$rec = $regex[$i];
			if( $rec->matches($needle) ) return $rec;
		}

		return FALSE;
	}

	/**
	 * UNUSED
	 * credits: temporal dot pl at gmail dot com
	 * reference: http://php.net/manual/en/function.array-search.php
	 * @ignore
	 */
	private static function route_binarySearch($needle,array $haystack,$comparator)
	{
		if( count($haystack) == 0 ) return FALSE;

		$high = count( $haystack ) - 1;
		$low = 0;
		while( $high >= $low ) {
			$probe = (int)Floor( ( $high + $low ) / 2 );
			$comparison = $comparator( $haystack[$probe]['term'], $needle );
			if( $comparison < 0 ) {
				$low = $probe + 1;
			}
			elseif( $comparison > 0 ) {
				$high = $probe - 1;
			}
			else {
				return $probe;
			}
		}

		//The loop ended without a match
		//Compensate for needle greater than highest haystack element
		if($comparator($haystack[count($haystack)-1]['term'], $needle) < 0) $probe = count($haystack);
		return FALSE;
	}

	/**
	 * Test whether the specified route exists.
	 *
	 * @param CmsRoute $route The route object
	 * @param bool     $static_only A flag indicating that only static routes should be checked.
	 * @return bool
	 */
	public static function route_exists(CmsRoute $route,bool $static_only = FALSE) : bool
	{
		self::_load_static_routes();
		if( is_array(self::$_routes) ) {
			if( isset(self::$_routes[$route->signature()]) ) return TRUE;
		}

		if( $static_only ) return FALSE;

		if( is_array(self::$_dynamic_routes) ) {
			if( isset(self::$_dynamic_routes[$route->signature()]) ) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Find a route that matches the specified string
	 *
	 * @param string $str The string to test against (usually an incoming url request)
	 * @param bool $exact Perform an exact string match rather than a regex match.
	 * @param bool $static_only A flag indicating that only static routes should be checked.
	 * @return mixed CmsRoute the matching route, or null.
	 */
	public static function find_match(string $str,bool $exact = FALSE,bool $static_only = FALSE)
	{
		self::_load_static_routes();

		if( is_array(self::$_routes) ) {
			$res = self::_find_match($str,self::$_routes,$exact);
			if( is_object($res) ) return $res;
		}

		if( $static_only ) return;

		if( is_array(self::$_dynamic_routes) ) {
			$res = self::_find_match($str,self::$_dynamic_routes,$exact);
			if( is_object($res) ) return $res;
		}
	}

	/**
	 * Add a static route.
	 * This method will return TRUE, and do nothing, if the route already exists.
	 * The routes-cache will be cleared if the route is successfully added to the database.
	 *
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @since 1.11
	 * @param CmsRoute $route The route to add.
	 * @return bool, or not at all
	 */
	public static function add_static(CmsRoute &$route)
	{
		self::_load_static_routes();
		if( self::route_exists($route) ) return TRUE;

		$query = 'INSERT INTO '.CMS_DB_PREFIX.'routes (term,key1,key2,key3,data,created) VALUES (?,?,?,?,?,NOW())';
		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->Execute($query,[$route['term'], $route['key1'], $route['key2'], $route['key3'], serialize($route)]);
		if( $dbr ) {
			self::_clear_static_cache();
			return TRUE;
		}
		die($db->sql.' -- '.$db->ErrorMsg());
	}

	/**
	 * Delete a static route.
	 * The routes-cache will be cleared if the route is successfully removed from the database.
	 *
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @since 1.11
	 * @param mixed string | null $term The route-regex to search for
	 * @param mixed string | null $key1 Optional value recorded in table key1 field (originator)
	 * @param mixed string | null $key2 Optional value recorded in table key2 field (if $key1 is non-NULL)
	 * @param mixed string | null $key3 Optional value recorded in table key3 field (if $key1 and $key2 are non-NULL)
	 * @return bool
	 */
	public static function del_static($term,$key1 = null,$key2 = null,$key3 = null) : bool
	{
		$where = [];
		$parms = [];
		if( $term ) {
			$where[] = 'term = ?';
			$parms[] = $term;
		}

		if( !is_null($key1) ) {
			$where[] = 'key1 = ?';
			$parms[] = $key1;

			if( !is_null($key2) ) {
				$where[] = 'key2 = ?';
				$parms[] = $key2;

				if( !is_null($key3) ) {
					$where[] = 'key3 = ?';
					$parms[] = $key3;
				}
			}
		}

		if( !$where ) return FALSE;

		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'routes WHERE ';
		$query .= implode(' AND ',$where);
		$dbr = $db->Execute($query,$parms);
		if( $dbr ) {
			self::_clear_static_cache();
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Add a dynamic route.
	 * Dynamic routes are not stored to the database, and are checked after static routes when searching for a match.
	 * This method will return FALSE if the route already exists (static or dynamic)
	 *
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @since 1.11
	 * @param CmsRoute $route The dynamic route object to add
	 * @return bool.
	 */
	public static function add_dynamic(CmsRoute $route) : bool
	{
		if( self::route_exists($route) ) return FALSE;
		if( !is_array(self::$_dynamic_routes) ) self::$_dynamic_routes = [];
		self::$_dynamic_routes[$route->signature()] = $route;
		return TRUE;
	}

	/**
	 * Register a new route.
	 * This is an alias of the add_dynamic() method.
	 *
	 * @see cms_route_manager::add_dynamic()
	 * @param CmsRoute $route The route to register
	 * @return bool
	 */
	public static function register(CmsRoute $route) : bool
	{
		return self::add_dynamic($route);
	}

	/**
	 * Load all modules and call relevant Initialize method to ensure
	 * that their dynamic routes are created.
	 *
	 * @deprecated since 2.3 - this method does nothing because its
	 * functionality happens elsewhere, for each request,
	 */
	public static function load_routes()
	{
/*		global $CMS_ADMIN_PAGE;
		$flag = false;
		if( isset($CMS_ADMIN_PAGE) ) {
			// hack to force modules to register their routes.
			$flag = $CMS_ADMIN_PAGE;
			unset($CMS_ADMIN_PAGE);
		}

		// TODO
		$modules = ModuleOperations::get_instance()->GetLoadedModules();
		foreach( $modules as $name => &$module ) {
			if( $flag ) {
			    $module->InitializeAdmin();
			}
			else {
				$module->InitializeFrontend();
			}
		}
		if( $flag ) $CMS_ADMIN_PAGE = $flag;
*/
	}

	/**
	 * Reset the static route table.
	 *
	 * @since 1.11
	 * @author Robert Campbell
	 * @internal
	 */
	public static function rebuild_static_routes()
	{
		// clear the route table and cache
		self::_clear_static_cache();
		$db = CmsApp::get_instance()->GetDb();
		$query = 'TRUNCATE TABLE '.CMS_DB_PREFIX.'routes';
		$db->Execute($query);

		// get content routes
		$query = 'SELECT content_id,page_url FROM '.CMS_DB_PREFIX."content WHERE active=1 AND COALESCE(page_url,'') != ''";
		$tmp = $db->GetArray($query);
		if( $tmp ) {
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				$route = CmsRoute::new_builder($tmp[$i]['page_url'],'__CONTENT__',$tmp[$i]['content_id'],'',TRUE);
				self::add_static($route);
			}
		}

		// get module routes
		$installed = ModuleOperations::get_instance()->GetInstalledModules();
		foreach( $installed as $module_name ) {
			$modobj = cms_utils::get_module($module_name);
			if( !$modobj ) continue;
			$routes = $modobj->CreateStaticRoutes();
		}
	}

	/**
	 * Load existing static routes from the database via the cache
	 * This method will also refresh the cache from the database if the cache cannot be found.
	 * Note: It should not be necessary to load routes, as this method is called internally.
	 * @internal
	 */
	private static function _load_static_routes()
	{
		if( self::$_routes_loaded ) return;

		$data = cms_cache_handler::get_instance()->get('routes');
		if( $data ) {
			self::$_routes = [];
			for( $i = 0, $n = count($data); $i < $n; ++$i ) {
				$obj = $data[$i]['data'];
				if( is_object($obj) ) {
					self::$_routes[$obj->signature()] = $obj;
				}
			}
			self::$_routes_loaded = TRUE;
		}
	}

	/**
	 * @ignore
	 * Note: dynamic routes don't get cleared.
	 */
	private static function _clear_static_cache()
	{
		cms_cache_handler::get_instance()->erase('routes');
		self::$_routes = null;
		self::$_routes_loaded = FALSE;
	}
} // class

