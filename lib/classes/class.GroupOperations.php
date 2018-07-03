<?php
#Class of group-related functions
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CMSMS\Group, CmsPermission, CmsApp, \Exception;
use const CMS_DB_PREFIX;

/**
 * A singleton class for doing group related functions.
 * Many of the Group object functions are just wrappers around these.
 *
 * @since 0.6
 * @package CMS
 * @license GPL
 */
final class GroupOperations
{
	/**
	 * @ignore
	 */
	private static $_instance = null;

	/**
	 * @ignore
	 */
	private $_perm_cache;

	/**
	 * @ignore
	 */
	private function __construct() {}

	/**
	 * @ignore
	 */
	private function __clone() {}

	/**
	 * Retrieve the single instance of this class
	 *
	 * @return GroupOperations
	 */
	final public static function &get_instance() : self
	{
		if( !self::$_instance ) self::$_instance = new self();
		return self::$_instance;
	}

	/**
	 * Loads all the groups from the database and returns them
	 *
	 * @return array The list of groups
	 */
	public function LoadGroups()
	{
		$list = Group::load_all();
		return $list;
	}

	/**
	 * Load a group from the database by its id
	 *
	 * @param int $id The id of the group to load
	 * @return mixed The group if found. If it's not found, then false
	 * @deprecated
	 */
	public function LoadGroupByID($id)
	{
		return Group::load($id);
	}

	/**
	 * Given a group object, inserts it into the database.
	 *
	 * @param mixed $group The group object to save to the database
	 * @return int The id of the newly created group. If none is created, -1
	 * @deprecated
	 */
	public function InsertGroup(Group $group)
	{
		$group->save();
	}

	/**
	 * Given a group object, update its attributes in the database.
	 *
	 * @param mixed $group The group to update
	 * @return bool True if the update was successful, false if not
	 * @deprecated
	 */
	public function UpdateGroup(Group $group)
	{
		$group->save();
	}

	/**
	 * Given a group id, delete it from the database along with all its associations.
	 *
	 * @param int $id The group's id to delete
	 * @return bool True if the delete was successful. False if not.
	 * @deprecated
	 */
	public function DeleteGroupByID($id)
	{
		try {
			$group = Group::load($id);
			return $group->delete();
		}
		catch( Exception $e ) {
			return FALSE;
		}
	}

	/**
	 * Test if a group has the specified permission
	 *
	 * @param int $groupid The group id
	 * @param string $perm The permission name
	 * @return bool
	 */
	public function CheckPermission($groupid,$perm)
	{
		$permid = CmsPermission::get_perm_id($perm);
		if( $permid < 1 ) return FALSE;
		if( $groupid == 1 ) return TRUE;

		if( !isset($this->_perm_cache) || !is_array($this->_perm_cache) || !isset($this->_perm_cache[$groupid]) ) {
			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT permission_id FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ?';
			$dbr = $db->GetCol($query,array((int)$groupid));
			if( is_array($dbr) && count($dbr) ) $this->_perm_cache[$groupid] = $dbr;
		}

		return isset($this->_perm_cache[$groupid]) && in_array($permid,$this->_perm_cache[$groupid]);
	}

	/**
	 * Grant a permission to a group
	 *
	 * @param int $groupid The group id
	 * @param string $perm The permission name
	 */
	public function GrantPermission($groupid,$perm)
	{
		$permid = CmsPermission::get_perm_id($perm);
		if( $permid < 1 ) return;
		if( $groupid <= 1 ) return;

		$db = CmsApp::get_instance()->GetDb();

		$new_id = $db->GenId(CMS_DB_PREFIX.'group_perms_seq');
		if( !$new_id ) return;

		$now = $db->DbTimeStamp(time());
		$query = 'INSERT INTO '.CMS_DB_PREFIX."group_perms (group_perm_id,group_id,permission_id,create_date,modified_date)
				  VALUES (?,?,?,$now,$now)";
 		$dbr = $db->Execute($query,array($new_id,$groupid,$permid));
		unset($this->_perm_cache);
	}

	/**
	 * De-associate the specified permission with the group
	 *
	 * @param int $groupid The group id
	 * @param string $perm The permission name
	 */
	public function RemovePermission($groupid,$perm)
	{
		$permid = CmsPermission::get_perm_id($perm);
		if( $permid < 1 ) return;
		if( $groupid <= 1 ) return;

		$query = 'DELETE FROM '.CMS_DB_PREFIX.'group_perms WHERE group_id = ? AND perm_id = ?';
		$dbr = $db->Execute($query,array($groupid,$permid));
		unset($this->_perm_cache);
	}
} // class

//backward-compatibility shiv
\class_alias(GroupOperations::class, 'GroupOperations', false);
