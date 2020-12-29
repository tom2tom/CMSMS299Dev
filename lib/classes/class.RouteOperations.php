<?php
/*
Functions for managing CMSMS routes
Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\ModuleOperations;
use CMSMS\Route;
use CMSMS\SysDataCache;
use CMSMS\SysDataCacheDriver;
use Exception;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;

/* supports route_binarySearch() ??
if( !function_exists('__internal_cmp_routes') ) {
	/* *
	 * @internal UNUSED
	 * @ignore
	 * /
	function __internal_cmp_routes($a,$b)
	{
		return strcmp($a['term'],$b['term']);
	}
}
*/
/**
 * A class to manage CMSMS routes.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since  1.9
 */
final class RouteOperations
{
    // static properties here >> StaticProperties class ?
	/**
	 * @var bool Whether the 'static' routes array has been populated
	 * @ignore
	 */
	private static $_routes_loaded = FALSE;

	/**
	 * @var array Intra-request cache of routes data
	 * Populated from database routes table and page-URL's
	 * Each row's key is a 'signature' derived from route properties.
	 * Value is array with 'term' to match, 'exact' for (default) type of match,
	 *  and 'data' to construct a Route when needed
	 * Caseless sorted on 'term'
	 * @ignore
	 */
	private static $_routes;

	/**
	 * @var bool Whether the 'dynamic' routes array has been populated
	 * @ignore
	 */
	private static $_dynamic_routes_loaded = FALSE;

	/**
	 * @var array Intra-request cache of routes data
	 * Populated by modules which register intra-request routes
	 * Each row's key is a 'signature' derived from route properties.
	 * Value is array with 'term' to match, 'exact' for (default) type of match,
	 *  and 'data' to construct a Route when needed
	 * Caseless sorted on 'term'
	 * @ignore
	 */
	private static $_dynamic_routes;

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Initialize non-static routes global-cache
	 */
	public static function setup()
	{
		$obj = new SysDataCacheDriver('routes',function()
		{
			/*
			Load all relevant modules and call relevant Initialize method
			to create their dynamic routes.
			Pretty URL's, hence routes, are processed only during frontend requests.
			*/

			$tmp = null;
//			$tmp = self::$_dynamic_routes;
//			self::$_dynamic_routes = null;

			if( AppState::test_state(AppState::STATE_ADMIN_PAGE) ) { // TODO USELESS check, AND exclude login-state
				AppState::remove_state(AppState::STATE_ADMIN_PAGE);
				$flag = true;
			}
			$modops = ModuleOperations::get_instance();
			$extras = $modops->GetLoadableModuleNames(); // hence incremental changes
			$skips = $modops->GetMethodicModules('IsRoutableModule',FALSE); //deprecated since 2.99
			$polls = array_diff($extras, $skips);
//eventually we could just... $polls = $modops->GetCapableModules(CMSMS\CoreCapabilities::ROUTE_MODULE);

			$modops->PollModules($polls);

			if( isset($flag) ) AppState::add_state(AppState::STATE_ADMIN_PAGE);

			if( $tmp ) {
				//TODO merge $tmp and self::$_dynamic_routes, if count bigger after that ...
				SysDataCache::get_instance()->set('routes', self::$_dynamic_routes);
			}
			return self::$_dynamic_routes; // old and new
		});
		SysDataCache::get_instance()->add_cachable($obj);
	}

	// ========== FINDING|MATCHING ==========

	/**
	 * @ignore
`	 *
	 * @param string $needle What we are looking for
	 * @param array $haystack Sorted array of routes-data to search
	 * @param bool $exact Optional flag whether to include in this search
	 *  regex-matches against patterns that are flagged non-regex. Default false.
	 *  (This is only helpful if the regex-flagging process has been ignored.)
	 * @return mixed route-properties array | null
	 */
	private static function _find_match(string $needle,array $haystack,bool $exact = FALSE)
	{
		$props = self::literal_find($needle,$haystack);
		if( $props ) {
			return $props;
		}
		$props = self::regex_find($needle,$haystack,$exact);
		if( $props ) {
			return $props;
		}
	}

	/**
	 * Binary search a sorted strings-array for the specified string
	 * credit: dennis dot decoene at removthis dot moveit dot be
	 * reference: http://php.net/manual/en/function.array-search.php
	 *
	 * @param string $needle What we are looking for
	 * @param array $haystack Sorted array of routes-data to search
	 * @return mixed array $haystack member (value) | null
	 */
	private static function literal_find($needle,$haystack)
	{
		$low = 0;
		$high = $c = count($haystack);

		while( $high - $low > 1 ) {
			$probe = ($high + $low) / 2;
			$row = array_slice($haystack,$probe,1);
			$props = reset($row);
			$r = self::literal_compare($needle,$props);
			if( $r == 0 ) {
				return $props;
			}
			elseif( $r > 0 ) {
				$low = $probe;
			}
			else {
				$high = $probe;
			}
		}

		if( $high != $c ) {
			$row = array_slice($haystack,$high,1);
			$props = reset($row);
			if( self::literal_compare($needle,$props) == 0 ) {
				return $props;
			}
		}
	}

