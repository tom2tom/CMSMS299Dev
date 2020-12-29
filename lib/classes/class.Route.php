<?php
/*
Class to manage information for a route.
Copyright (C) 2010-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use ArrayAccess;
use CMSMS\Crypto;
use CMSMS\DeprecationNotice;
use CMSMS\RouteOperations;
use const CMS_DEPREC;

/**
 * Class to hold and interact with properties of a route.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since 2.99
 * @since 1.9 as global-namespace CmsRoute
 */
class Route implements ArrayAccess
{
	/**
	 * @ignore
	 */
	private const KEYS = [
	 'defaults', //module-parameters unused here but can be retrieved from outside
	 'exact', //whether term is for exact-matching, if not, then regex
	 'key1', //destination e.g module name, or __CONTENT__
	 'key2', //page id or NULL
	 'key3', //user-defined parameter for 'refining' static-route deletions (usable during deletion if key1 and ke2 are both non-NULL)
	 'term', //exact string or regex to check against
	 'results', //matches-array populated by Operations class from preg_match() results
    ];

	/**
	 * @ignore
	 */
	private $_data;

	/**
	 * Construct a new route object.
	 *
	 * @param string $pattern The route string (exact or regular expression)
	 * @param mixed  $key1 Optional first key. Unless $pattern is exact,
	 *  usually a module name or int page id. Default ''.
	 * @param mixed  $defaults Optional array of parameter defaults for this
	 *  route's destination-module | NULL.  Applicable only when the destination is a module.
	 * @param bool   $is_exact Optional flag indicating whether $pattern is
	 *  for exact-matching. Default FALSE (hence a regular expression).
	 * @param string $key2 Optional second key.
	 * @param string $key3 Optional third key. For user-defined data. Default ''.
	 */
	public function __construct($pattern,$key1 = '',$defaults = NULL,$is_exact = FALSE,$key2 ='',$key3 = '')
	{
		$this->_data['term'] = $pattern;
		$this->_data['exact'] = $is_exact;

		if( is_numeric($key1) && empty($key2) ) {
			$this->_data['key1'] = '__CONTENT__';
			$this->_data['key2'] = (int)$key1;
		}
		else {
			$this->_data['key1'] = $key1;
			$this->_data['key2'] = $key2;
		}
		if( !empty($defaults) ) $this->_data['defaults'] = $defaults;
		if( !empty($key3) ) $this->_data['key3'] = $key3;
	}

	/**
	 * @ignore
	 */
	public function __set($key,$value)
	{
		if( in_array($key,self::KEYS) ) $this->_data[$key] = $value;
	}

	/**
	 * @ignore
	 */
	public function __isset($key)
	{
		return in_array($key,self::KEYS) && isset($this->_data[$key]);
	}

	/**
	 * @ignore
	 */
	public function __get($key)
	{
		return $this->_data[$key] ?? NULL;
	}

	/**
	 * @ignore
	 */
	public function OffsetGet($key)
	{
		if( in_array($key,self::KEYS) && isset($this->_data[$key]) ) return $this->_data[$key];
	}

	/**
	 * @ignore
	 */
	public function OffsetSet($key,$value)
	{
		if( in_array($key,self::KEYS) ) $this->_data[$key] = $value;
	}

	/**
	 * @ignore
	 */
	public function OffsetExists($key)
	{
		return in_array($key,self::KEYS) && isset($this->_data[$key]);
	}

	/**
	 * @ignore
	 */
	public function OffsetUnset($key)
	{
		if( in_array($key,self::KEYS) && isset($this->_data[$key]) ) unset($this->_data[$key]);
	}

	/**
	 * Static convenience function to create a new route.
	 *
	 * @param string $pattern The route string (exact or regular expression)
	 * @param string $key1 Optional first key. Usually a module name
	 * @param string $key2 Optional second key. Default ''
	 * @param array  $defaults Optional array of parameter defaults for this object.
	 *  Ignored unless the destination is a module. Default NULL
	 * @param bool   $is_exact Optional Flag indicating whether $pattern is
	 *  for exact-matching. Default FALSE.
	 * @param string $key3 Optional third key. For user-defined data. Default ''.
	 */
	public static function new_builder($pattern,$key1 = '',$key2 = '',$defaults = NULL,$is_exact = FALSE,$key3 = '')
	{
		return new self($pattern,$key1,$defaults,$is_exact,$key2,$key3);
	}

	/**
	 * Return the signature of this object, a hash derived from its properties
	 */
	public function get_signature() : string
	{
		$tmp = array_intersect_key($this->_data, [
		 'defaults'=>1,
		 'exact'=>1,
		 'key1'=>1,
		 'key2'=>1,
		 'key3'=>1,
		 'term'=>1,
		]);
		$s = json_encode($tmp,JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return Crypto::hash_string($s);
	}

	/**
	 * Return the signature of this object
	 * @deprecated since 2.99 Instead use get_signature()
	 */
	public function signature()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','get_signature'));
		return $this->get_signature();
	}

	/**
	 * Return the route term|pattern (exact string or regex)
	 *
	 * @return string
	 */
	public function get_term()
	{
		return $this->_term;
	}

	/**
	 * Return the destination (module name etc) if any.
	 *
	 * @return mixed string | NULL
	 */
	public function get_dest()
	{
		return $this->_data['key1'] ?? NULL;
	}

	/**
	 * Return the page id, if the destination is a content page.
	 *
	 * @return mixed int page id | NULL
	 */
	public function get_content()
	{
		if( $this->is_content() ) return $this->_data['key2'] ?? NULL;
	}

	/**
	 * Return the default parameters recorded for this object
	 *
	 * @return mixed The default parameters for the route | NULL if none were recorded.
	 */
	public function get_defaults()
	{
		if( !empty($this->_data['defaults']) ) return $this->_data['defaults'];
	}

	/**
	 * Test whether this object is for a content page.
	 *
	 * @return bool
	 */
	public function is_content()
	{
		return ( isset($this->_data['key1']) && $this->_data['key1'] == '__CONTENT__');
	}

	/**
	 * Return matches reported by a regex match.
	 *
	 * @return mixed preg_match matches array | NULL
	 */
	public function get_results()
	{
		return $this->_data['results'] ?? NULL;
	}

	/**
	 * Test if this object matches the specified string.
	 * @deprecated since 2.99 instead use RouteOperations::is_match()
	 *
	 * @param string $str The input string
	 * @param bool $exact Optional flag whether to try for exact string-match
	 *  regardless of recorded object properties.
	 * @return bool indicating success
	 */
	public function matches($str,$exact = FALSE)
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','RouteOperations::is_match'));
		return RouteOperations::is_match($this,$str,$exact);
	}
} // class
