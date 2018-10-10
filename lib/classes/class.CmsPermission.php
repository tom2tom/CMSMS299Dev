<?php
#Class and utilities for working with permissions.
#Copyright (C) 2014-2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//namespace CMSMS;

/**
 * Simple class for dealing with a permission.
 *
 * @since 2.0
 * @package CMS
 * @license GPL
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 */
final class CmsPermission
{
	/**
	 * @ignore
	 */
	const KEYS = ['id','source','name','text','create_date','modified_date'];

	/**
	 * @ignore
	 */
	private $_data;

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
		'create_date' => '',
		'modified_date' => '',
        ];
	}

	/**
	 * @ignore
	 */
	public function __get($key)
	{
		if( !in_array($key,self::KEYS) ) throw new CmsInvalidDataException($key.' is not a valid key for a CmsPermission Object');
		return $this->_data[$key] ?? null;
	}

	/**
	 * @ignore
	 */
	public function __set($key,$value)
	{
		if( $key == 'id' ) throw new CmsInvalidDataException($key.' cannot be set this way in a CmsPermission Object');
		if( !in_array($key,self::KEYS) ) throw new CmsInvalidDataException($key.' is not a valid key for a CmsPermission Object');

		$this->_data[$key] = $value;
	}

	/**
	 * Insert a new permission
	 *
	 * @throws CmsSQLErrorException
	 */
	protected function _insert()
	{
		$this->validate();

		$db = CmsApp::get_instance()->GetDb();
		$new_id = $db->GenID(CMS_DB_PREFIX.'permissions_seq');
		if( !$new_id ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

		$now = $db->DbTimeStamp(time());
		$query = 'INSERT INTO '.CMS_DB_PREFIX."permissions
(permission_id,permission_name,permission_text,permission_source,create_date,modified_date)
VALUES (?,?,?,?,$now,$now)";
		$dbr = $db->Execute($query,
							[$new_id, $this->_data['name'], $this->_data['text'], $this->_data['source']]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$this->_data['id'] = $new_id;
	}

	/**
	 * Validate the exception
	 *
	 * @throws CmsInvalidDataException
	 * @throws CmsLogicException
	 */
	public function validate()
	{
		if( $this->_data['source'] == '' )
			throw new CmsInvalidDataException('Source cannot be empty in a CmsPermission object');
		if( $this->_data['name'] == '' )
			throw new CmsInvalidDataException('Name cannot be empty in a CmsPermission object');
		if( $this->_data['text'] == '' )
			throw new CmsInvalidDataException('Text cannot be empty in a CmsPermission object');

		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) {
			// Name must be unique
			$db = CmsApp::get_instance()->GetDb();
			$query = 'SElECT permission_id FROM '.CMS_DB_PREFIX.'permissions
                WHERE permission_name = ?';
			$dbr = $db->GetOne($query,[$this->_data['name']]);
			if( $dbr > 0 ) throw new CmsInvalidDataException('Permission with name '.$this->_data['name'].' already exists');
		}
	}

	/**
	 * Save the permission to the database
	 *
	 * @throws CmsLogicException
	 */
	public function save()
	{
		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) return $this->_insert();
		throw new CmsLogicException('Cannot update an existing CmsPermission object');
	}

	/**
	 * Delete this permission
	 *
	 * @throws CmsLogicExceptin
	 */
	public function delete()
	{
		if( !isset($this->_data['id']) || $this->_data['id'] < 1 ) {
			throw new CmsLogicException('Cannnot delete a CmsPermission object that has not been saved');
		}

		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms WHERE permission_id = ?';
		$dbr = $db->Execute($query,[$this->_data['id']]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'permissions WHERE permission_id = ?';
		$dbr = $db->Execute($query,[$this->_data['id']]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		unset($this->_data['id']);
	}

	/**
	 * Load a permission with the specified name
	 *
	 * @param string $name
	 * @return CmsPermission
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

		$db = CmsApp::get_instance()->GetDb();
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
			throw new CmsInvalidDataException('Could not find permission named '.$name);
		}

		$obj = new CmsPermission();
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
	 * Given a permission name, get it's id
	 *
	 * @param string $permname
	 * @return int
	 */
	public static function get_perm_id($permname)
	{
		try {
			$perm = self::load($permname);
			return $perm->id;
		}
		catch( CmsException $e ) {
		}
	}
} // class