	private static function literal_compare($needle,$props)
	{
		$patn = "/ \t\n\r\0\x0B";
		$a = trim($needle, $patn);
		if( empty($props['exact']) ) $patn .= $props['term'][0];
		$b = trim($props['term'], $patn);
		return strcasecmp($a,$b);
	}

	/*
	 * Search for a regex match for a specified string.
	 * Nothing fancy, the regexes probably include repteated named sub-patterns
	 *
	 * @param string $needle What we are looking for
	 * @param array $haystack Sorted array of routes-data to search
	 * @param bool $exact Optional flag whether to include in this search
	 *  patterns that are flagged non-regex. (This is only helpful if
	 *  the regex-flagging process has been ignored.) Default false.
	 * @return mixed array $haystack member (value) | null
	 */
	private static function regex_find($needle,$haystack,$exact=FALSE)
	{
		foreach( $haystack as &$props ) {
			if( $exact || empty($props['exact']) ) {
				if( $exact ) {
					if( self::is_exact($props['term']) ) {
						$props['term'] = '~'.str_replace('~', '\\~', $props['term']).'~';
					}
				}
				$matches = null;
				$r = preg_match($props['term'],$needle,$matches);
				if( $r == 1 && $matches ) {
					$props['results'] = array_filter($matches, function ($k)
					{
						return !is_numeric($k);
					}, ARRAY_FILTER_USE_KEY);
					return $props;
				}
			}
		}
		unset($props);
	}

	/**
	 * Test if the specified object|array properties match the specified string.
	 * Depending upon object-properties and $exact, either a string comparison or
	 * regular expression match is performed.
	 * String comparison is caseless, and assumes single-byte case-conversion is ok.
	 *
	 * @param mixed $a Route object | array with member 'term' and optional 'exact'
	 * @param string $str The string to be checked
	 * @param bool $exact Optional flag whether to try for an
	 *  exact string-match regardless of recorded object|array properties.
	 * @return int <0 | ==0 | >0 suitable for (exact-match) binary search
	 *  or 0 | 1 for regex match (NB valued like strcmp, not like preg_match)
	 */
	public static function is_match(&$a,string $str,bool $exact = FALSE)
	{
		if( $a instanceof Route ) {
			$pattern = $a->term;
			if( !$exact ) $exact = $a->exact;
		}
		else {
			$pattern = $a['term'];
			if( !$exact ) $exact = !empty($a['exact']);
		}

		if( $exact ) {
			$a = trim($pattern, "/ \t\n\r\0\x0B");
			$b = trim($str, "/ \t\n\r\0\x0B");
			return strcasecmp($a,$b);
		}

		$matches = null;
		$res = preg_match($pattern,$str,$matches);
		if( $a instanceof Route ) {
			if( $matches ) {
				$a->results = $matches;
			}
		}
		else if( $matches ) {
			$a['results'] = $matches;
		}
		return ($res == 1) ? 0 : 1; //strcmp-comparable reporting
	}

	/**
	 * Test whether the specified route exists.
	 *
	 * @param Route $route The route object
	 * @param bool     $static_only Optional flag indicating that only static routes should be checked. Default FALSE.
	 * @return bool
	 */
	public static function route_exists(Route $route,bool $static_only = FALSE) : bool
	{
		self::load_static_routes();
		$sig = $route->get_signature();
		if( is_array(self::$_routes) ) {
			if( isset(self::$_routes[$sig]) ) return TRUE;
		}

		if( $static_only ) return FALSE;

		self::load_routes(); //incremental load
		if( is_array(self::$_dynamic_routes) ) {
			if( isset(self::$_dynamic_routes[$sig]) ) return TRUE;
		}
		return FALSE;
	}

