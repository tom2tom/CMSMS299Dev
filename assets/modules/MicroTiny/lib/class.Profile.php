<?php
/*
MicroTiny module class: Profile
Copyright (C) 2009-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of the Microtiny module for CMS Made Simple
<http://dev.cmsmadesimple.org/projects/microtiny>

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
namespace MicroTiny;

use ArrayAccess;
use CMSMS\DataException;
use CMSMS\Utils;
use LogicException;
use MicroTiny;
use RuntimeException;
use UnexpectedValueException;
use function cms_to_bool;

class Profile implements ArrayAccess
{
	const KEYS = [
		'allowcssoverride',
		'allowimages',
		'allowresize',
		'allowtables',
		'dfltstylesheet',
		'formats',
		'label',
		'menubar',
		'name',
		'showstatusbar',
		'system',
	];
	// static properties here >> Lone property|ies ?
	private static $_module = null;
	private $_data = [];

	public function __construct(/*array */$data = [])
	{
		if( $data ) {
			foreach( $data as $key => $value ) {
				$this->_data[$key] = $value;
			}
		}
	}

	#[\ReturnTypeWillChange]
	public function offsetGet($key)// : mixed
	{
		switch( $key ) {
		case 'menubar':
		case 'allowimages':
		case 'allowtables':
		case 'showstatusbar':
		case 'allowresize':
		case 'allowcssoverride':
		case 'system':
			if( isset($this->_data[$key]) ) return (bool)$this->_data[$key];

		case 'formats':
			if( isset($this->_data[$key]) ) return $this->_data[$key];

		case 'name':
		case 'dfltstylesheet':
			if( isset($this->_data[$key]) ) return trim($this->_data[$key]);

		case 'label':
			if( isset($this->_data[$key]) ) return $this->_data[$key];
			return $this['name'];

		default:
			throw new LogicException("'$key' is not a property of ".__CLASS__.' objects');
		}
	}

	public function offsetSet($key,$value) : void
	{
		switch( $key ) {
		case 'menubar':
		case 'allowtables':
		case 'allowimages':
		case 'showstatusbar':
		case 'allowresize':
		case 'allowcssoverride':
		case 'system':
			$this->_data[$key] = cms_to_bool($value);
			break;

		case 'formats':
			if( is_array($value) ) $this->_data[$key] = $value;
			break;

		case 'dfltstylesheet':
		case 'name':
		case 'label':
			$value = trim($value);
			if( $value ) $this->_data[$key] = $value;
			break;

		default:
			throw new LogicException("'$key' is not a property of ".__CLASS__.' objects');
		}
	}

	public function offsetExists($key) : bool
	{
		if( in_array($key, self::KEYS) ) return isset($this->_data[$key]);

		throw new LogicException("'$key' is not a property of ".__CLASS__.' objects');
	}

	public function offsetUnset($key) : void
	{
		switch( $key ) {
		case 'menubar':
		case 'allowtables':
		case 'allowimages':
		case 'showstatusbar':
		case 'allowresize':
		case 'allowcssoverride':
		case 'dfltstylesheet':
		case 'formats':
		case 'label':
			unset($this->_data[$key]);
			break;

		case 'system':
		case 'name':
			throw new LogicException("Cannot unset '$key' property of ".__CLASS__.' objects');

		default:
			throw new LogicException("'$key' is not a property of ".__CLASS__.' objects');
		}
	}

	public function save()
	{
		if( !isset($this->_data['name']) || $this->_data['name'] == '' ) {
			throw new DataException('No name provided for Microtiny profile');
		}

		$data = json_encode($this->_data,JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
		self::_get_module()->SetPreference('profile_'.$this->_data['name'],$data);
	}

	public function delete()
	{
		if( $this['name'] == '' ) return;
		self::_get_module()->RemovePreference('profile_'.$this['name']);
		unset($this->_data['name']);
	}

	private static function _load_from_data($data)
	{
		if( !$data || !is_array($data) ) { throw new LogicException('Invalid data provided to '.__METHOD__); }

		$obj = new self();
		foreach( $data as $key => $value ) {
			if( !in_array($key,self::KEYS) ) { throw new LogicException("$key is not a valid property of ".__CLASS__.' objects, in '.__FUNCTION__); }
			$obj->_data[$key] = trim($value);
		}
		return $obj;
	}

	public static function set_module(MicroTiny $module)
	{
		self::$_module = $module;
	}

	private static function _get_module()
	{
		if( !is_object(self::$_module) ) {
			self::$_module = Utils::get_module('MicroTiny');
			if( !is_object(self::$_module) ) {
				// module not yet installed - hack for installation
				self::$_module = new MicroTiny();
			}
		}
		return self::$_module;
	}

	/**
	 *
	 * @param string $name
	 * @return mixed self|null
	 * @throws UnexpectedValueException
	 */
	public static function load($name)
	{
		if( $name == '' ) return;
		$data = self::_get_module()->GetPreference('profile_'.$name);
		if( !$data ) throw new UnexpectedValueException('Unknown Microtiny profile '.$name);
		$props = json_decode($data,true);
		if( !$props ) throw new RuntimeException('Invalid data for Microtiny profile '.$name);

		$obj = new self();
		$obj->_data = $props;
		return $obj;
	}

	public static function list_all()
	{
		$prefix = 'profile_';
		return self::_get_module()->ListPreferencesByPrefix($prefix);
	}
} //class
