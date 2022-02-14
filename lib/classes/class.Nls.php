<?php
/*
Class to provide data and methods for encapsulating a language
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use function debug_display;

/**
 * A class to provide data and methods for encapsulating a language
 *
 * @since 1.11
 * @package CMS
 * @license GPL
 */
class Nls
{
	/**
	 * @ignore
	 */
	protected $_isocode;

	/**
	 * @ignore
	 */
	protected $_locale;

	/**
	 * @ignore
	 */
	protected $_fullname;

	/**
	 * @ignore
	 */
	protected $_encoding;

	/**
	 * @ignore
	 */
	protected $_aliases;

	/**
	 * @ignore
	 */
	protected $_display;

	/**
	 * @ignore
	 */
	protected $_key;

	/**
	 * @ignore
	 */
	protected $_direction;

	/**
	 * @ignore
	 */
	protected $_htmlarea;

	/**
	 * Check whether this object matches the passed-in string
	 *
	 * Matches are achieved by checking name, fullname, and each alias
	 *
	 * @param string $str The test string
	 * @return bool indicating match
	 */
	public function matches($str)
	{
		if( $str == $this->name() ) return true;
//		if( $str == $this->isocode() ) return true; UNHELPFUL
		if( $str == $this->fullname() ) return true;
		$aliases = $this->aliases();
		if( is_string($aliases) ) { $aliases = explode(',', $aliases); }
		if( $aliases ) {
			foreach( $aliases as $alias ) {
				if( strcasecmp($alias, $str) == 0 ) {
					return true;
				}
			}
		}
		return false;
	}

	/**
	 * Return the name of this object
	 * @return string
	 */
	public function name()
	{
		if( !empty($this->_key) ) return $this->_key;
		return '';
	}

	/**
	 * Return this isocode of this object
	 * @return string
	 */
	public function isocode()
	{
		if( !empty($this->_isocode) ) return $this->_isocode;
		return substr($this->_fullname, 0, 2);
	}

	/**
	 * Return the display string for this object
	 * @return string
	 */
	public function display()
	{
		if( !empty($this->_display) ) return $this->_display;
		return '';
	}

	/**
	 * Return the locale string for this object
	 * @return string
	 */
	public function locale()
	{
		if( !empty($this->_locale) ) return $this->_locale;
		return '';
	}

	/**
	 * Return the encoding for this object (default UTF-8)
	 * @return string
	 */
	public function encoding()
	{
		if( !empty($this->_encoding) ) return $this->_encoding;
		return 'UTF-8';
	}

	/**
	 * Return the full name of this object
	 * @return string
	 */
	public function fullname()
	{
		if( !empty($this->_fullname) ) return $this->_fullname;
		return '';
	}

	/**
	 * Return the aliases associated with this object
	 * @return mixed array of aliases (maybe empty) or comma-separated string (maybe empty)
	 */
	public function aliases()
	{
		if( !empty($this->_aliases) ) {
			if( is_array($this->_aliases) ) return $this->_aliases;
			return explode(',',$this->_aliases);
		}
		return [];
	}

	/**
	 * Return the key associated with this object
	 * @return string
	 */
	public function key()
	{
		if( !empty($this->_key) ) return $this->_key;
		return '';
	}

	/**
	 * Return the direction of this object (ltr or rtl)
	 * @return string
	 */
	public function direction()
	{
		if( !empty($this->_direction) ) return $this->_direction;
		return 'ltr';
	}

	/**
	 * Return the first two characters of the isocode for this object
	 * This is used typically for WYSIWYG text editors.
	 *
	 * @return string
	 */
	public function htmlarea()
	{
		if( !empty($this->_htmlarea) ) return $this->_htmlarea;
		return substr($this->_fullname, 0, 2);
	}

	/**
	 * Create an Nls object from a compatible array.
	 *
	 * @internal
	 * @ignore
	 * @param array $data
	 */
	public static function from_array($data)
	{
		$obj = new self();

		// name and key
		if( isset($data['englishlang']) ) {
			foreach( $data['englishlang'] as $k => $v ) {
				$obj->_fullname = $v;
				$obj->_key = $k;
				break;
			}
		}

		// get the display value
		if( isset($data['language'][$obj->_key]) ) $obj->_display = $data['language'][$obj->_key];

		// get the isocode?
		if( isset($data['isocode'][$obj->_key]) ) {
			$obj->_isocode = $data['isocode'][$obj->_key];
		}
		else {
			$t = explode('_',$obj->_key);
			if( $t ) $obj->_isocode = $t[0];
		}

		// get the locale
		if( isset($data['locale'][$obj->_key]) ) $obj->_locale = $data['locale'][$obj->_key];

		// get the encoding
		if( isset($data['encoding'][$obj->_key]) ) $obj->_encoding = strtoupper($data['encoding'][$obj->_key]);

		if( isset($data['htmlarea'][$obj->_key]) ) $obj->_htmlarea = $data['htmlarea'][$obj->_key];

		// get the direction
		if( isset($data['direction'][$obj->_key]) ) $obj->_direction = strtolower($data['direction'][$obj->_key]);

		// get aliases
		if( isset($data['alias']) ) $obj->_aliases= array_keys($data['alias']);

		if( $obj->_key == '' ) {
			debug_display($data);
			debug_display($obj);
			exit;
		}
		return $obj;
	}
} // class
