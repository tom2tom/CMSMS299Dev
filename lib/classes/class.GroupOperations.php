<?php
#Class of group-related functions
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
use CMSMS\AppSingle;
use CMSMS\Group;
use CmsPermission;
use DeprecationNotice;
use LogicException;
use const CMS_DB_PREFIX;
use const CMS_DEPREC;
use function lang;
use function restricted_cms_permissions;

/**
 * A singleton class of methods for performing group-related functions.
 * Many of the Group-class methods are just wrappers around these.
 *
 * @since 0.6
 * @final
 * @package CMS
 * @license GPL
 */
final class GroupOperations
{
	/* *
	 * @ignore
	 */
//	private static $_instance = null;

	/**
	 * @var array
	 * @ignore
	 */
	private $_perm_cache;

	/* *
	 * @ignore
	 */
//	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Get the singleton instance of this class.
	 * @deprecated since 2.3 instead use CMSMS\AppSingle::GroupOperations()
	 * @return GroupOperations
	 */
	public static function get_instance() : self
	{
        assert(empty(CMS_DEPREC), new DeprecationNotice('method','CMSMS\AppSingle::GroupOperations()'));
		return AppSingle::GroupOperations();
	}

	/**
	 * Loads all the groups from the database and returns them
	 *
	 * @return mixed array The list of Group-objects | null
	 */
	public function LoadGroups()
	{
		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT group_id, group_name, group_desc, active FROM '.CMS_DB_PREFIX.'groups ORDER BY group_id';
		$list = $db->GetArray($query);
		$out = [];
		for( $i = 0, $n = count($list); $i < $n; ++$i ) {
			$row = $list[$i];
			$obj = new Group();
			$obj->id = (int) $row['group_id'];
			$obj->name = $row['group_name'];
			$obj->description = $row['group_desc'];
			$obj->active = (int) $row['active'];
			$out[] = $obj;
		}
		if( $out ) return $out;
	}

	/**
	 * Load a group from the database by its id
	 *
	 * @param mixed  int|null $id The id of the group to load
	 * @return mixed The group if found. If it's not found, then false
	 */
	public function LoadGroupByID($id)
	{
		$id = (int) $id;
		if( $id < 1 ) throw new CmsInvalidDataException(lang('missingparams'));

		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT group_id, group_name, group_desc, active FROM '.CMS_DB_PREFIX.'groups WHERE group_id = ? ORDER BY group_id';
		$row = $db->GetRow($query, [$id]);

		$obj = new Group();
		$obj->id = $row['group_id'];
		$obj->name = $row['group_name'];
		$obj->description = $row['group_desc'];
		$obj->active = $row['active'];
		return $obj;
	}

	/**
	 * @since 2.3
	 * @return mixed int | bool
	 */
	public function Upsert(Group $group)
	{
		$db = CmsApp::get_instance()->GetDb();
		$id = $group->id;
		if( $id < 1 ) {
			$id = $db->GenID(CMS_DB_PREFIX.'groups_seq');
			$now = $db->DbTimeStamp(time());
			$query = 'INSERT INTO '.CMS_DB_PREFIX."groups
(group_id, group_name, group_desc, active, create_date, modified_date)
VALUES ($id,?,?,?,$now,$now)";
			$dbr = $db->Execute($query, [$group->name, $group->description, $group->active]);
			return ($dbr != FALSE) ? $id : -1;
		}
		else {
			$query = 'UPDATE '.CMS_DB_PREFIX.'groups SET group_name = ?, group_desc = ?, active = ?, modified_date = NOW() WHERE group_id = ?';
//			$dbr =
			$db->Execute($query, [$group->name,$group->description,$group->active,$id]);
//			return $dbr != FALSE; useless - post-update result on MySQL is always unreliable
			return TRUE;
		}
	}

	/**
	 * Given a group object, inserts it into the database.
	 *
	 * @deprecated since 2.3 use GroupOperations::Upsert()
	 * @param Group $group The group object to save to the database
	 * @return int The id of the newly created group. If none is created, -1
	 */
	public function InsertGroup(Group $group) : int
	{
		$id = $group->id;
		if( $id < 1 ) {
			return $this->Upsert($group);
		}
		return -1;
	}

