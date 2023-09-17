<?php
/*
Class and utilities for working with permissions.
Copyright (C) 2014-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Lone;
use CMSMS\Permission;
use CMSMS\SQLException;
use LogicException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use const CMS_DB_PREFIX;

/**
 * Simple class for dealing with a permission.
 *
 * @since 3.0
 * @since 2.0 as global-namespace CmsPermission
 * @package CMS
 * @license GPL
 */
final class Permission
{
	/**
	 * Value to use for the 'originator' of core-permissions
	 * @since 3.0
	 */
	const CORE = '__CORE__';

	/**
	 * @ignore
	 */
	private const PROPS = ['id','name','desc','originator','create_date','modified_date'];
	/**
	 * @ignore
	 * Property-name aliases, some of them deprecated since 3.0
	 */
	private const ALIASPROPS = ['description' => 'desc','text' => 'desc','source' => 'originator'];

	/**
	 * @ignore
	 * This object's properties
	 */
	private $_data;

	// static properties here >> Lone property|ies ?
	/**
	 * @ignore
	 * Intra-request cache of loaded permission-objects
	 *  Each member like id => object
	 */
	private static $_cache;

	/**
	 * Constructor
	 * @param mixed $props array | null Optional permission-properties Since 3.0
	 */
	public function __construct($props = [])
	{
		$this->_data = [
			'id' => 0,
			'name' => '',
			'desc' => NULL, // aka unset
			'originator' => NULL,
			'create_date' => NULL,
			'modified_date' => NULL,
		];
		if( $props && is_array($props) ) {
			$keeps = array_intersect_key($props, $this->_data);
			$this->_data = array_merge($this->_data, $keeps);
			foreach( self::ALIASPROPS as $alt => $key ) {
				if( isset($props[$alt]) ) {
					$this->_data[$key] = $props[$alt];
				}
			}
		}
	}

	/**
	 * @ignore
	 * @throws UnexpectedValueException
	 * @return mixed recorded value | null
	 */
	#[\ReturnTypeWillChange]
	public function __get(string $key)//: mixed
	{
		if( !in_array($key,self::PROPS) ) {
			//try for a deprecated alias
			if( isset(self::ALIASPROPS[$key]) ) {
				$key = self::ALIASPROPS[$key];
			} else {
				throw new LogicException($key.' is not a property of '.__CLASS__.' objects');
			}
		}
		return $this->_data[$key] ?? null;
	}

	/**
	 * @ignore
	 * @throws LogicException or UnexpectedValueException
	 */
	public function __set(string $key,$value): void
	{
		if( $key == 'id' ) {
			throw new LogicException($key.' cannot be set this way in '.__CLASS__.' objects');
		}
		if( !in_array($key,self::PROPS) ) {
			if( isset(self::ALIASPROPS[$key]) ) {
				$key = self::ALIASPROPS[$key];
			} else {
				throw new LogicException($key.' is not a property of '.__CLASS__.' objects');
			}
		}
		$this->_data[$key] = $value;
	}

	/**
	 * Record a new permission
	 *
	 * @throws SQLException if saving fails
	 */
	private function _insert()
	{
		if (empty($this->_data['originator'])) { $this->_data['originator'] = self::CORE; }
		if (empty($this->_data['desc'])) { $this->_data['desc'] = null; } // record null in db

		$this->validate();

		$db = Lone::get('Db');
		//setting create_date should be redundant with DT default setting, but timezone ?
		$longnow = $db->DbTimeStamp(time(), false);
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'permissions
(`name`,description,originator,create_date) VALUES (?,?,?,?)';
		$dbr = $db->execute($query,
			[$this->_data['name'], $this->_data['desc'], $this->_data['originator'], $longnow]);
		if( $dbr ) {
			$this->_data['id'] = $db->Insert_ID(); // == $dbr
		}
		else {
			throw new SQLException($db->sql.' -- '.$db->errorMsg());
		}
	}