	/**
	 * Return a route-object that matches the specified string, if match is found
	 *
	 * @param string $str The string whose match is sought (usually an incoming request URL)
	 * @param bool $exact Optional flag instructing an exact string match regardless of object properties. Default FALSE.
	 * @param bool $static_only Optional flag indicating that only static (db-recorded) routes should be checked. Default FALSE.
	 * @return mixed Route the matching route, or null.
	 */
	public static function find_match(string $str,bool $exact = FALSE,bool $static_only = FALSE)
	{
		self::load_static_routes();

		if( is_array(self::$_routes) ) {
			$row = self::_find_match($str,self::$_routes,$exact);
			if( $row ) {
				$props = json_decode($row['data'], TRUE);
				$parms = [
				$props['term'],
				$props['key1'] ?? '',
				$props['defaults'] ?? NULL,
				$props['exact'] ?? FALSE,
				$props['key2'] ?? NULL,
				$props['key3'] ?? NULL,
				];
				$obj = new Route(...$parms);
				$obj->results = $row['results'];
				return $obj;
			}
		}

		if( $static_only ) return;

		self::load_routes(); //incremental load
		if( is_array(self::$_dynamic_routes) ) {
			$row = self::_find_match($str,self::$_dynamic_routes,$exact);
			if( $row ) {
				$props = json_decode($row['data'], TRUE);
				$parms = [
				$props['term'],
				$props['key1'] ?? '',
				$props['defaults'] ?? NULL,
				$props['exact'] ?? FALSE,
				$props['key2'] ?? NULL,
				$props['key3'] ?? NULL,
				];
				$obj = new Route(...$parms);
				$obj->results = $row['results'];
				return $obj;
			}
		}
	}

	// ========== NON-STATIC ROUTES ==========

	/**
	 * Add an intra-request route.
	 * Dynamic routes are not stored to the database, and are checked
	 *  after static routes when searching for a match.
	 * This method will do nothing and return TRUE if the route already exists (static or dynamic)
	 *
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @since 1.11
	 * @param Route $route The dynamic route object to add
	 * @return TRUE always
	 */
	public static function add(Route $route) : bool
	{
		if( self::route_exists($route) ) return TRUE; //TODO v. slow check!
		if( !is_array(self::$_dynamic_routes) ) self::$_dynamic_routes = [];
		$arr = (array)$route;
		$props = reset($arr);
		$sig = self::get_signature($props);
		$data = json_encode($props,JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		self::$_dynamic_routes[$sig] = [
		'term' => $props['term'],
		'exact' => !empty($props['exact']),
		'data' => $data,
		];
		uasort(self::$_dynamic_routes, function ($a,$b)
		{
			return strcasecmp($a['term'], $b['term']);
		});
		return TRUE;
	}

	/**
	 * Register a new route.
	 * This is an alias of the add() method.
	 *
	 * @see RouteOperations::add_dynamic()
	 * @param Route $route The route to register
	 * @return bool
	 */
	public static function register(Route $route) : bool
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','add_dynamic'));
		return self::add_dynamic($route);
	}

	/**
	 * Populate intra-request routes (from modules via the 'routes' cache)
	 */
	public static function load_routes()
	{
		if( self::$_dynamic_routes_loaded ) return;
		self::setup();
		self::$_dynamic_routes = SysDataCache::get_instance()->get('routes');
		self::$_dynamic_routes_loaded = TRUE;
	}

	// ========== STATIC ROUTES ==========

	/**
	 * Add (or actually, upsert) a static route.
	 * This clears the local static-routes cache after successful completion.
	 *
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @since 1.11
	 * @param Route $route The route to add.
	 * @return bool indicating success
	 */
	public static function add_static(Route $route)
	{
		//as well as combined data, we separately record some individual properties to facilitate deletion
		$arr = (array)$route;
		$props = reset($arr);
		$data = json_encode($props,JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$key1 = ( isset($props['key1']) && ($props['key1'] || is_numeric($props['key1'])) ) ? $props['key1'] : NULL;
		$key2 = ( isset($props['key2']) && ($props['key2'] || is_numeric($props['key2'])) ) ? $props['key2'] : NULL;
		$key3 = ( isset($props['key3']) && ($props['key3'] || is_numeric($props['key3'])) ) ? $props['key3'] : NULL;

		$db = AppSingle::Db();
		$tbl = CMS_DB_PREFIX.'routes';
		$query = "UPDATE $tbl SET term=?,data=? WHERE key1=? AND key2=? AND key3=?";
		$db->Execute($query, [$props['term'],$data,$key1,$key2,$key3]);
		$query = <<<EOS
INSERT INTO $tbl (term,key1,key2,key3,data)
SELECT ?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM $tbl T WHERE T.key1=? AND T.key2=? AND T.key3=?)
EOS;
		$dbr = $db->Execute($query, [$props['term'],$key1,$key2,$key3,$data,$key1,$key2,$key3]);
		if( $dbr ) {
			self::clear_static_routes();
			return TRUE;
		}
		throw new Exception ($db->sql.' -- '.$db->ErrorMsg());
	}

