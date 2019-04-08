<?php
#Class for managing template groups/categories.
#Copyright (C) 2014-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

//namespace CMSMS; CMSMS\TemplatesGroup

use CMSMS\AdminUtils;

/**
 * A class representing a templates group.
 *
 * Templates can be organized into groups (aka categories), this class manages the group itself.
 *
 * @package CMS
 * @license GPL
 * @since 2.0
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 */
class CmsLayoutTemplateCategory
{
	/**
	 * @ignore
	 */
	const TABLENAME = 'layout_tpl_categories';

	/**
	 * @ignore
	 */
	const MEMBERSTABLE  = 'layout_tplcat_members';
	const TPLTABLE  = 'layout_tplcat_members'; //deprecated since 2.3

	/**
	 * @ignore
	 */
	private $_dirty = FALSE;

	/**
	 * @ignore
	 */
	private $_data = [];

	/**
	 * Get the group id
	 *
	 * @return mixed int | null if this group hasn't been saved
	 */
	public function get_id()
	{
		return $this->_data['id'] ?? null;
	}

	/**
	 * Get the group name
	 *
	 * @return string
	 */
	public function get_name()
	{
		return $this->_data['name'] ?? '';
	}

	/**
	 * Set the group name.
	 *
	 * The group name must be unique, and can only contain certain characters.
	 *
	 * @throws CmsInvalidDataException
	 * @param sting $str The templates-group name. Valid per AdminUtils::is_valid_itemname()
	 */
	public function set_name($str)
	{
		$str = trim($str);
		if( !$str ) throw new CmsInvalidDataException('Name cannot be empty');
		if( !AdminUtils::is_valid_itemname($str) ) {
			throw new CmsInvalidDataException('Invalid characters in name');
		}
		$this->_data['name'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the group description
	 *
	 * @return string
	 */
	public function get_description()
	{
		return $this->_data['description'] ?? '';
	}

	/**
	 * Set the group description
	 *
	 * @param string $str The description (maybe empty)
	 */
	public function set_description($str)
	{
		$str = trim($str);
		$this->_data['description'] = $str;
		$this->_dirty = TRUE;
	}

	private function get_stamp($datetime)
	{
		$dt = new DateTime('@0',null);
		$dt->modify($datetime);
		return $dt->getTimestamp();
	}

   /**
	* Get the timestamp for when this group was first saved.
	*
	* @return int UNIX UTC timestamp. Default 0.
	*/
	public function get_created()
	{
		$str = $this->_data['create_date'] ?? null;
		return ($str !== null) ? $this->get_stamp($str) : 0;
	}

	/**
	 * Get the timestamp for when this group was last saved
	 *
	 * @return int UNIX UTC timestamp. Default 0.
	 */
	public function get_modified()
	{
		$str = $this->_data['modified_date'] ?? null;
		return ($str !== null) ? $this->get_stamp($str) : 0;
	}

	/**
	 * Validate the correctness of this object
	 * @throws CmsInvalidDataException
	 */
	protected function validate()
	{
		if( !$this->get_name() ) throw new CmsInvalidDataException('A template group must have a name');
		if( !AdminUtils::is_valid_itemname($this->get_name()) ) {
			throw new CmsInvalidDataException('Name may contain only letters, numbers and underscores.');
		}

		$db = cmsms()->GetDb();
		$cid = $this->get_id();
		if( !$cid ) {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$tmp = $db->GetOne($query,[$this->get_name()]);
		}
		else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$tmp = $db->GetOne($query,[$this->get_name(),$cid]);
		}
		if( $tmp ) {
			throw new CmsInvalidDataException('A template group with the same name already exists');
		}
	}

	/**
	 * @ignore
	 */
	protected function _insert()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		//,modified
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,description) VALUES (?,?)'; //,?
		$dbr = $db->Execute($query,[
			$this->get_name(),
			$this->get_description(),
//			time()
		]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$cid = $this->_data['id'] = $db->Insert_ID();
		$this->_dirty = FALSE;
		audit($cid,'CMSMS','Templates Group Created');
	}

	/**
	 * @ignore
	 */
	protected function _update()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		$db = cmsms()->GetDb();
		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name = ?, description = ? WHERE id = ?'; //, modified = ?
//		$dbr =
		$db->Execute($query,[
			$this->get_name(),
			$this->get_description(),
//			time(),
			(int)$this->get_id()
		]);
//USELESS		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$this->_dirty = FALSE;
		audit($this->get_id(),'CMSMS','Templates Group Updated');
	}

	/**
	 * Save this object to the database
	 * @throws CmsSQLErrorException
	 * @throws CmsInvalidDataException
	 */
	public function save()
	{
		if( !$this->get_id() ) return $this->_insert();
		return $this->_update();
	}

	/**
	 * Delete this object from the database
	 *
	 * This method will delete the object from the database, and erase the item order and id values
	 * from this object, suitable for re-saving
	 *
	 * @throw CmsSQLErrorException
	 */
	public function delete()
	{
		$cid = $this->get_id();
		if( !$cid ) return;

		$db = cmsms()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->Execute($query,[$cid]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

		audit($cid,'CMSMS','Templates Group Deleted');
		unset($this->_data['id']);
		$this->_dirty = TRUE;
	}

	/**
	 * @ignore
	 */
	private static function _load_from_data($row) : self
	{
		$ob = new self();
		$ob->_data = $row;
		return $ob;
	}

	/**
	 * Load a group object from the database
	 *
	 * @throws CmsDataNotFoundException
	 * @param int|string $val Either the integer group id, or the group name
	 * @return self
	 */
	public static function load($val)
	{
		$db = cmsms()->GetDb();
		$row = null;
		if( is_numeric($val) && $val > 0 ) {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->GetRow($query,[(int)$val]);
		}
		else {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->GetRow($query,[$val]);
		}
		if( !$row ) throw new CmsDataNotFoundException('Could not find template group identified by '.$val);

		return self::_load_from_data($row);
	}

	/**
	 * Load a set of categories from the database
	 *
	 * @param string $prefix An optional group-name prefix.
	 * @return array of CmsLayoutTemplateCategory objects
	 */
	public static function get_all($prefix = '')
	{
		$db = cmsms()->GetDb();
		if( $prefix ) {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name LIKE ? ORDER BY name';
			$res = $db->GetArray($query,[$prefix.'%']);
		}
		else {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY name';
			$res = $db->GetArray($query);
		}
		if( $res ) {
			$out = [];
			foreach( $res as $row ) {
				$out[] = self::_load_from_data($row);
			}
			return $out;
		}
	}
} // class
