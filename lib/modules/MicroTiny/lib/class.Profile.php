<?php
#MicroTiny module class: Profile
#Copyright (C) 2009-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#This file is a component of the Microtiny module for CMS Made Simple
# <http://dev.cmsmadesimple.org/projects/microtiny>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace MicroTiny;

use ArrayAccess;
use cms_utils;
use CmsInvalidDataException;
use MicroTiny;
use function cms_to_bool;

class Profile implements ArrayAccess
{
	private static $_keys = [
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
	private static $_module = null;
	private $_data = [];

	public function __construct($data = [])
	{
		if( $data ) {
			foreach( $data as $key => $value ) {
				$this->_data[$key] = $value;
			}
		}
	}

	public function OffsetGet($key)
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
			break;

		case 'formats':
			if( isset($this->_data[$key]) ) return $this->_data[$key];
			break;

		case 'name':
		case 'dfltstylesheet':
			if( isset($this->_data[$key]) ) return trim($this->_data[$key]);
			break;

		case 'label':
			if( isset($this->_data[$key]) ) return $this->_data[$key];
			return $this['name'];

		default:
			throw new CmsInvalidDataException('invalid key '.$key.' for '.self::class.' object');
		}
	}

	public function OffsetSet($key,$value)
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
			throw new CmsInvalidDataException('invalid key '.$key.' for '.self::class.' object');
		}
	}

	public function OffsetExists($key)
	{
		if( in_array($key, self::$_keys) ) return isset($this->_data[$key]);

		throw new CmsInvalidDataException('invalid key '.$key.' for '.self::class.' object');
	}

	public function OffsetUnset($key)
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
			throw new LogicException('Cannot unset '.$key.' for '.self::class);

		default:
			throw new CmsInvalidDataException('invalid key '.$key.' for '.self::class.' object');
		}
	}

	public function save()
	{
		if( !isset($this->_data['name']) || $this->_data['name'] == '' ) {
			throw new CmsInvalidDataException('Invalid microtiny profile name');
		}

		$data = serialize($this->_data);
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
		if( !is_array($data) || !count($data) ) throw new CmsInvalidDataException('Invalid data passed to '.self::class.'::'.__METHOD__);

		$obj = new self();
		foreach( $data as $key => $value ) {
			if( !in_array($key,self::$_keys) ) throw new CmsInvalidDataException('Invalid key '.$key.' for data in .'.self::class);
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
			self::$_module = cms_utils::get_module('MicroTiny');
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
	 * @throws CmsInvalidDataException
	 */
	public static function load($name)
	{
		if( $name == '' ) return;
		$data = self::_get_module()->GetPreference('profile_'.$name);
		if( !$data ) throw new CmsInvalidDataException('Unknown microtiny profile '.$name);

		$obj = new self();
		$obj->_data = unserialize($data);
		return $obj;
	}

	public static function list_all()
	{
		$prefix = 'profile_';
		return self::_get_module()->ListPreferencesByPrefix($prefix);
	}
} //class