	/**
	 * Delete a static route.
	 * This clears the local static-routes cache after successful completion.
	 *
	 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
	 * @since 1.11
	 * @param mixed string | null $pattern The route-string to search for
	 * @param mixed string | null $key1 Optional value recorded in table key1 (destination) field
	 * @param mixed string | null $key2 Optional value recorded in table key2 (page-id) field (used here if $key1 is non-NULL)
	 * @param mixed string | null $key3 Optional value recorded in table key3 (user-filter) field (used here if $key1 and $key2 are non-NULL)
	 * @return bool
	 */
	public static function del_static($pattern,$key1 = null,$key2 = null,$key3 = null) : bool
	{
		$where = [];
		$parms = [];
		if( $pattern ) {
			$where[] = 'term = ?';
			$parms[] = $pattern;
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

		$db = AppSingle::Db();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'routes WHERE ';
		$query .= implode(' AND ',$where);
		$dbr = $db->Execute($query,$parms);
		if( $dbr ) {
			self::clear_static_routes();
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Reset the routes-table content (from modules and page URLs)
	 *
	 * @since 1.11
	 * @author Robert Campbell
	 * @internal
	 */
	public static function rebuild_static_routes()
	{
		// clear the route table and local cache
		self::clear_static_routes();
		$db = AppSingle::Db();
		$query = 'TRUNCATE '.CMS_DB_PREFIX.'routes';
		$db->Execute($query);

		// get module routes
		$modops = ModuleOperations::get_instance();
		$modules = $modops->GetMethodicModules('CreateStaticRoutes');
		if( $modules ) {
			foreach( $modules as $modname ) {
				$modinst = $modops->get_module_instance($modname);
				$modinst->CreateStaticRoutes();
				$modinst = null; // help the garbage-collector
			}
		}
		// and routes from module-method alias
		$modules = $modops->GetMethodicModules('CreateRoutes');
		if( $modules ) {
			foreach( $modules as $modname ) {
				$modinst = $modops->get_module_instance($modname);
				$modinst->CreateRoutes();
				$modinst = null;
			}
		}

		// get content routes
		//TODO check this is always done after page-URL|content change
		$query = 'SELECT content_id,page_url FROM '.CMS_DB_PREFIX."content WHERE active=1 AND COALESCE(page_url,'') != ''";
		$tmp = $db->GetArray($query);
		if( $tmp ) {
			for( $i = 0, $n = count($tmp); $i < $n; $i++ ) {
				$route = new Route($tmp[$i]['page_url'],'__CONTENT__',null,TRUE,$tmp[$i]['content_id']);
				self::add_static($route);
			}
		}
	}

	/**
	 * Load routes recorded in the database (by modules and for page-URLs)
	 * @see RouteOperations::rebuild_static_routes()
	 * @access private
	 */
	private static function load_static_routes()
	{
		if( self::$_routes_loaded ) return;

		self::$_routes = [];
		$db = AppSingle::Db();
		$query = 'SELECT data FROM '.CMS_DB_PREFIX.'routes WHERE data != "" AND data IS NOT NULL';
		$rows = $db->GetCol($query);
		if( $rows ) {
			for( $i = 0, $n = count($rows); $i < $n; ++$i ) {
				$data = $rows[$i];
				$props = json_decode($data, TRUE);
				$sig = self::get_signature($props);
				self::$_routes[$sig] = [
				'term' => $props['term'],
				'exact' => !empty($props['exact']),
				'data' => $data,
				];
			}
			uasort(self::$_routes, function($a, $b)
			{
				return strcasecmp($a['term'], $b['term']);
			});
		}
		self::$_routes_loaded = TRUE;
	}

	/**
	 * @ignore
	 */
	private static function clear_static_routes()
	{
		self::$_routes = null;
		self::$_routes_loaded = FALSE;
	}

	// ========== UTILITES ==========

	/**
	 * Get the 'signature' of $a
	 * @access private
	 *
	 * @param mixed $a A Route object, or parameters-array corresponding
	 * to the properties of such an object
	 * @return string
	 */
	private static function get_signature($a) : string
	{
		if( $a instanceof Route ) {
			return $a->get_signature();
		}
		$tmp = json_encode($a,JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return Crypto::hash_string($tmp);
	}

	/**
	 * @ignore
	 */
	private static function is_exact(string $pattern) : bool
	{
		$sl = strcspn($pattern, '^*?+.-<[({$');
		if( $sl < strlen($pattern) ) {
			$res = preg_match($pattern, 'foobar');
			return ($res === FALSE || preg_last_error() != PREG_NO_ERROR);
		}
		return TRUE;
	}
} // class
