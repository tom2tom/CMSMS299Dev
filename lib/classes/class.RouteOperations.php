<?php
/*
Functions for managing CMSMS routes
Copyright (C) 2016-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\LoadedDataType;
use CMSMS\Lone;
use CMSMS\Route;
use Exception;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;

/* routes-table fields overview
Runtime work uses the merged contents of the data field. Other fields are for table-management
  CHECKME UTF char(s) OK in term ?
  term URL-slug or regex to check prety-url against
  dest1 destination identifier, a module name or __PAGE__
  page supplementary destination identifier, a page id or NULL
  delmatch custom row-identifier for tailoring static-route deletions, or NULL
*/
/* supports route_binarySearch() ??
if( !function_exists('_internal_cmp_routes') ) {
	/* *
	 * @internal UNUSED
	 * @ignore
	 * /
	function _internal_cmp_routes($a,$b)
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
 * @since  1.9
 */
final class RouteOperations
{
	// static properties here >> Lone property|ies ?
	/**
	 * @var bool Whether the 'static' routes array has been populated
	 * @ignore
	 */
	private static $_routes_loaded = FALSE;

	/**
	 * @var array Intra-request cache of routes data
	 * Populated from database routes table and page-URL's
	 * Each row's key is a 'signature' derived from route properties,
	 *  and its value is an array
	 *   'term' regex to match,
	 *   'exact' for (default) type of match,
	 *   'data' to construct a Route when needed
	 * The rows are caseless-sorted on 'term'
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
	 * Each row's key is a 'signature' derived from route properties,
	 *  and its value is an array
	 *   'term' regex to match,
	 *   'exact' for (default) type of match,
	 *   'data' to construct a Route when needed
	 * The rows are caseless-sorted on 'term'
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
	private function __clone(): void {}

	/**
	 * Initialize non-static routes global-cache
	 * Pretty URL's, hence routes, are processed only during frontend
	 * requests (ATM at least). So this calls InitializeFrontend() for
	 * all relevant modules, to create their dynamic routes, if any.
	 */
	public static function load_setup()
	{
		$obj = new LoadedDataType('routes',function(bool $force, ...$args) {
			// in case there's something unexpected in any InitializeFrontend(), block recursion
			static $sema4 = 0;
			if( ++$sema4 !== 1 ) {
				--$sema4;
				return;
			}
			$data = ( !empty(self::$_dynamic_routes) ) ? self::$_dynamic_routes : [];
			self::$_dynamic_routes = [];

			//eventually we should be able to just ...
			//$polls = Lone::get('LoadedMetadata')->get('capable_modules',$force,CMSMS\CapabilityType::ROUTE_MODULE);
			$modops = Lone::get('ModuleOperations');
			$extras = $modops->GetLoadableModuleNames(); // hence incremental changes
			$skips = Lone::get('LoadedMetadata')->get('methodic_modules',$force,'RegisterRoute',FALSE ); //deprecated since 3.0
			$polls = array_diff($extras,$skips);

			foreach( $polls as $modname ) {
				$modops->PollModule($modname,$force,function($mod) {
					if( $mod ) {
						$mod->InitializeFrontend();
					}
				});
			}

			$fresh = array_merge($data,self::$_dynamic_routes);
			Lone::get('LoadedData')->set('routes',$fresh);
			self::$_dynamic_routes = $fresh;
			--$sema4;
			return self::$_dynamic_routes; // old and/or new, or maybe nothing
		});
		Lone::get('LoadedData')->add_type($obj);
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
	 * @return array route-property/ies | empty
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
		return [];
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
		$a = trim($needle,$patn);
		if( empty($props['exact']) ) $patn .= $props['term'][0];
		$b = trim($props['term'],$patn);
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
	private static function regex_find($needle,$haystack,$exact = FALSE)
	{
		foreach( $haystack as &$props ) {
			if( $exact || empty($props['exact']) ) {
				if( $exact ) {
					if( self::is_exact($props['term']) ) {
						$props['term'] = '~^'.str_replace('~','\\~',$props['term']).'$~'; // TODO preg_quote() term ??
					}
				}
				$matches = [];
				$r = preg_match($props['term'],$needle,$matches);
				if( $r == 1 && $matches ) {
					$props['results'] = array_filter($matches,function ($k) {
						return !is_numeric($k);
					},ARRAY_FILTER_USE_KEY);
					return $props;
				}
			}
		}
		unset($props);
	}

	/**
	 * Test whether the specified object|array properties match the specified string.
	 * Depending upon object-properties and $exact, either a string comparison or
	 * regular expression match is performed.
	 * String comparison is caseless, and assumes single-byte case-conversion is ok.
	 *
	 * @param mixed $a Route object | array with member 'term' and optional 'exact'
	 * @param string $str The string to be checked
	 * @param bool $exact Optional flag whether to try for an
	 *  exact string-match regardless of recorded object|array properties.
	 * @return int <0 | =0 | >0 suitable for (exact-match) binary search
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
			$a = trim($pattern,"/ \t\n\r\0\x0B");
			$b = trim($str,"/ \t\n\r\0\x0B");
			return strcasecmp($a,$b);
		}

		$matches = [];
		$res = preg_match($pattern,$str,$matches);
		if( $a instanceof Route ) {
			if( $matches ) {
				$a->results = $matches;
			}
		}
		elseif( $matches ) {
			$a['results'] = $matches;
		}
		return ($res == 1) ? 0 : 1; //strcmp-comparable reporting
	}

	/**
	 * Test whether the specified route exists.
	 *
	 * @param Route $route The route object
	 * @param bool  $static_only Optional flag indicating that only
	 *  static routes should be checked. Default FALSE.
	 * @return bool
	 */
	public static function route_exists(Route $route,bool $static_only = FALSE): bool
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
	 * @param bool $exact Optional flag instructing an exact check regardless of object properties. Default FALSE.
	 * For avoidance of doubt: this indicates 'maybe I got it wrong, check the other type too'
	 * @param bool $static_only Optional flag indicating that only static (db-recorded) routes should be checked. Default FALSE.
	 * @return mixed Route the matching route | null
	 */
	public static function find_match(string $str,bool $exact = FALSE,bool $static_only = FALSE)
	{
		self::load_static_routes();

		if( is_array(self::$_routes) ) {
			$row = self::_find_match($str,self::$_routes,$exact);
			if( $row ) {
				$props = json_decode($row['data'],TRUE);
				// Route constructor expects: $pattern,$dest1,$defaults,$is_exact,$page,$delmatch
				$parms = [
					$props['term'],
					((!empty($props['dest1'])) ? $props['dest1'] : Route::PAGE),
					$props['exact'] ?? FALSE,
					((!empty($props['page'])) ? (int)$props['page'] : NULL), // page 0 N/A
					$props['delmatch'] ?? NULL,
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
				$props = json_decode($row['data'],TRUE);
				$parms = [
					$props['term'],
					((!empty($props['dest1'])) ? $props['dest1'] : Route::PAGE),
					$props['defaults'] ?? NULL,
					$props['exact'] ?? FALSE,
					((!empty($props['page'])) ? (int)$props['page'] : NULL),
					$props['delmatch'] ?? NULL,
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
	 * @since 1.11
	 * @param Route $route The dynamic route object to add
	 * @return TRUE always
	 */
	public static function add(Route $route): bool
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
		uasort(self::$_dynamic_routes,function($a,$b) {
			return strcasecmp($a['term'],$b['term']);
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
	public static function register(Route $route): bool
	{
		assert(!CMS_DEPREC,new DeprecationNotice('method','add_dynamic'));
		return self::add_dynamic($route);
	}

	/**
	 * Populate intra-request routes (from modules via the 'routes' cache)
	 */
	public static function load_routes()
	{
		if( self::$_dynamic_routes_loaded ) return;
		self::load_setup();
		self::$_dynamic_routes = Lone::get('LoadedData')->get('routes');
		self::$_dynamic_routes_loaded = TRUE;
	}

	// ========== STATIC ROUTES ==========

	/**
	 * Add (or actually, upsert) a static route.
	 * This clears the local static-routes cache after successful completion.
	 *
	 * @since 1.11
	 * @param Route $route The route to add.
	 * @return bool indicating success
	 * @throws Exception if database upsert fails
	 */
	public static function add_static(Route $route)
	{
		$arr = (array)$route;
		$props = array_intersect_key(reset($arr),[
			'defaults' => 1,
			'delmatch' => 1,
			'dest1' => 1,
			'exact' => 1,
			'page' => 1,
			'term' => 1,
		]);
		$data = json_encode($props,JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		//as well as combined data, we separately record some individual properties to facilitate table-content management
		$term = $props['term'] ?? NULL; // will fail if NULL
		$dest1 = ( isset($props['dest1']) && ($props['dest1'] || is_numeric($props['dest1'])) ) ? $props['dest1'] : NULL;
		$page = ( isset($props['page']) && ($props['page'] || is_numeric($props['page'])) ) ? $props['page'] : NULL;
		$delmatch = ( isset($props['delmatch']) && ($props['delmatch'] || is_numeric($props['delmatch'])) ) ? $props['delmatch'] : NULL;

		$db = Lone::get('Db');
		$tbl = CMS_DB_PREFIX.'routes';
		$query = "UPDATE $tbl SET term=?,delmatch=?,data=? WHERE dest1=? AND page=?"; // TODO dest1+page not a unique combination
		$db->execute($query,[$term,$delmatch,$data,$dest1,$page]);
		$query = <<<EOS
INSERT INTO $tbl (term,dest1,page,delmatch,data,create_date)
SELECT ?,?,?,?,?,? FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM $tbl T WHERE T.term=? AND T.dest1=? AND T.page=?)
EOS;
		$longnow = $db->DbTimeStamp(time(),false);
		$dbr = $db->execute($query,[$term,$dest1,$page,$delmatch,$data,$longnow,$term,$dest1,$page]);
		if( $dbr ) {
			self::clear_static_routes();
			return TRUE;
		}
		throw new Exception ($db->sql.' -- '.$db->errorMsg());
	}

	/**
	 * Delete a static route.
	 * This clears the local static-routes cache after successful completion.
	 *
	 * @since 1.11
	 * @param mixed string | null $pattern The route-string to search for
	 * @param mixed string | null $dest1 Optional value recorded in table
	 *  dest1 (destination) field
	 * @param mixed string | null $page Optional value recorded in table
	 *  page (page-id) field (used here if $dest1 is non-NULL)
	 * @param mixed string | null $delmatch Optional value recorded in
	 *  table delmatch (filter) field. Pre-3.0 this was used only if
	 *  both $dest1 and $page were non-NULL)
	 * @return bool indicating success
	 */
	public static function del_static($pattern,$dest1 = '',$page = '',$delmatch = ''): bool
	{
		$wheres = [];
		$parms = [];
		if( $pattern ) {
			$wheres[] = 'term = ?';
			$parms[] = $pattern;
		}

		if( $page || is_numeric($page) ) {
			$wheres[] = "dest1 = '".Route::PAGE."'";
			$wheres[] = 'page = ?';
			$parms[] = (string)$page;
		}
		elseif( $dest1 ) {
			$wheres[] = 'dest1 = ?';
			$parms[] = (string)$dest1;
		}

		if( $delmatch || is_numeric($delmatch) ) {
			$wheres[] = 'delmatch = ?';
			$parms[] = (string)$delmatch;
		}

		if( !$wheres ) return FALSE;

		$db = Lone::get('Db');
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'routes WHERE ';
		$query .= implode(' AND ',$wheres);
		$dbr = $db->execute($query,$parms);
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
	 * @internal
	 */
	public static function rebuild_static_routes()
	{
		// clear the route table and local cache
		self::clear_static_routes();
		$db = Lone::get('Db');
		$query = 'TRUNCATE '.CMS_DB_PREFIX.'routes';
		$db->execute($query);

		// get module routes
		$modops = Lone::get('ModuleOperations');
		$modnames = Lone::get('LoadedMetadata')->get('methodic_modules',TRUE,'CreateStaticRoutes');
		if( $modnames ) {
			foreach( $modnames as $modname ) {
				$mod = $modops->get_module_instance($modname);
				$mod->CreateStaticRoutes();
				$mod = NULL; // help the garbage-collector
			}
		}
		// and routes from module-method alias
		$modnames = Lone::get('LoadedMetadata')->get('methodic_modules',TRUE,'CreateRoutes');
		if( $modnames ) {
			foreach( $modnames as $modname ) {
				$mod = $modops->get_module_instance($modname);
				$mod->CreateRoutes();
				$mod = NULL;
			}
		}

		// get content routes
		//TODO check this is always done after page-URL|content change
		$query = 'SELECT content_id,page_url FROM '.CMS_DB_PREFIX."content WHERE active=1 AND COALESCE(page_url,'') != ''";
		$data = $db->getArray($query);
		if( $data ) {
			for( $i = 0,$n = count($data); $i < $n; $i++ ) {
				$route = new Route($data[$i]['page_url'],'__CONTENT__',[],TRUE,$data[$i]['content_id']);
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
		$db = Lone::get('Db');
		$query = 'SELECT data FROM '.CMS_DB_PREFIX."routes WHERE data != '' AND data IS NOT NULL";
		$rows = $db->getCol($query);
		if( $rows ) {
			for( $i = 0,$n = count($rows); $i < $n; ++$i ) {
				$data = $rows[$i];
				$props = json_decode($data,TRUE);
				$sig = self::get_signature($props);
				self::$_routes[$sig] = [
				'term' => $props['term'],
				'exact' => !empty($props['exact']),
				'data' => $data,
				];
			}
			uasort(self::$_routes,function($a,$b) {
				return strcasecmp($a['term'],$b['term']);
			});
		}
		self::$_routes_loaded = TRUE;
	}

	/**
	 * @ignore
	 */
	private static function clear_static_routes()
	{
		self::$_routes = NULL;
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
	private static function get_signature($a): string
	{
		if( $a instanceof Route ) {
			return $a->get_signature();
		}
		$s = json_encode($a,JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return Crypto::hash_string($s);
	}

	/**
	 * @ignore
	 */
	private static function is_exact(string $pattern): bool
	{
		$sl = strcspn($pattern,'^*?+.-<[({$');
		if( $sl < strlen($pattern) ) {
			$res = preg_match($pattern,'foobar');
			return ($res === FALSE || preg_last_error() != PREG_NO_ERROR);
		}
		return TRUE;
	}
} // class
