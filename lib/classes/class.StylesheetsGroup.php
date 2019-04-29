<?php
#Class for managing a stylesheets group.
#Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use CmsDataNotFoundException;
use CmsInvalidDataException;
use CMSMS\AdminUtils;
use CMSMS\Database\Connection;
use CMSMS\StylesheetOperations;
use CmsSQLErrorException;
use const CMS_DB_PREFIX;
use function audit;
use function cms_to_stamp;

/**
 * A class representing a stylesheets group.
 *
 * Stylesheets can be organized into groups, this class is for interacting with each such group.
 *
 * @package CMS
 * @license GPL
 * @since 2.3
 */
class StylesheetsGroup
{
	/**
	 * @ignore
	 */
	const TABLENAME = 'layout_css_groups';
	/**
	 * @ignore
	 */
	const MEMBERSTABLE = 'layout_cssgroup_members';

	/**
	 * @ignore
	 */
	protected $_dirty = false;

	/**
	 * Array of group properties: id, name, description
	 * @ignore
	 */
	protected $_data = [];

	/**
	 * Array of integer stylesheet id's, ordered by group item_order
	 * @ignore
	 */
	protected $_members = [];

	/**
	 * @ignore
	 */
	private static $_lock_cache;
	private static $_lock_cache_loaded = false;

	/**
	 * Set all core properties of the group.
	 * For object initialization. No validation
	 * @param array $props
	 */
	public function set_properties(array $props)
	{
		$this->_data = $props;
	}

	/**
	 * Get the group id
	 *
	 * @return mixed int | null if this group hasn't been saved
	 */
	public function get_id()
	{
		return  ( isset($this->_data['id']) ) ? (int)$this->_data['id'] : null;
	}

	/**
	 * Get the group name
	 *
	 * @return string
	 */
	public function get_name() : string
	{
		return $this->_data['name'] ?? '';
	}

	/**
	 * Set the group name.
	 *
	 * The group name must be unique, and can only contain certain characters.
	 *
	 * @throws CmsInvalidDataException
	 * @param sting $str The stylesheets-group name. Valid per AdminUtils::is_valid_itemname()
	 */
	public function set_name(string $str)
	{
		$str = trim($str);
		if( !$str ) throw new CmsInvalidDataException('Name cannot be empty');
		if( !AdminUtils::is_valid_itemname($str) ) {
			throw new CmsInvalidDataException('Invalid characters in name');
		}
		$this->_data['name'] = $str;
		$this->_dirty = true;
	}

	/**
	 * Get the group description
	 *
	 * @return string
	 */
	public function get_description() : string
	{
		return $this->_data['description'] ?? '';
	}

	/**
	 * Set the group description
	 *
	 * @param string $str The description (maybe empty)
	 */
	public function set_description(string $str)
	{
		$str = trim($str);
		$this->_data['description'] = $str;
		$this->_dirty = true;
	}

