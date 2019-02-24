<?php
#admin-group class for CMSMS
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
 * Generic group class. This can be used for any logged in group or group related function.
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
	private $_data = ['id'=>-1,'name'=>null,'description'=>null,'active'=>false];

	/**
	 * @ignore
	 */
	public function __get($key)
	{
		if( isset($this->_data[$key]) ) return $this->_data[$key];
		throw new LogicException($key.' is not a member of '.__CLASS__);
	}

	/**
	 * @ignore
	 */
	public function __set($key,$val)
	{
		switch( $key ) {
		case 'id':
			throw new LogicException($key.' is not a settable member of '.__CLASS__);
			break;

		case 'name':
		case 'description':
			$this->_data[$key] = trim($val);
			break;

		case 'active':
			$this->_data[$key] = cms_to_bool($val);
			break;

		default:
			throw new LogicException($key.' is not a member of '.__CLASS__);
		}
	}

	/**
	 * Validate this object.
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
	 * @ignore
	 */
	protected function update()
	{
		$db = CmsApp::get_instance()->GetDb();
		$sql = 'UPDATE '.CMS_DB_PREFIX.'groups SET group_name = ?, group_desc = ?, active = ?, modified_date = NOW() WHERE group_id = ?';
//		$dbresult =
		$db->Execute($sql, [$this->name,$this->description,$this->active,$this->id]);
//		return $dbresult != FALSE; useless - post-update result on MySQL is always unreliable
		return TRUE;
	}

	/**
	 * @ignore
	 */
	protected function insert()
	{
		$db = CmsApp::get_instance()->GetDb();
		$this->_data['id'] = $db->GenID(CMS_DB_PREFIX.'groups_seq');
		$time = $db->DbTimeStamp(time());
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'groups (group_id, group_name, group_desc, active, create_date, modified_date)
VALUES (?,?,?,?,'.$time.', '.$time.')';
		$dbresult = $db->Execute($query, [$this->id, $this->name, $this->description, $this->active]);
		return $dbresult != FALSE;
	}

	/**
	 * Persists the group to the database.
	 *
	 * @return bool true if the save was successful, false if not.
	 */
	public function Save()
	{
		$this->validate();
		if( $this->id > 0 ) {
			return $this->update();
		} else {
			return $this->insert();
		}
	}

	/**
	 * Deletes the group from the database
	 *
	 * @throws LogicException
	 * @return bool whether the delete was successful.
	 */
	public function Delete()
	{
		$db = CmsApp::get_instance()->GetDb();
		if( $this->$_data['id'] < 1 ) {
			if( $this->$_data['name'] ) {
				$query = 'SELECT group_id FROM '.CMS_DB_PREFIX.'user_groups where group_name = ?';
				$this->id = (int) $db->GetOne($query, [$this->name]);
				if( $this->id <= 0 ) return FALSE;
			} else {
				return FALSE;
			}
		}
		if( $this->id == 1 ) throw new LogicException(lang('error_deletespecialgroup'));
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'user_groups where group_id = ?';
		$db->Execute($query, [$this->id]);
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms where group_id = ?';
		$db->Execute($query, [$this->id]);
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'groups where group_id = ?';
		$db->Execute($query, [$this->id]);
		$this->_data['id'] = -1;
		return TRUE;
	}

	/**
	 * Get a populated Group-object representing the group with the specified id.
	 *
	 * @param int $id
	 * @return Group-object
	 * @throws CmsInvalidDataException
	 */
	public static function load($id)
	{
		$id = (int) $id;
		if( $id < 1 ) throw new CmsInvalidDataException(lang('missingparams'));

		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT group_id, group_name, group_desc, active FROM '.CMS_DB_PREFIX.'groups WHERE group_id = ? ORDER BY group_id';
		$row = $db->GetRow($query, [$id]);

		$obj = new self();
		$obj->_data['id'] = $row['group_id'];
		$obj->name = $row['group_name'];
		$obj->description = $row['group_desc'];
		$obj->active = $row['active'];
		return $obj;
	}

	/**
	 * Load all groups
	 *
	 * @return mixed Array of populated Group-objects or null.
	 */
	public static function load_all()
	{
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT group_id, group_name, group_desc, active FROM '.CMS_DB_PREFIX.'groups ORDER BY group_id';
		$list = $db->GetArray($query);
		$out = [];
		for( $i = 0, $n = count($list); $i < $n; ++$i ) {
			$row = $list[$i];
			$obj = new self();
			$obj->_data['id'] = (int) $row['group_id'];
			$obj->name = $row['group_name'];
			$obj->description = $row['group_desc'];
			$obj->active = (int) $row['active'];
			$out[] = $obj;
		}
		if( $n ) return $out;
	}

	/**
	 * Check if the group has the specified permission.
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
		if( $this->id <= 0 ) return FALSE;
		$groupops = GroupOperations::get_instance();
		return $groupops->CheckPermission($this->id,$perm);
	}

	/**
	 * Ensure this group has the specified permission.
	 *
	 * @since 1.11
	 * @author Robert Campbell
	 * @internal
	 * @access private
	 * @ignore
	 * @param mixed $perm Either the permission id, or permission name to test.
	 */
	public function GrantPermission($perm)
	{
		if( $this->id < 1 ) return;
		if( $this->HasPermission($perm) ) return;
		$groupops = GroupOperations::get_instance();
		return $groupops->GrantPermission($this->id,$perm);
	}

	/**
	 * Ensure this group does not have the specified permission.
	 *
	 * @since 1.11
	 * @author Robert Campbell
	 * @internal
	 * @access private
	 * @ignore
	 * @param mixed $perm Either the permission id, or permission name to test.
	 */
	public function RemovePermission($perm)
	{
		if( $this->id <= 0 ) return;
		if( !$this->HasPermission($perm) ) return;
		$groupops = GroupOperations::get_instance();
		return $groupops->RemovPermission($this->id,$perm);
	}
}

//backward-compatibility shiv
\class_alias(Group::class, 'Group', false);