	/**
	 * Validate some permission properties: name, originator
	 * @since 3.0 description may be empty
	 *
	 * @throws LogicException if validation fails
	 */
	public function validate()
	{
		if( $this->_data['name'] == '' ) {
			throw new LogicException('Name cannot be empty in a '.__CLASS__.' object');
		}
		if( $this->_data['originator'] == '' ) {
			throw new LogicException('Originator cannot be empty in a '.__CLASS__.' object');
		}
		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) {
			// Name must be unique for its originator
			$db = Lone::get('Db');
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.'permissions WHERE `name`=? AND originator=?';
			$dbr = $db->getOne($query, [$this->_data['name'], $this->_data['originator']]);
			if( $dbr > 0 ) {
				throw new LogicException('A permission with name '.$this->_data['name'].' already exists');
			}
		}
	}

	/**
	 * Save this permission to the database
	 *
	 * @throws LogicException
	 */
	public function save()
	{
		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) {
			$this->_insert();
		}
		throw new LogicException('Cannot update an existing '.__CLASS__.' object');
	}

	/**
	 * Delete this permission
	 *
	 * @throws LogicException
	 * @throws SQLException
	 */
	public function delete()
	{
		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) {
			throw new LogicException('Cannnot delete a '.__CLASS__.' object that has not been saved');
		}

		$db = Lone::get('Db');
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms WHERE permission_id=?';
//		$dbr =
		$db->execute($query,[$this->_data['id']]);

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'permissions WHERE id=?';
		$dbr = $db->execute($query,[$this->_data['id']]);
		if( !$dbr ) throw new SQLException($db->sql.' -- '.$db->errorMsg());
		if( is_array(self::$_cache) ) {
			unset(self::$_cache[$this->_data['id']]);
		}
		$this->_data['id'] = 0;
	}

	/**
	 * Load a permission with the specified identifier
	 *
	 * @param mixed $a string name | int id
	 * @since 3.0 the name may be like 'originator::name' or just 'name'
	 * @return Permission
	 * @throws RuntimeException if nil or > 1 permissions match
	 */
	public static function load($a)
	{
		if( is_array(self::$_cache) ) {
			if( is_numeric($a) ) {
				$a = (int)$a;
				if( isset(self::$_cache[$a]) ) {
					return self::$_cache[$a];
				}
			}
			elseif( strpos($a,'::') !== false ) {
				$parts = explode('::',$a,2);
				$parts = array_map('trim',$parts);
				if( !$parts[0] || strcasecmp($parts[0],'core') == 0 ) { $parts[0] = self::CORE; }
				foreach( self::$_cache as $perm_id => $perm ) {
					if( $perm->name == $parts[1] && $perm->originator == $parts[0] ) return $perm;
				}
			}
			else {
				$out = [];
				foreach( self::$_cache as $perm_id => $perm ) {
					if( $perm->name == $a ) { $out[] = $perm; }
				}
				switch (count($out)) {
					case 0:
						break;
					case 1:
						return reset($out);
					default:
						throw new RuntimeException("Multiple permissions match '$a'");
				}
			}
		}

		$db = Lone::get('Db');
		if( is_numeric($a) ) {
			if( $a > 0 ) {
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.'permissions WHERE id=?';
				$row = $db->getRow($query,[(int)$a]);
			}
		}
		elseif( strpos($a,'::') !== false ) {
			$parts = explode('::',$a,2);
			$parts = array_map('trim',$parts);
			if( !$parts[0] || strcasecmp($parts[0],'core') == 0 ) { $parts[0] = self::CORE; }
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'permissions WHERE originator=? AND `name`=?';
			$row = $db->getRow($query,$parts);
		}
		else {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'permissions WHERE `name`=?';
			$all = $db->getArray($query,[$a]);
			if( $all ) {
				if( count($all) == 1 ) {
					$row = $all[0];
				}
				else {
					throw new RuntimeException("Multiple permissions match '$a'");
				}
			}
			else {
				$row = false;
			}
		}
		if( !$row ) {
			throw new RuntimeException('Could not find permission identified by '.$a);
		}

		$row['desc'] = $row['description'];
		$obj = new self($row);

		$id = $obj->id;
		if ($id > 0) {
			if( !is_array(self::$_cache) ) {
				self::$_cache = [];
			}
			self::$_cache[$id] = $obj;
		}
		return $obj;
	}

	/**
	 * Get the id of a named permission, if possible
	 *
	 * @param string $permname since 3.0 like 'originator::name' or just 'name'
	 * @return mixed int | null
	 */
	public static function get_perm_id($permname)
	{
		try {
			$perm = self::load($permname);
			return $perm->id;
		}
		catch( Throwable $t ) {
			//nothing here
		}
	}

	/**
	 * Get the name of a numbered permission, if possible
	 * @since 3.0
	 * @param muixed $permid int | numeric string
	 * @return mixed string | null
	 */
	public static function get_perm_name($permid)
	{
		try {
			$perm = self::load($permid);
			return $perm->name;
		}
		catch( Throwable $t ) {
			//nothing here
		}
	}
} // class
