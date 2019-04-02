<?php
#Admin-group class for CMSMS
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

use CmsApp;
use CmsInvalidDataException;
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
 * @package CMS
 * @license GPL
 */
class Group
{
	/**
	 * @ignore
	 */
	const VALIDPROPS = ['id','name','description','active'];

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
	public function __get($key)
	{
		if( in_array($key,self::VALIDPROPS) ) return $this->$key;
		throw new LogicException($key.' is not a member of '.__CLASS__);
	}

	/**
	 * @ignore
	 */
	public function __set($key,$val)
	{
		if( !in_array($key,self::VALIDPROPS) ) {
			throw new LogicException($key.' is not a member of '.__CLASS__);
		}
		switch( $key ) {
		case 'id':
			if( $this->id != -1 ) {
				throw new LogicException($key.' is not a settable member of '.__CLASS__);
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
			self::$_operations = new GroupOperations();
		}
		return self::$_operations;
	}

	/**
	 * Validate the group.
	 *
	 * @throws LogicException
	 * @throws CmsInvalidDataException
	 */
	public function validate()
	{
		if( !$this->name ) throw new LogicException('No name specified for this group');
		$db = CmsApp::get_instance()->GetDb();
		$sql = 'SELECT group_id FROM '.CMS_DB_PREFIX.'groups WHERE group_name = ? AND group_id != ?';
		$dbresult = $db->GetOne($sql,[$this->name,$this->id]);
		if( $dbresult ) throw new CmsInvalidDataException(lang('errorgroupexists',$this->name));
	}

	/**
	 * Record the group in the database.
	 * @see GroupOperations::Upsert()
	 *
	 * @return bool indicating successful completion.
	 * @throws LogicException
	 * @throws CmsInvalidDataException
	 */
	public function Save()
	{
		$this->validate();
		self::get_operations()->Upsert($this);
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
		return self::get_operations()->DeleteGroupByID($this->id);
	}

	/**
	 * Get a populated Group-object representing the group with the specified id.
	 * @see GroupOperations::LoadGroupByID()
	 *
	 * @param int $id
	 * @return Group-object
	 * @throws CmsInvalidDataException
	 */
	public static function load($id)
	{
		return self::get_operations()->LoadGroupByID($id);
	}

	/**
	 * Get all groups.
	 * @see GroupOperations::LoadGroups()
	 *
	 * @return mixed array of populated Group-objects, or null.
	 */
	public static function load_all()
	{
		return self::get_operations()->LoadGroups();
	}

	/**
	 * Check if the group has the specified permission.
	 * @see GroupOperations::CheckPermission()
	 *
	 * @since 1.11
	 * @author Robert Campbell
	 * @internal
	 * @access private
	 * @ignore
	 * @param mixed $perm Either the permission id, or permission name to test.
	 * @return bool whether the group has the specified permission.
	 */
	public function HasPermission($perm)
	{
		if( $this->id <= 0 ) return false;
		if( $this->id == 1 ) return true;
		return self::get_operations()->CheckPermission($this->id,$perm);
	}

	/**
	 * Ensure this group has the specified permission.
	 * @see GroupOperations::GrantPermission()
	 *
	 * @since 1.11
	 * @author Robert Campbell
	 * @internal
	 * @ignore
	 * @param mixed $perm Either the permission id, or permission name to test.
	 * @return bool
	 */
	public function GrantPermission($perm)
	{
		if( $this->id < 1 ) return false;
		if( $this->HasPermission($perm) ) return false;
		return self::get_operations()->GrantPermission($this->id,$perm);
	}

	/**
	 * Ensure this group does not have the specified permission.
	 * @see GroupOperations::RemovePermission()
	 *
	 * @since 1.11
	 * @author Robert Campbell
	 * @internal
	 * @ignore
	 * @param mixed $perm Either the permission id, or permission name to test.
	 * @return bool
	 */
	public function RemovePermission($perm)
	{
		if( $this->id <= 0 ) return false;
		if( !$this->HasPermission($perm) ) return false;
		return self::get_operations()->RemovPermission($this->id,$perm);
	}
}

//backward-compatibility shiv
\class_alias(Group::class, 'Group', false);
