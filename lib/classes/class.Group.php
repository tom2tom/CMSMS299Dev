<?php
/*
Admin-group class for CMSMS
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\GroupOperations;
use CMSMS\Lone;
use LogicException;
use const CMS_DB_PREFIX;
use function cms_to_bool;
use function lang;

/**
 * Generic group class.
 * This can be used for any logged-in group or group-related function.
 *
 * @property-read int $id The group id
 * @property string $name The group name
 * @property string $description The group description
 * @property bool $active Indicates active status of this group.
 * @since 0.9
 * @since 3.0 non-final status is deprecated
 * @package CMS
 * @license GPL
 */
/*final*/ class Group
{
	/**
	 * @ignore
	 */
	private const PROPS = ['id','name','description','active'];

	// static properties here >> Lone property|ies ?
	/**
	 * GroupOperations object populated on demand
	 * @ignore
	 */
	private static $_operations;

	/**
	 * Group properties
	 * @ignore
	 */
	private $id = -1;
	private $name = '';
	private $description = '';
	private $active = false;

	/**
	 * @ignore
	 */
	#[\ReturnTypeWillChange]
	public function __get(string $key)//: mixed
	{
		if( in_array($key,self::PROPS) ) return $this->$key;
		throw new LogicException($key.' is not a property of '.__CLASS__);
	}

	/**
	 * @ignore
	 */
	public function __set(string $key,$val): void
	{
		if( !in_array($key,self::PROPS) ) {
			throw new LogicException($key.' is not a property of '.__CLASS__);
		}
		switch( $key ) {
		case 'id':
			if( $this->id != -1 ) {
				throw new LogicException($key.' is not a settable property of '.__CLASS__);
			}
			$val = (int)$val;
			break;

		case 'name':
		case 'description':
			$val = trim($val);
			break;

		case 'active':
			$val = cms_to_bool($val);
			break;
		}
		$this->$key = $val;
	}

	/**
	 * @ignore
	 */
	private static function get_operations()
	{
		if( empty(self::$_operations) ) {
			self::$_operations = Lone::get('GroupOperations');
		}
		return self::$_operations;
	}

	/**
	 * Validate the group.
	 *
	 * @throws LogicException
	 * @throws DataException
	 */
	public function validate()
	{
		if( !$this->name ) {
			throw new LogicException('No name specified for this group');
		}
		$db = Lone::get('Db');
		$sql = 'SELECT group_id FROM `'.CMS_DB_PREFIX.'groups` WHERE group_name = ? AND group_id != ?';
		$dbr = $db->getOne($sql,[$this->name,$this->id]);
		if( $dbr ) {
			throw new LogicException(lang('errorgroupexists',$this->name));
		}
	}

	/**
	 * Record the group in the database.
	 * @see GroupOperations::Upsert()
	 *
	 * @return bool indicating successful completion.
	 * @throws LogicException
	 * @throws DataException
	 */
	public function Save()
	{
		$this->validate();
		$res = self::get_operations()->Upsert($this);
		if( $this->id < 0 ) {
			$this->id = $res;
		}
// TODO Lone::get('LoadedData')->delete('menu_modules'); for all users in group, if not installing
	}

	/**
	 * Delete the group from the database.
	 * @see GroupOperations::DeleteGroupByID()
	 *
	 * @throws LogicException if the group is the default (gid 1)
	 * @return bool whether the delete was successful.
	 */
	public function Delete()
	{
// TODO Lone::get('LoadedData')->delete('menu_modules'); for all users in group, if not installing
		return self::get_operations()->DeleteGroupByID($this->id);
	}

	/**
	 * Get a populated Group-object representing the group with the specified id.
	 * @see GroupOperations::LoadGroupByID()
	 *
	 * @param int $id
	 * @return Group-object
	 * @throws DataException
	 */
	public static function load($id)
	{
		return self::get_operations()->LoadGroupByID($id);
	}

	/**
	 * Get all groups (whether active or not).
	 * @see GroupOperations::LoadGroups()
	 *
	 * @return array Group-object(s) | empty
	 */
	public static function load_all()
	{
		return self::get_operations()->LoadGroups();
	}

	/**
	 * Check whether this group has the specified permission.
	 * @see GroupOperations::CheckPermission()
	 *
	 * @since 1.11
	 * @internal
	 * @access private
	 * @ignore
	 * @param mixed $perm Permission name string, or (since 3.0) array of those, to test.
	 * (Previous documentation referring to a numeric permission-id was incorrect)
	 * @return bool whether the group has the specified permission.
	 */
	public function HasPermission($perm)
	{
		if( $this->id < 1 ) { return false; }
		return self::get_operations()->CheckPermission($this->id,$perm);
	}

	/**
	 * Ensure this group has the specified permission.
	 * @see GroupOperations::GrantPermission()
	 *
	 * @since 1.11
	 * @internal
	 * @ignore
	 * @param string $perm Name of the permission to grant (previous
	 *  documentation about a numeric permission-id was incorrect)
	 * @return bool, false if current user_id is invalid, or the named
	 *  permission is already granted
	 */
	public function GrantPermission($perm)
	{
		if( $this->id < 1 ) { return false; }
//		if( is_numeric($perm) ) { $perm = $TODO; }
		if( $this->HasPermission($perm) ) { return false; }
		return self::get_operations()->GrantPermission($this->id,$perm);
// TODO Lone::get('LoadedData')->delete('menu_modules'); for all users in group, if not installing
	}

	/**
	 * Ensure this group does not have the specified permission.
	 * @see GroupOperations::RemovePermission()
	 *
	 * @since 1.11
	 * @internal
	 * @ignore
	 * @param string $perm Name of the permission to remove (Previous documentation
	 *  about a numeric permission-id was incorrect)
	 * @return bool, false if current user_id is invalid, or the named permission
	 *  is not already granted
	 */
	public function RemovePermission($perm)
	{
		if( $this->id < 1 ) { return false; }
//		if( is_numeric($perm) ) { $perm = $TODO; }
		if( !$this->HasPermission($perm) ) { return false; }
		return self::get_operations()->RemovPermission($this->id,$perm);
// TODO Lone::get('LoadedData')->delete('menu_modules'); for all users in group, if not installing
	}
}

//backward-compatibility shiv
\class_alias(Group::class, 'Group', false);