	/**
	 * Get the timestamp for when this group was first saved
	 *
	 * @return int UNIX UTC timestamp. Default 1 (i.e. not falsy)
	 */
	public function get_created() : int
	{
		$str = $this->_data['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Get the timestamp for when this group was last saved
	 *
	 * @return int UNIX UTC timestamp. Default 1.
	 */
	public function get_modified() : int
	{
		$str = $this->_data['modified_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : $this->get_created();
	}

	/**
	 * Return the members of this group, as a comma-separated string of id's
	 *
	 * @return string, maybe empty
	 */
	public function get_members_summary() : string
	{
		return implode(',',$this->_members);
	}

	/**
	 * Return the members of this group, as objects or by name
	 *
	 * @param bool   $by_name Whether to return members' names. Default false.
	 * @return assoc. array of CmsLayoutStylesheet objects or name strings. May be empty.
	 * Keys (if any) are respective numeric id's.
	 */
	public function get_members(bool $by_name = false) : array
	{
		if( !$this->_members ) return [];

		$out = [];
		if( $by_name ) {
			$db = CmsApp::get_instance()->GetDb();
			$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.StylesheetOperations::TABLENAME.' WHERE id IN ('.implode(',',$this->_members).')';
			$dbr = $db->GetAssoc($query);
			foreach( $this->_members as $id ) {
				$out[$id] = $dbr[$id] ?? '<Missing Stylesheet>';
			}
		}
		else {
			foreach( $this->_members as $id ) {
				$out[$id] = StylesheetOperations::get_stylesheet($id);
			}
		}
		return $out;
	}

	/**
	 * Return array of stylesheet id(s) corresponding to $a
	 *
	 * @param mixed $a scalar or array, integer id(s) or string name(s), or null
	 * @return array
	 */
	protected function interpret_members($a) : array
	{
		if( $a ) {
			if( is_array($a) ) {
				$id = reset($a);
				if( is_numeric($id) ) {
					return $a;
				}
				else {
					$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.StylesheetOperations::TABLENAME.' WHERE name IN ('.str_repeat('?,',count($a)-1).'?)';
					$db = CmsApp::get_instance()->GetDb();
					$dbr = $db->GetAssoc($query,[$a]);
					if( $dbr ) {
						$ids = [];
						foreach( $a as $name ) {
							$ids[] = array_search($name,$dbr);
						}
						return array_filter($ids);
					}
				}
			}
			elseif( is_numeric($a) ) {
				return [(int)$a];
			}
			else {
				$query = 'SELECT id FROM '.CMS_DB_PREFIX.StylesheetOperations::TABLENAME.' WHERE name = ?';
				$db = CmsApp::get_instance()->GetDb();
				$id = $db->GetOne($query,[$a]);
				if( $id ) return [$id];
			}
		}
		return [];
	}

	/**
	 * Set all the members of this group, or empty it
	 *
	 * @param mixed $a scalar or array, integer id(s) or string name(s), or null
	 */
	public function set_members($a)
	{
		$ids = $this->interpret_members($a);
		if( $ids ) {
			$this->_members = array_values($ids);
		}
		else {
			$this->_members = [];
		}
		$this->_dirty = true;
	}

	/**
	 * Append member(s) to this group
	 *
	 * @param mixed $a scalar or array, integer id(s) or string name(s), or null
	 */
	public function add_members($a)
	{
		$ids = $this->interpret_members($a);
		if( $ids ) {
			if( !empty($this->_members) ) {
				$tmp = array_merge($this->_members,$ids);
				$this->_members = array_values(array_unique($tmp,SORT_NUMERIC));
			}
			else {
				$this->_members = array_values($ids);
			}
			$this->_dirty = true;
		}
	}

	/**
	 * Remove member(s) from this group
	 *
	 * @param mixed $a scalar or array, integer id(s) or string name(s), or null
	 */
	public function remove_members($a)
	{
		if( !empty($this->_members) ) {
			$ids = $this->interpret_members($a);
			if( $ids ) {
				$tmp = array_diff($this->_members, $ids);
				$this->_members = array_values($tmp);
				$this->_dirty = true;
			}
		}
	}

	/**
 	 * @ignore
 	 */
 	private static function get_locks() : array
 	{
 		if( !self::$_lock_cache_loaded ) {
 			self::$_lock_cache = [];
 			$tmp = LockOperations::get_locks('stylesheetgroup');
 			if( $tmp ) {
 				foreach( $tmp as $one ) {
 					self::$_lock_cache[$one['oid']] = $one;
 				}
 			}
 			self::$_lock_cache_loaded = true;
 		}
 		return self::$_lock_cache;
 	}

	/**
 	 * Get any applicable lock for this group
 	 *
 	 * @return mixed Lock | null
 	 * @see Lock
 	 */
 	public function get_lock()
 	{
 		$locks = self::get_locks();
 		return $locks[$this->get_id()] ?? null;
 	}

    /**
 	* Test whether this group currently has a lock
 	*
 	* @return bool
 	*/
 	public function locked() : bool
 	{
 		$lock = $this->get_lock();
 		return is_object($lock);
 	}

    /**
	 * Test whether any lock on this group has expired
 	 *
 	 * @return bool
 	 */
	public function lock_expired() : bool
 	{
 		$lock = $this->get_lock();
 		if( is_object($lock) ) return $lock->expired();
 		return false;
 	}

	/**
	 * Validate the properties of this object
	 * Unique valid name only
	 * @throws CmsInvalidDataException
	 */
	protected function validate()
	{
		if( !$this->get_name() ) {
			throw new CmsInvalidDataException('A stylesheets group must have a name');
		}
		if( !AdminUtils::is_valid_itemname($this->get_name()) ) {
			throw new CmsInvalidDataException('Name may contain only letters, numbers and underscores.');
		}

		$db = CmsApp::get_instance()->GetDb();
		$gid = $this->get_id();
		if( !$gid ) {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$dbr = $db->GetOne($query,[$this->get_name()]);
		}
		else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$dbr = $db->GetOne($query,[$this->get_name(),$gid]);
		}
		if( $dbr ) {
			throw new CmsInvalidDataException('A stylesheets group with the same name already exists');
		}
	}

	/**
	 * Record group members in the members table
	 * @ignore
	 * @param Connection $db
	 * @param bool $insert Whether this is an insert or update
	 */
	private function save_members(Connection $db,bool $insert)
	{
		$gid = $this->get_id();
		if( !$gid ) return;

		if( !$insert ) {
			$db->Execute('DELETE FROM '.CMS_DB_PREFIX.self::MEMBERSTABLE.' WHERE group_id='.$gid);
		}
		if( $this->_members ) {
			$o = 1;
			$stmt = $db->Prepare('INSERT INTO '.CMS_DB_PREFIX.self::MEMBERSTABLE.' (group_id,css_id,item_order) VALUES (?,?,?)');
			foreach( $this->_members as $id ) {
				$db->Execute($stmt,[$gid,$id,$o++]);
			}
			$stmt->close();
		}
	}

	/**
	 * @ignore
	 */
	protected function insert()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		$db = CmsApp::get_instance()->GetDb();
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,description) VALUES (?,?)';
		$dbr = $db->Execute($query,[
			$this->get_name(),
			$this->get_description(),
		]);
		if( !$dbr ) {
			throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		}
		$gid = $this->_data['id'] = $db->Insert_ID();
		$this->save_members($db,true);
		$this->_dirty = false;
		audit($gid,'CMSMS','Stylesheets group created');
	}

