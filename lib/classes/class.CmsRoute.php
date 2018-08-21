<?php
# Class to hold information for a single route.
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

//namespace CMSMS;

/**
 * Simple global convenience object to hold information for a single route.
 *
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 * @since  1.9
 * @property string $term
 * @property string $key1
 * @property string $key2
 * @property string $key3
 * @property array  $defaults
 * @property string absolute
 * @property string results
 */
class CmsRoute implements \ArrayAccess
{
	/**
	 * @ignore
	 */
	private $_data;

	/**
	 * @ignore
	 */
	private $_results;

	/**
	 * @ignore
	 */
	static private $_keys = ['term','key1','key2','key3','defaults','absolute','results'];

	/**
	 * Construct a new route object.
	 *
	 * @param string $term The route string (or regular expression)
	 * @param string $key1 The first key. Usually a module name.
	 * @param array  $defaults An array of parameter defaults for this module.  Only applicable when the destination is a module.
	 * @param bool $is_absolute Flag indicating whether the term is a regular expression or an absolute string.
	 * @param string $key2 The second key.
	 * @param string $key3 The second key.
	 */
	public function __construct($term,$key1 = '',$defaults = null,$is_absolute = FALSE,$key2 = null,$key3 = null)
	{
		$this->_data['term'] = $term;
		$this->_data['absolute'] = $is_absolute;

		if( is_numeric($key1) && empty($key2) ) {
			$this->_data['key1'] = '__CONTENT__';
			$this->_data['key2'] = (int)$key1;
		}
		else {
			$this->_data['key1'] = $key1;
			$this->_data['key2'] = $key2;
		}
		if( is_array($defaults) ) $this->_data['defaults'] = $defaults;
		if( !empty($key3) ) $this->_data['key3'] = $key3;
	}

	/**
	 * Static convenience function to create a new route.
	 *
	 * @param string $term The route string (or regular expression)
	 * @param string $key1 The first key. Usually a module name
	 * @param string $key2 The second key
	 * @param array  $defaults An array of parameter defaults for this module.  Only applicable when the destination is a module
	 * @param bool $is_absolute Flag indicating whether the term is a regular expression or an absolute string
	 * @param string $key3 The second key
	 */
	public static function &new_builder($term,$key1,$key2 = '',$defaults = null,$is_absolute = FALSE,$key3 = '')
	{
		$obj = new CmsRoute($term,$key1,$defaults,$is_absolute,$key2,$key3);
		return $obj;
	}

	/**
	 * Return the signature for a route
	 */
	public function signature()
	{
		$tmp = serialize($this->_data);
		$tmp = md5($tmp);
		return $tmp;
	}

	/**
	 * @ignore
	 */
	public function OffsetGet($key)
	{
		if( in_array($key,self::$_keys) && isset($this->_data[$key]) ) return $this->_data[$key];
	}

	/**
	 * @ignore
	 */
	public function OffsetSet($key,$value)
	{
		if( in_array($key,self::$_keys) ) $this->_data[$key] = $value;
	}

	/**
	 * @ignore
	 */
	public function OffsetExists($key)
	{
		if( in_array($key,self::$_keys) && isset($this->_data[$key]) ) return TRUE;
		return FALSE;
	}


	/**
	 * @ignore
	 */
	public function OffsetUnset($key)
	{
		if( in_array($key,self::$_keys) && isset($this->_data[$key]) ) unset($this->_data[$key]);
	}

	/**
	 * Returns the route term (string or regex)
	 *
	 * @deprecated
	 * @return string
	 */
	public function get_term()
	{
		return $this->_term;
	}

	/**
	 * Retrieve the destination module name.
	 *
	 * @deprecated
	 * @return string Destination module name. or null.
	 */
	public function get_dest()
	{
		return $this->_data['key1'];
	}

	/**
	 * Retrieve the page id, if the destination is a content page.
	 *
	 * @deprecated
	 * @return int Page id, or null.
	 */
	public function get_content()
	{
		if( $this->is_content() ) return $this->_data['key2'];
	}

	/**
	 * Retrieve the default parameters for this route
	 *
	 * @deprecated
	 * @return array The default parameters for the route.. Null if no defaults specified.
	 */
	public function get_defaults()
	{
		if( isset($this->_data['defaults']) && is_array($this->_data['defaults']) ) return $this->_data['defaults'];
	}

	/**
	 * Test whether this route is for a page.
	 *
	 * @deprecated
	 * @return bool
	 */
	public function is_content()
	{
		return ($this->_data['key1'] == '__CONTENT__')?TRUE:FALSE;
	}

	/**
	 * Get matching parameter results.
	 *
	 * @deprecated
	 * @return array Matching parameters... or Null
	 */
	public function get_results()
	{
		return $this->_results;
	}

	/**
	 * Test if this route matches the specified string
	 * Depending upon the route, either a string comparison or regular expression match
	 * is performed.
	 *
	 * @param string $str The input string
	 * @param bool $exact Perform an exact string match rather than depending on the route values.
	 * @return bool
	 */
	public function matches($str,$exact = false)
	{
		$this->_results = null;
		if( (isset($this->_data['absolute']) && $this->_data['absolute']) || $exact ) {
			$a = trim($this->_data['term']);
			$a = trim($a,'/');
			$b = trim($str);
			$b = trim($b,'/');

			if( !strcasecmp($a,$b) ) return TRUE;
			return FALSE;
		}

		$tmp = [];
		$res = (bool)preg_match($this->_data['term'],$str,$tmp);
		if( $res && is_array($tmp) ) $this->_results = $tmp;
		return $res;
	}

} // class
