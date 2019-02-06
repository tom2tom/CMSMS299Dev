<?php
#Class for managing template categories.
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

//namespace CMSMS;

use CMSMS\AdminUtils;

/**
 * A class representing a template category.
 *
 * Templates can be optionally organized into categories, this class manages the category itself.
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
	const TPLTABLE  = 'layout_cat_tplassoc';

	/**
	 * @ignore
	 */
	private $_dirty = FALSE;

	/**
	 * @ignore
	 */
	private $_data = [];

	/**
	 * Get the category id
	 *
	 * @return mixed int | null if this category hasn't been saved
	 */
	public function get_id()
	{
		return $this->_data['id'] ?? null;
	}

	/**
	 * Get the category name
	 *
	 * @return string
	 */
	public function get_name()
	{
		return $this->_data['name'] ?? '';
	}

	/**
	 * Set the category name.
	 *
	 * The category name must be unique, and can only contain certain characters.
	 *
	 * @throws CmsInvalidDataException
	 * @param sting $str The template-category name. Valid per AdminUtils::is_valid_itemname()
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
	 * Get the category description
	 *
	 * @return string
	 */
	public function get_description()
	{
		return $this->_data['description'] ?? '';
	}

	/**
	 * Set the category description
	 *
	 * @param string $str The description (maybe empty)
	 */
	public function set_description($str)
	{
		$str = trim($str);
		$this->_data['description'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the category order
	 *
	 * @return int
	 */
	public function get_item_order()
	{
		return $this->_data['item_order'] ?? 0;
	}

	/**
	 * Set the item order.
	 *
	 * The item order must be > 0, unique and incremental
	 * No validation is done on the item order in this method.
	 *
	 * @param int $idx
	 */
	public function set_item_order($idx)
	{
		$idx = (int)$idx;
		if( $idx < 1 ) return;
		$this->_data['item_order'] = $idx;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the date that this category was last saved to the database
	 *
	 * @return int The unix timestamp from the database
	 */
	public function get_modified()
	{
		return $this->_data['modified'] ?? 0;
	}

	/**
	 * Validate the correctness of this object
	 * @throws CmsInvalidDataException
	 */
	protected function validate()
	{
		if( !$this->get_name() ) throw new CmsInvalidDataException('A template categoy must have a name');
		if( !AdminUtils::is_valid_itemname($this->get_name()) ) {
			throw new CmsInvalidDataException('Name must contain only letters, numbers and underscores.');
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
			throw new CmsInvalidDataException('A template categoy with the same name already exists');
		}
	}

	/**
	 * @ignore
	 */
	protected function _insert()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		$db = cmsms()->GetDb();
		$query = 'SELECT max(item_order) FROM '.CMS_DB_PREFIX.self::TABLENAME;
		$item_order = $db->GetOne($query);
		if( !$item_order ) $item_order = 0;
		$item_order++;
		$this->_data['item_order'] = $item_order;

		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,description,item_order,modified) VALUES (?,?,?,?)';
		$dbr = $db->Execute($query,[
			$this->get_name(),
			$this->get_description(),
			$this->get_item_order(),
			time()
		]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$cid = $this->_data['id'] = $db->Insert_ID();
		$this->_dirty = FALSE;
		audit($cid,'CMSMS','Template Category Created');
	}

	/**
	 * @ignore
	 */
	protected function _update()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		$db = cmsms()->GetDb();
		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name = ?, description = ?, item_order = ?, modified = ? WHERE id = ?';
//		$dbr =
		$db->Execute($query,[
			$this->get_name(),
			$this->get_description(),
			$this->get_item_order(),
			time(),
			(int)$this->get_id()
		]);
//USELESS		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$this->_dirty = FALSE;
		audit($this->get_id(),'CMSMS','Template Category Updated');
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

		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET item_order = item_order - 1 WHERE item_order > ?';
		$db->Execute($query,[$this->_data['item_order']]);

		audit($cid,'CMSMS','Template Category Deleted');
		unset($this->_data['item_order']);
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
	 * Load a category object from the database
	 *
	 * @throws CmsDataNotFoundException
	 * @param int|string $val Either the integer category id, or the category name
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
		if( !$row ) throw new CmsDataNotFoundException('Could not find template category identified by '.$val);

		return self::_load_from_data($row);
	}

	/**
	 * Load a set of categories from the database
	 *
	 * @param string $prefix An optional category name prefix.
	 * @return array Array of CmsLayoutTemplateCategory objects
	 */
	public static function get_all($prefix = '')
	{
		$db = cmsms()->GetDb();
		if( $prefix ) {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name LIKE ? ORDER BY item_order ASC';
			$res = $db->GetArray($query,[$prefix.'%']);
		}
		else {
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY item_order ASC';
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