	/**
	 * Given a group object, update its attributes in the database.
	 *
	 * @deprecated since 2.3 use GroupOperations::Upsert()
	 * @param mixed $group The group to update
	 * @return bool indication whether the update was successful
	 */
	public function UpdateGroup(Group $group) : bool
	{
		$id = $group->id;
		if( $id > 0 ) {
			if( $this->Upsert($group) ) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Given a group with id > 1, delete it from the database along with all its associations.
	 *
	 * @param int $id
	 * @return bool
	 * @throws CmsInvalidDataException
	 * @throws LogicException
	 */
	public function DeleteGroupByID(int $id) : bool
	{
		if( $id < 1 ) throw new CmsInvalidDataException(lang('missingparams'));
		if( $id == 1 ) throw new LogicException(lang('error_deletespecialgroup'));
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'user_groups where group_id = ?';
		$dbr = $db->Execute($query, [$id]);
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms where group_id = ?';
		if( $dbr ) $dbr = $db->Execute($query, [$id]);
		$query = 'DELETE FROM '.CMS_DB_PREFIX.'groups where group_id = ?';
		if( $dbr ) $dbr = $db->Execute($query, [$id]);
		return $dbr;
	}

	/**
	 * Test if a group has the specified permission(s)
	 *
	 * @param int $groupid The group id
	 * @param mixed $perm The permission name string, or (since 2.3) an array of them.
	 * If the latter, and an optional true-valued argument follows, then
	 * the named permission(s) in the array will be AND'd instead of OR'd
	 * @return bool
	 */
	public function CheckPermission(int $groupid, ...$perm) : bool
	{
		if( $groupid == 1 ) {
			$checks = restricted_cms_permissions();
			if( is_numeric($perm[0]) ) {
//				$t = CmsPermission::get_perm_name($perm[0]);
//				if( !$t ) return FALSE;
//				if( !in_array($t,$checks) ) return TRUE;
				return FALSE;
			}
			elseif( is_string($perm[0]) ) {
				if( !in_array($perm[0],$checks) ) return TRUE;
			}
			elseif( is_array($perm[0]) ) {
				if( !array_intersect($checks,$perm[0]) ) return TRUE;
			}
			return FALSE;
		}

		if( !isset($this->_perm_cache) || !is_array($this->_perm_cache) || !isset($this->_perm_cache[$groupid]) ) {
			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT permission_id FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ?';
			$dbr = $db->GetCol($query,[(int)$groupid]);
			if( $dbr ) {
				$this->_perm_cache[$groupid] = $dbr;
			}
			else {
				$this->_perm_cache[$groupid] = [];
			}
		}

		//TODO some cases,  $config['develop_mode'] >> return TRUE;
		if( empty($this->_perm_cache[$groupid]) ) return FALSE;

		if( is_numeric($perm[0]) ) {
			return in_array((int)$perm[0],$this->_perm_cache[$groupid]);
		}
		elseif( is_string($perm[0]) ) {
			$permid = (int)CmsPermission::get_perm_id($perm[0]);
			if( $permid < 1 ) return FALSE;
			return in_array($permid,$this->_perm_cache[$groupid]);
		}
		elseif( is_array($perm[0]) ) {
			if( !empty($perm[1]) ) {
				foreach( $perm[0] as $pname ) {
					$permid = (int)CmsPermission::get_perm_id($pname);
					if( $permid < 1 ) return FALSE;
					if( !in_array($permid,$this->_perm_cache[$groupid]) ) {
						return FALSE;
					}
				}
				return TRUE;
			}
			else {
				foreach( $perm[0] as $pname ) {
					$permid = (int)CmsPermission::get_perm_id($pname);
					if( $permid < 1 ) return FALSE;
					if( in_array($permid,$this->_perm_cache[$groupid]) ) {
						return TRUE;
					}
				}
			}
		}
		return FALSE;
	}

	/**
	 * Grant a permission to a group
	 *
	 * @param int $groupid The group id
	 * @param string $perm The permission name
	 */
	public function GrantPermission(int $groupid,string $perm)
	{
		$permid = (int)CmsPermission::get_perm_id($perm);
		if( $permid < 1 ) return;
		if( $groupid < 1 ) return;
		if( $groupid == 1) {
			$checks = restricted_cms_permissions();
			if( !in_array($perm,$checks) ) {
				return; //no need to record
			}
		}

		$db = CmsApp::get_instance()->GetDb();

		$new_id = $db->GenId(CMS_DB_PREFIX.'group_perms_seq');
		if( !$new_id ) return;

		$now = $db->DbTimeStamp(time());
		$query = 'INSERT INTO '.CMS_DB_PREFIX."group_perms
(group_perm_id,group_id,permission_id,create_date,modified_date)
VALUES ($new_id,?,?,$now,$now)";
// 		$dbr =
		$db->Execute($query,[$groupid,$permid]);
		unset($this->_perm_cache);
	}

	/**
	 * Disassociate the specified permission with the group
	 *
	 * @param int $groupid The group id
	 * @param string $perm The permission name
	 */
	public function RemovePermission(int $groupid,string $perm)
	{
		$permid = (int)CmsPermission::get_perm_id($perm);
		if( $permid < 1 ) return;
		if( $groupid < 1 ) return;
		if( $groupid == 1 ) {
			$checks = restricted_cms_permissions();
			if( !in_array($perm,$checks) ) {
				return; //nothing recorded
			}
		}

		$db = CmsApp::get_instance()->GetDb();

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ? AND perm_id = ?';
//		$dbr =
		$db->Execute($query,[$groupid,$permid]);
		unset($this->_perm_cache);
	}
} // class

//backward-compatibility shiv
\class_alias(GroupOperations::class, 'GroupOperations', false);
