<?php
/*
Class for managing a stylesheets group.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AdminUtils;
use CMSMS\Database\Connection;
use CMSMS\DataException;
use CMSMS\Lock;
use CMSMS\SingleItem;
use CMSMS\SQLException;
use CMSMS\StylesheetOperations;
use LogicException;
use UnexpectedValueException;
use const CMS_DB_PREFIX;
use function cms_to_stamp;
use function CMSMS\log_info;

/**
 * A class representing a stylesheets group.
 *
 * Stylesheets can be organized into groups, this class is for interacting with each such group.
 *
 * @package CMS
 * @license GPL
 * @since 3.0
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
	protected $dirty = false;

	/**
	 * Array of group properties: id, name, description
	 * @ignore
	 */
	protected $props = [];

	/**
	 * Array of integer stylesheet id's, ordered by group item_order
	 * @ignore
	 */
	protected $members = [];

	// static properties here >> SingleItem property|ies ?
	/**
	 * @ignore
	 */
	private static $lock_cache;
	private static $lock_cache_loaded = false;

	/**
	 * Set all core properties of the group.
	 * For object initialization. No validation
	 * @param array $props
	 */
	public function set_properties(array $props)
	{
		$this->props = $props;
		$this->dirty = true;
	}

	/**
	 * Get the group id
	 *
	 * @return mixed int | null if this group hasn't been saved
	 */
	public function get_id()
	{
		return ( isset($this->props['id']) ) ? (int)$this->props['id'] : null;
	}

	/**
	 * Get the group name
	 *
	 * @return string
	 */
	public function get_name() : string
	{
		return $this->props['name'] ?? '';
	}

	/**
	 * Set the group name.
	 *
	 * The group name must be unique, and can only contain certain characters.
	 *
	 * @param sting $str The stylesheets-group name. Valid per AdminUtils::is_valid_itemname()
	 * @throws DataException or UnexpectedValueException
	 */
	public function set_name(string $str)
	{
		$str = trim($str);
		if( !$str ) throw new DataException('Name cannot be empty');
		if( !AdminUtils::is_valid_itemname($str) ) {
			throw new UnexpectedValueException('Invalid characters in name');
		}
		$this->props['name'] = $str;
		$this->dirty = true;
	}

	/**
	 * Get the group description
	 *
	 * @return string
	 */
	public function get_description() : string
	{
		return $this->props['description'] ?? '';
	}

	/**
	 * Set the group description
	 *
	 * @param string $str The description (maybe empty)
	 */
	public function set_description(string $str)
	{
		$str = trim($str);
		$this->props['description'] = $str;
		$this->dirty = true;
	}

	/**
	 * Get the timestamp for when this group was first saved
	 *
	 * @return int UNIX UTC timestamp. Default 1 (i.e. not falsy)
	 */
	public function get_created() : int
	{
		$str = $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Get the timestamp for when this group was last saved
	 *
	 * @return int UNIX UTC timestamp. Default 1.
	 */
	public function get_modified() : int
	{
		$str = $this->props['modified_date'] ?? $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Return the members of this group, as a comma-separated string of id's
	 *
	 * @return string, maybe empty
	 */
	public function get_members_summary() : string
	{
		return implode(',',$this->members);
	}

	/**
	 * Return the members of this group, as objects or by name
	 *
	 * @param bool   $by_name Whether to return members' names. Default false.
	 * @return assoc. array of Stylesheet objects or name strings. May be empty.
	 * Keys (if any) are respective numeric id's.
	 */
	public function get_members(bool $by_name = false) : array
	{
		if( !$this->members ) return [];

		$out = [];
		if( $by_name ) {
			$db = SingleItem::Db();
			$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.StylesheetOperations::TABLENAME.' WHERE id IN ('.implode(',',$this->members).')';
			$dbr = $db->getAssoc($query);
			foreach( $this->members as $id ) {
				$out[$id] = $dbr[$id] ?? '<Missing Stylesheet>';
			}
		}
		else {
			foreach( $this->members as $id ) {
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
					$db = SingleItem::Db();
					$dbr = $db->getAssoc($query,[$a]);
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
				$db = SingleItem::Db();
				$id = $db->getOne($query,[$a]);
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
			$this->members = array_values($ids);
		}
		else {
			$this->members = [];
		}
		$this->dirty = true;
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
			if( !empty($this->members) ) {
				$tmp = array_merge($this->members,$ids);
				$this->members = array_values(array_unique($tmp,SORT_NUMERIC));
			}
			else {
				$this->members = array_values($ids);
			}
			$this->dirty = true;
		}
	}

	/**
	 * Remove member(s) from this group
	 *
	 * @param mixed $a scalar or array, integer id(s) or string name(s), or null
	 */
	public function remove_members($a)
	{
		if( !empty($this->members) ) {
			$ids = $this->interpret_members($a);
			if( $ids ) {
				$tmp = array_diff($this->members, $ids);
				$this->members = array_values($tmp);
				$this->dirty = true;
			}
		}
	}

	/**
	 * @ignore
	 */
	private static function get_locks() : array
	{
		if( !self::$lock_cache_loaded ) {
			self::$lock_cache = [];
			$tmp = LockOperations::get_locks('stylesheetgroup');
			if( $tmp ) {
				foreach( $tmp as $one ) {
					self::$lock_cache[$one['oid']] = $one;
				}
			}
			self::$lock_cache_loaded = true;
		}
		return self::$lock_cache;
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
	 * Unique valid name when inserting
	 * @throws DataException or UnexpectedValueException or LogicException
	 */
	protected function validate()
	{
		$name = $this->get_name();
		if( !$name ) {
			throw new DataException('A stylesheets group must have a name');
		}
		if( !AdminUtils::is_valid_itemname($name) ) {
			throw new UnexpectedValueException('Name may contain only letters, numbers and/or these \'_ /+-,.\'.');
		}

		$db = SingleItem::Db();
		$gid = $this->get_id();
		if( $gid > 0 ) {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$dbr = $db->getOne($query,[$name,$gid]);
		}
		else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$dbr = $db->getOne($query,[$name]);
		}
		if( $dbr ) {
			throw new LogicException('A stylesheets group with the same name already exists');
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
			$db->execute('DELETE FROM '.CMS_DB_PREFIX.self::MEMBERSTABLE.' WHERE group_id='.$gid);
		}
		if( $this->members ) {
			$o = 1;
			$stmt = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.self::MEMBERSTABLE.' (group_id,css_id,item_order) VALUES (?,?,?)');
			foreach( $this->members as $id ) {
				$db->execute($stmt,[$gid,$id,$o++]);
			}
			$stmt->close();
		}
	}

	/**
	 * @ignore
	 */
	protected function insert()
	{
		if( !$this->dirty ) return;
		$this->validate();

		$db = SingleItem::Db();
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,description) VALUES (?,?)';
		$dbr = $db->execute($query,[
			$this->get_name(),
			$this->get_description(),
		]);
		if( !$dbr ) {
			throw new SQLException($db->sql.' -- '.$db->errorMsg());
		}
		$gid = $this->props['id'] = $db->Insert_ID();
		$this->save_members($db,true);
		$this->dirty = false;
		log_info($gid,'CMSMS','Stylesheets-group '.$this->get_name().' Created');
	}

	/**
	 * @ignore
	 */
	protected function update()
	{
		if( !$this->dirty ) return;
		$this->validate();

		$db = SingleItem::Db();
		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name = ?, description = ? WHERE id = ?';
		$db->execute($query,[
			$this->get_name(),
			$this->get_description(),
			(int)$this->get_id()
		]);
		$this->save_members($db,false);
		$this->dirty = false;
		log_info($this->get_id(),'CMSMS','Stylesheets group updated');
	}

	/**
	 * Save this object to the database
	 * @throws SQLException or DataException
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
	 * @throw SQLException
	 */
	public function delete()
	{
		$gid = $this->get_id();
		if( !$gid ) return;

		$db = SingleItem::Db();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->execute($query,[$gid]);
		if( !$dbr ) throw new SQLException($db->sql.' -- '.$db->errorMsg());
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::MEMBERSTABLE.' WHERE group_id = ?';
		$db->execute($query,[$gid]);

		log_info($gid,'CMSMS','Stylesheets group deleted');
		unset($this->props['id']);
		$this->dirty = true;
	}

	/**
	 * Load a group object from the database
	 *
	 * @param int|string $val Either the integer group id, or the group name
	 * @return self
	 * @throws LogicException
	 */
	public static function load($val) : self
	{
		$db = SingleItem::Db();
		if( is_numeric($val) && $val > 0 ) {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->getRow($query,[(int)$val]);
		}
		else {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->getRow($query,[$val]);
		}
		if( $row ) {
			$query = 'SELECT css_id FROM '.CMS_DB_PREFIX.self::MEMBERSTABLE.' WHERE group_id=? ORDER BY item_order';
			$dbr = $db->getCol($query,[$row['id']]);
			$ob = new self();
			$ob->set_properties($row);
			$ob->set_members($dbr);
			return $ob;
		}
		throw new LogicException('Could not find stylesheets group identified by '.$val);
	}
} // class
