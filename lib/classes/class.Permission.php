<?php
/*
Class and utilities for working with permissions.
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSMS\SQLErrorException;
use LogicException;
use Throwable;
use UnexpectedValueException;
use const CMS_DB_PREFIX;

/**
 * Simple class for dealing with a permission.
 *
 * @since 2.99
 * @since 2.0 as global-namespace CmsPermission
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 */
final class Permission
{
	/**
	 * @ignore
	 */
	private const PROPS = ['id','source','name','text','create_date','modified_date'];

	/**
	 * @ignore
	 */
	private $_data;

    // static properties here >> StaticProperties class ?
	/**
	 * @ignore
	 */
	private static $_perm_map;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->_data = [
		'id' => 0,
		'source' => '',
		'name' => '',
		'text' => '',
		'create_date' => '', // database DT-field value
		'modified_date' => '', // ditto
        ];
	}

	/**
	 * @ignore
	 * @throws UnexpectedValueException
	 * @return mixed recorded value | null
	 */
	public function __get($key)
	{
		if( !in_array($key,self::PROPS) ) throw new UnexpectedValueException($key.' is not a valid key for a '.__CLASS__.' object');
		return $this->_data[$key] ?? null;
	}

	/**
	 * @ignore
	 * @throws LogicException or UnexpectedValueException
	 */
	public function __set($key,$value)
	{
		if( $key == 'id' ) throw new LogicException($key.' cannot be set this way in a '.__CLASS__.' object');
		if( !in_array($key,self::PROPS) ) throw new UnexpectedValueException($key.' is not a valid key for a '.__CLASS__.' object');

		$this->_data[$key] = $value;
	}

	/**
	 * Insert a new permission
	 *
	 * @throws SQLErrorException if saving fails
	 */
	protected function _insert()
	{
		$this->validate();

		$db = AppSingle::Db();

		//setting create_date should be redundant with DT setting
		$query = 'INSERT INTO '.CMS_DB_PREFIX."permissions
(permission_name,permission_text,permission_source,create_date)
VALUES (?,?,?,NOW())";
		$dbr = $db->Execute($query,
		[$this->_data['name'], $this->_data['text'], $this->_data['source']]);
		if( $dbr ) {
			$this->_data['id'] = $db->Insert_ID();
		}
		else {
			throw new SQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		}
	}

	/**
	 * Validate the permission properties: source, name, text
	 *
	 * @throws LogicException
	 */
	public function validate()
	{
		if( $this->_data['source'] == '' )
			throw new LogicException('Source cannot be empty in a '.__CLASS__.' object');
		if( $this->_data['name'] == '' )
			throw new LogicException('Name cannot be empty in a '.__CLASS__.' object');
		if( $this->_data['text'] == '' )
			throw new LogicException('Text cannot be empty in a '.__CLASS__.' object');

		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) {
			// Name must be unique
			$db = AppSingle::Db();
			$query = 'SElECT permission_id FROM '.CMS_DB_PREFIX.'permissions
 WHERE permission_name = ?';
			$dbr = $db->GetOne($query,[$this->_data['name']]);
			if( $dbr > 0 ) throw new LogicException('A permission with name '.$this->_data['name'].' already exists');
		}
	}

	/**
	 * Save the permission to the database
	 *
	 * @throws LogicException
	 */
	public function save()
	{
		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) return $this->_insert();
		throw new LogicException('Cannot update an existing '.__CLASS__.' object');
	}

	/**
	 * Delete this permission
	 *
	 * @throws LogicException
	 * @throws SQLErrorException
	 */
	public function delete()
	{
		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) {
			throw new LogicException('Cannnot delete a '.__CLASS__.' object that has not been saved');
		}

		$db = AppSingle::Db();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms WHERE permission_id = ?';
		$dbr = $db->Execute($query,[$this->_data['id']]);
		if( !$dbr ) throw new SQLErrorException($db->sql.' -- '.$db->ErrorMsg());

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'permissions WHERE permission_id = ?';
		$dbr = $db->Execute($query,[$this->_data['id']]);
		if( !$dbr ) throw new SQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		unset($this->_data['id']);
	}

	/**
	 * Load a permission with the specified name
	 *
	 * @param string $name
	 * @return Permission
	 * @throws UnexpectedValueException
	 */
	public static function load($name)
	{
		if( is_array(self::$_perm_map) ) {
			if( (int)$name <= 0 ) {
				foreach( self::$_perm_map as $perm_id => $perm ) {
					if( $perm->name == $name ) return $perm;
				}
			}
		}

		$db = AppSingle::Db();
		$row = null;
		if( (int)$name > 0 ) {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'permissions WHERE permission_id = ?';
			$row = $db->GetRow($query,[(int)$name]);
		}
		else {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.'permissions WHERE permission_name = ?';
			$row = $db->GetRow($query,[$name]);
		}
		if( !$row ) {
			throw new UnexpectedValueException('Could not find permission named '.$name);
		}

		$obj = new self();
		$obj->_data['id'] = $row['permission_id'];
		$obj->_data['name'] = $row['permission_name'];
		$obj->_data['text'] = $row['permission_text'];
		$obj->_data['create_date'] = $row['create_date'];
		$obj->_data['modified_date'] = $row['modified_date'];

		if( !is_array(self::$_perm_map) ) self::$_perm_map = [];
		self::$_perm_map[$obj->id] = $obj;
		return $obj;
	}

	/**
	 * Get the id of a named permission, if possible
	 *
	 * @param string $permname
	 * @return mixed int|null
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
	 * @since 2.99
	 * @param muixed $permid int | numeric string
	 * @return mixed string|null
	 */
	public static function get_perm_name($permid)
	{
		try {
			$perm = TODOfunc((int)$permid);
			return $perm->name;
		}
		catch( Throwable $t ) {
			//nothing here
		}
	}
} // class
