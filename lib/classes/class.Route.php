<?php
/*
Class to manage information for a route.
Copyright (C) 2010-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
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
 * @since 3.0
 * @since 1.9 as global-namespace CmsRoute
 */
class Route implements ArrayAccess
{
	/**
	 * Token stored in a Route's dest1 field when the destination is a content page.
	 */
	const PAGE = '__PAGE__';

	/**
	 * @ignore
	 */
	private const KEYS = [
	 'defaults', // module-parameters array, unused by the route itself but cached for route-processing
	 'delmatch', // custom row-identifier or NULL
	 'dest1', // destination identifier - a module name, or self::PAGE
	 'page', // page id, or NULL if dest1 is a module name
	 'exact', // whether term is for exact-matching, otherwise a regex
	 'term', // exact URL-slug or regex to match
	 'results', // matches-array populated by Operations class from preg_match() results
	];

	/**
	 * @ignore
	 */
	private $_data;

	/**
	 * Construct a new Route object.
	 *
	 * @param string $pattern The route matcher (exact URL-slug or regular expression)
	 * @param mixed string | int $dest1 Optional route destination identifier.
	 *  Unless $pattern is exact, usually a module name or page id. Default NULL.
	 * @param mixed array | null $defaults Optional parameter defaults for this
	 *  route's destination-module. Applicable only when the destination is a module.
	 * @param bool   $is_exact Optional flag indicating whether $pattern is
	 *  for exact-matching. Default FALSE (indicating a regular expression).
	 * @param mixed string | null $page Optional route destination page id. Default NULL
	 * @param mixed string | null $delmatch Optional specific-row identifier.
	 *  May be specified to tailor static-route deletion in a case where
	 *  dest1 and page are both non-falsy i.e. the destination is a content page.
	 */
	public function __construct($pattern,$dest1 = NULL,$defaults = [],$is_exact = FALSE,$page = NULL,$delmatch = NULL)
	{
		$this->_data['term'] = $pattern;

		if( is_numeric($dest1) && !$page ) {
			$this->_data['dest1'] = self::PAGE;
			$this->_data['page'] = (int)$dest1;
		}
		elseif (!$dest1 && $page) {
			$this->_data['dest1'] = self::PAGE;
			$this->_data['page'] = (int)$page;
		}
		else {
			$this->_data['dest1'] = $dest1; //TODO if both these are falsy?
			$this->_data['page'] = $page;
		}

		$this->_data['exact'] = $is_exact;
		if( $defaults ) $this->_data['defaults'] = $defaults;
		if( $delmatch ) $this->_data['delmatch'] = $delmatch;
	}

	/**
	 * @ignore
	 */
	public function __set(string $key,$value) : void
	{
		if( in_array($key,self::KEYS) ) $this->_data[$key] = $value;
	}

	/**
	 * @ignore
	 */
	public function __isset(string $key) : bool
	{
		return in_array($key,self::KEYS) && isset($this->_data[$key]);
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function __get(string $key)// : mixed
	{
		return $this->_data[$key] ?? null;
	}

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet($key)// : mixed
	{
		if( in_array($key,self::KEYS) && isset($this->_data[$key]) ) return $this->_data[$key];
		return null;
	}

	/**
	 * @ignore
	 */
	public function offsetSet($key,$value) : void
	{
		if( in_array($key,self::KEYS) ) $this->_data[$key] = $value;
	}

	/**
	 * @ignore
	 */
	public function offsetExists($key) : bool
	{
		return in_array($key,self::KEYS) && isset($this->_data[$key]);
	}

	/**
	 * @ignore
	 */
	public function offsetUnset($key) : void
	{
		if( in_array($key,self::KEYS) && isset($this->_data[$key]) ) unset($this->_data[$key]);
	}

	/**
	 * Static convenience function to create a new route.
	 *
	 * @param string $pattern The route matcher (exact URL-slug or regular expression)
	 * @param mixed string | int | null $dest1 Optional route destination.
	 *  Unless $pattern is exact, usually a module name or page id. Default NULL.
	 * @param mixed string | null $page Optional page id. Default NULL
	 * @param mixed array | null $defaults Optional parameter defaults for this
	 *  route's destination-module. Applicable only when the destination is a module.
	 * @param bool   $is_exact Optional flag indicating whether $pattern is
	 *  for exact-matching. Default FALSE (indicating a regular expression).
	 * @param mixed string | null $delmatch Optional specific-row identifier.
	 *  May be specified to tailor static-route deletion in a case where
	 *  dest1 and page are both non-falsy i.e. the destination is a content page.
	 */
	public static function new_builder($pattern,$dest1 = NULL,$page = NULL,$defaults = [],$is_exact = FALSE,$delmatch = NULL)
	{
		return new self($pattern,$dest1,$defaults,$is_exact,$page,$delmatch);
	}

	/**
	 * Return the signature of this object, a hash derived from its properties
	 */
	public function get_signature() : string
	{
		$props = array_intersect_key($this->_data, [
			'defaults' => 1,
			'dest1' => 1,
			'delmatch' => 1,
			'exact' => 1,
			'page' => 1,
			'term' => 1,
		]);
		$s = json_encode($props, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		return Crypto::hash_string($s);
	}

	/**
	 * Return the signature of this object
	 * @deprecated since 3.0 Instead use get_signature()
	 */
	public function signature()
	{
		assert(empty(CMS_DEPREC), new DeprecationNotice('method','get_signature'));
		return $this->get_signature();
	}

	/**
	 * Return this object's matcher|pattern (exact URL-slug or regex)
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
		return $this->_data['dest1'] ?? NULL;
	}

	/**
	 * Return the page id, if the destination is a content page.
	 *
	 * @return mixed int | numeric string page id | NULL
	 */
	public function get_content()
	{
		if( isset($this->_data['dest1']) && $this->_data['dest1'] == self::PAGE ) {
			return $this->_data['page'] ?? NULL;
		}
	}

	/**
	 * Return the default parameters recorded for this object
	 *
	 * @return mixed The default (module) parameters for this Route | null if none were recorded.
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
		return ( isset($this->_data['dest1']) && $this->_data['dest1'] == self::PAGE );
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
	 * @deprecated since 3.0 instead use RouteOperations::is_match()
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
//if (!\class_exists('CmsRoute', false)) \class_alias(Route::class, 'CmsRoute', false);