	/**
	 * @ignore
	 */
	protected function update()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		$db = CmsApp::get_instance()->GetDb();
		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name = ?, description = ? WHERE id = ?';
		$db->Execute($query,[
			$this->get_name(),
			$this->get_description(),
			(int)$this->get_id()
		]);
		$this->save_members($db,false);
		$this->_dirty = false;
		audit($this->get_id(),'CMSMS','Stylesheets group updated');
	}

	/**
	 * Save this object to the database
	 * @throws CmsSQLErrorException
	 * @throws CmsInvalidDataException
	 */
	public function save()
	{
		if( !$this->get_id() ) {
			$this->insert();
		}
		$this->update();
	}

	/**
	 * Delete this object from the database
	 *
	 * This method will delete the object from the database, and erase the id value
	 * from this object, suitable for re-saving
	 *
	 * @throw CmsSQLErrorException
	 */
	public function delete()
	{
		$gid = $this->get_id();
		if( !$gid ) return;

		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->Execute($query,[$gid]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::MEMBERSTABLE.' WHERE group_id = ?';
		$db->Execute($query,[$gid]);

		audit($gid,'CMSMS','Stylesheets group deleted');
		unset($this->_data['id']);
		$this->_dirty = true;
	}

	/**
	 * Load a group object from the database
	 *
	 * @param int|string $val Either the integer group id, or the group name
	 * @return self
	 * @throws CmsDataNotFoundException
	 */
	public static function load($val) : self
	{
		$db = CmsApp::get_instance()->GetDb();
		if( is_numeric($val) && $val > 0 ) {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->GetRow($query,[(int)$val]);
		}
		else {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->GetRow($query,[$val]);
		}
		if( $row ) {
			$query = 'SELECT css_id FROM '.CMS_DB_PREFIX.self::MEMBERSTABLE.' WHERE group_id=? ORDER BY item_order';
			$dbr = $db->GetCol($query,[$row['id']]);
			$ob = new self();
			$ob->set_properties($row);
			$ob->set_members($dbr);
			return $ob;
		}
		throw new CmsDataNotFoundException('Could not find stylesheets group identified by '.$val);
	}
} // class
