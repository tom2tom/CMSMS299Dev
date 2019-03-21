<?php
#Class to provide data and methods for encapsulating a language
#Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace CMSMS;
use function debug_display;

/**
 * A class to provide data and methods for encapsulating a single language
 *
 * @author Robert Campbell
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
	 * Test if this NLS object matches the passed in string
	 *
	 * Matches are achieved by checking name, isocode, fullname, and then aliases
	 *
	 * @param string $str The test string
	 * @return bool
	 */
	public function matches($str)
	{
		if( $str == $this->name() ) return TRUE;
		if( $str == $this->isocode() ) return TRUE;
		if( $str == $this->fullname() ) return TRUE;
		$aliases = $this->aliases();
		if( !is_array($aliases) ) $aliases = explode(',',$aliases);
		if( $aliases ) {
			foreach( $aliases as $alias ) {
				if( !strcasecmp($alias,$str) ) return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Return the name of this Nls object
	 * @return string
	 */
	public function name()
	{
		return $this->_key;
	}

	/**
	 * Return this isocode of this Nls object
	 * @return string
	 */
	public function isocode()
	{
		if( !$this->_isocode ) return substr($this->_fullname,0,2);
		return $this->_isocode;
	}

	/**
	 * Return the display string for this Nls object
	 * @return string
	 */
	public function display()
	{
	if( $this->_display ) return $this->_display;
	}

	/**
	 * Return the locale string for this Nls object
	 * @return string
	 */
	public function locale()
	{
		return $this->_locale;
	}

	/**
	 * Return the encoding for this Nls object (or UTF-8)
	 * @return string
	 */
	public function encoding()
	{
		if( !$this->_encoding ) return 'UTF-8';
		return $this->_encoding;
	}

	/**
	 * Return the full name of this Nls object
	 * @return string
	 */
	public function fullname()
	{
		if( $this->_fullname ) return $this->_fullname;
	}

	/**
	 * Return the aliases associated with this Nls object
	 * @return mixed array of aliases, or null
	 */
	public function aliases()
	{
		if( $this->_aliases ) {
			if( is_array($this->_aliases) ) return $this->_aliases;
			return explode(',',$this->_aliases);
		}
	}

	/**
	 * Return the key associated with this Nls object
	 * @return string
	 */
	public function key()
	{
		return $this->_key;
	}

	/**
	 * Return the direction of this Nls object (ltr or rtl)
	 * @return string
	 */
	public function direction()
	{
		if( $this->_direction ) return $this->_direction;
		return 'ltr';
	}

	/**
	 * Return the first two characters of the isocode for this Nls Object
	 * This is used typically for WYSIWYG text editors.
	 *
	 * @return string
	 */
	public function htmlarea()
	{
		if( $this->_htmlarea ) return $this->_htmlarea;
		return substr($this->_fullname,0,2);
	}

	/**
	 * Create an Nls object from a compatible array.
	 *
	 * @internal
	 * @ignore
	 * @param array $data
	 */
	public static function &from_array($data)
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
		if( isset($data['encoding'][$obj->_key]) ) $obj->_encoding = $data['encoding'][$obj->_key];

		if( isset($data['htmlarea'][$obj->_key]) ) $obj->_htmlarea = $data['htmlarea'][$obj->_key];

		// get the direction
		if( isset($data['direction'][$obj->_key]) ) $obj->_direction = $data['direction'][$obj->_key];

		// get aliases
		if( isset($data['alias']) ) $obj->_aliases= array_keys($data['alias']);

		if( $obj->_key == '' ) {
			debug_display($data);
			debug_display($obj); die();
		}
		return $obj;
	}
} // class