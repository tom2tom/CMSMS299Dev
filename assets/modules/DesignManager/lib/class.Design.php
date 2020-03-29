<?php
#Class of utility functions for a design a.k.a. LayoutCollection.
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

namespace DesignManager;

use CmsApp;
use CmsDataNotFoundException;
use CmsInvalidDataException;
use CmsLayoutStylesheet;
use CmsLayoutTemplate;
use CmsLogicException;
use CMSMS\AdminUtils;
use CMSMS\Events;
use CmsSQLErrorException;
use InvalidArgumentException;
use UnexpectedValueException;
use const CMS_DB_PREFIX;
use function cms_notice;
use function cms_to_stamp;

/**
 * A class to manage a design's assigned template(s) and/or stylesheet(s)
 *
 * @package CMS
 * @license GPL
 * @since 2.0 as CmsLayoutCollection
 * @since 2.3
 * @author Robert Campbell <calguy1000@cmsmadesimple.org>
 */
class Design
{
	/**
	 * @ignore
	 */
	const TABLENAME = 'module_designs';

	/**
	 * @ignore
	 */
	const CSSTABLE  = 'module_designs_css';

	/**
	 * @ignore
	 */
	const TPLTABLE  = 'module_designs_tpl';

	/**
	 * @ignore
	 */
	private $_dirty;

	/**
	 * @ignore
	 */
	private $_data = [];

	/**
	 * @ignore
	 */
	private $_css_members = [];

	/**
	 * @ignore
	 */
	private $_tpl_members = [];

    // static properties here >> StaticProperties class ?
	/**
	 * @ignore
	 */
	private static $_raw_cache;

	/* *
	 * @ignore
	 */
//	private static $_dflt_id;

	/**
	 * Get the design id
	 * Only designs that have been saved to the database have an id.
	 * @return int
	 */
	public function get_id()
	{
		return $this->_data['id'] ?? 0;
	}

	/**
	 * Get the design name
	 * @return string
	 */
	public function get_name()
	{
		return $this->_data['name'] ?? '';
	}

	/**
	 * Set the design name
	 * This marks the design as dirty
	 *
	 * @throws CmsInvalidDataException
	 * @param string $str
	 */
	public function set_name($str)
	{
		if( !AdminUtils::is_valid_itemname($str) ) {
			throw new CmsInvalidDataException("Invalid characters in name: $str");
		}
		$this->_data['name'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the default flag
	 * @deprecated since 2.3 there is no such thing as a default design
	 *
	 * @return bool
	 */
	public function get_default()
	{
		return false;
	}

	/**
	 * [Un]set this design as the default.
	 * @deprecated since 2.3 there is no such thing as a default design
	 *
	 * @param bool $flag
	 */
	public function set_default($flag)
	{
	}

	/**
	 * Get the design description
	 *
	 * @return string
	 */
	public function get_description()
	{
		return $this->_data['description'] ?? '';
	}

	/**
	 * Set the design description
	 *
	 * @param string $str
	 */
	public function set_description($str)
	{
		$str = trim($str);
		$this->_data['description'] = $str;
		$this->_dirty = TRUE;
	}

	/**
	 * Get the timestamp for when this design was first saved
	 *
	 * @return int UNIX UTC timestamp. Default 1 (i.e. not falsy)
	 */
	public function get_created()
	{
		$str = $this->_data['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Get the timestamp for when this design was last saved
	 *
	 * @return int UNIX UTC timestamp. Default 1
	 */
	public function get_modified()
	{
		$str = $this->_data['modified_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : $this->get_created();
	}

	/**
	 * Return whether this design has stylesheet(s) assigned to it
	 *
	 * @return bool
	 */
	public function has_stylesheets()
	{
		if( $this->_css_members ) return TRUE;
		return FALSE;
	}

	/**
	 * Get the list of stylesheets (if any) assigned to this design.
	 *
	 * @return array of integers
	 */
	public function get_stylesheets()
	{
		return $this->_css_members;
	}

	/**
	 * Set the list of stylesheets (maybe none) assigned to this design
	 *
	 * @param array $id_array integer stylesheet id's, or maybe empty
	 * @throws InvalidArgumentException
	 */
	public function set_stylesheets($id_array)
	{
		if( !is_array($id_array) ) return;

		foreach( $id_array as $one ) {
			if( !is_numeric($one) || $one < 1 ) {
				throw new InvalidArgumentException(__METHOD__.' expects an array of integers');
			}
		}

		$this->_css_members = $id_array;
		$this->_dirty = TRUE;
	}

	/**
	 * Add a stylesheet to the design
	 *
	 * @param mixed $css Either an integer stylesheet id, or a CmsLayoutStylesheet object
	 * @throws UnexpectedValueException
	 */
	public function add_stylesheet($css)
	{
		$css_t = null;
		if( $css instanceof CmsLayoutStylesheet ) {
			$css_t = $css->get_id();
		}
		else if( is_numeric($css) && $css > 0 ) {
			$css_t = (int) $css;
		}
		if( $css_t < 1 ) throw new UnexpectedValueException('Invalid css id specified to '.__METHOD__);

		if( !in_array($css_t,$this->_css_members) ) {
			$this->_css_members[] = (int) $css_t;
			$this->_dirty = TRUE;
		}
	}

	/**
	 * Delete a stylesheet from the list of stylesheets assigned to this design
	 *
	 * @param mixed $css Either an integer stylesheet id, or a CmsLayoutStylesheet object
	 * @throws UnexpectedValueException
	 */
	public function delete_stylesheet($css)
	{
		$css_t = null;
		if( $css instanceof CmsLayoutStylesheet ) {
			$css_t = $css->id;
		}
		else if( is_numeric($css) ) {
			$css_t = (int) $css;
		}
		if( $css_t < 1 ) throw new UnexpectedValueException('Invalid css id specified to '.__METHOD__);

		if( !in_array($css_t,$this->_css_members) ) return;
		$t = [];
		foreach( $this->_css_members as $one ) {
			if( $css_t != $one ) {
				$t[] = $one;
			}
			else {
				// do we want to delete this css from the database?
			}
		}
		$this->_css_members = $t;
		$this->_dirty = TRUE;
	}

	/**
	 * Test if this design has templates assigned to it
	 *
	 * @return bool
	 */
	public function has_templates()
	{
		if( $this->_tpl_members ) return TRUE;
		return FALSE;
	}

	/**
	 * Return a list of the template id's assigned to this template
	 *
	 * @return mixed array of integers | null
	 */
	public function get_templates()
	{
		if( !$this->get_id() ) return null;
		if( !$this->has_templates() ) return null;

		return $this->_tpl_members;
	}

	/**
	 * Set the list of templates (maybe none) assigned to this design
	 *
	 * @throws CmsLogicException
	 * @param array $id_array Array of integer template id's, or maybe empty
	 */
	public function set_templates($id_array)
	{
		if( !($id_array) ) return;

		foreach( $id_array as $one ) {
			if( !is_numeric($one) && $one < 1 ) throw new CmsLogicException(__METHOD__.' expects an array of integers');
		}

		$this->_tpl_members = $id_array;
		$this->_dirty = TRUE;
	}

	/**
	 * Add a template to the list of templates assigned to this design.
	 *
	 * @throws CmsLogicException
	 * @param mixed $tpl Accepts either an integer template id, or an instance of a CmsLayoutTemplate object
	 */
	public function add_template($tpl)
	{
		$tpl_id = null;
		if( $tpl instanceof CmsLayoutTemplate ) {
			$tpl_id = $tpl->get_id();
		}
		else if( is_numeric($tpl) ) {
			$tpl_id = (int) $tpl;
		}
		if( $tpl_id < 1 ) throw new CmsLogicException('Invalid template id specified to '.__METHOD__);

		if( !is_array($this->_tpl_members) ) $this->_tpl_members = [];
		if( !in_array($tpl_id,$this->_tpl_members) ) $this->_tpl_members[] = (int) $tpl_id;
		$this->_dirty = TRUE;
	}

	/**
	 * Remove a template from the list of the ones assigned to this design
	 *
	 * @throws CmsInvalidDataException
	 * @param mixed $tpl Either an integer template id, or a CmsLayoutTemplate object
	 */
	public function delete_template($tpl)
	{
		$tpl_id = null;
		if( $tpl instanceof CmsLayoutTemplate ) {
			$tpl_id = $tpl->get_id();
		}
		else if( is_numeric($tpl) ) {
			$tpl_id = (int) $tpl;
		}
		if( $tpl_id <= 0 ) throw new CmsLogicException('Invalid template id specified to '.__METHOD__);

		if( !in_array($tpl_id,$this->_tpl_members) ) return;
		$t = [];
		foreach( $this->_tpl_members as $one ) {
			if( $tpl_id != $one ) {
				$t[] = $one;
			}
			else {
				// do we want to delete this css from the database?
			}
		}
		$this->_tpl_members = $t;
		$this->_dirty = TRUE;
	}

	/**
	 * Validate this object before saving.
	 *
	 * @throws CmsInvalidDataException
	 */
	protected function validate()
	{
		if( $this->get_name() == '' ) throw new CmsInvalidDataException('A design must have a name.');
		if( !AdminUtils::is_valid_itemname($this->get_name()) ) {
			throw new CmsInvalidDataException('There are invalid character(s) in the design name.');
		}

		if( $this->_css_members ) {
			$t1 = array_unique($this->_css_members);
			if( count($t1) != count($this->_css_members) ) throw new CmsInvalidDataException('Duplicate CSS ids exist in design.');
		}

		$db = CmsApp::get_instance()->GetDb();
		$tmp = null;
		if( $this->get_id() ) {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$tmp = $db->GetOne($query,[$this->get_name(),$this->get_id()]);
		}
		else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$tmp = $db->GetOne($query,[$this->get_name()]);
		}
		if( $tmp ) {
			throw new CmsInvalidDataException('A design with the same name already exists.');
		}
	}

	/**
	 * @ignore
	 * @throws CmsSQLErrorException
	 */
	private function _insert()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		$db = CmsApp::get_instance()->GetDb();
		//TODO DT fields for created, modified
		// ,dflt,created,modified
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,description) VALUES (?,?)'; //,?,?,?
//		$now = time();
		$dbr = $db->Execute($query,[$this->get_name(), $this->get_description()]); // , ($this->get_default())?1:0, $now, $now
		if( !$dbr ) {
			throw new CmsSQLErrorException($db->sql.' --1 '.$db->ErrorMsg());
		}

		$did = $this->_data['id'] = $db->Insert_ID();
/*
		if( $this->get_default() ) {
			$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET dflt = 0 WHERE id != ?';
//			$dbr =
			$db->Execute($query,[$did]);
//USELESS			if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		}
*/
		if( $this->_css_members ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.self::CSSTABLE.' (design_id,css_id,css_order) VALUES (?,?,?)';
			for( $i = 0, $n = count($this->_css_members); $i < $n; $i++ ) {
				$css_id = $this->_css_members[$i];
				$dbr = $db->Execute($query,[$did,$css_id,$i+1]);
			}
		}
		if( $this->_tpl_members ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TPLTABLE.' (design_id,tpl_id,tpl_order) VALUES(?,?,?)';
			for( $i = 0, $n = count($this->_tpl_members); $i < $n; $i++ ) {
				$tpl_id = $this->_tpl_members[$i];
				$dbr = $db->Execute($query,[$did,$tpl_id,$i+1]);
			}
		}

		$this->_dirty = FALSE;
		cms_notice('Design '.$this->get_name().' created');
	}

	/**
	 * @ignore
	 * @throws CmsSQLErrorException
	 */
	private function _update()
	{
		if( !$this->_dirty ) return;
		$this->validate();

		$did = $this->get_id();
		$db = CmsApp::get_instance()->GetDb();
		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name = ?, description = ? WHERE id = ?'; //, dflt = ?, modified = ?
		$dbr = $db->Execute($query,[$this->get_name(), $this->get_description(), $did]); //, ($this->get_default())?1:0, time(),
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' --2 '.$db->ErrorMsg());
/*
		if( $this->get_default() ) {
			$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET dflt = 0 WHERE id != ?';
//			$dbr =
			$db->Execute($query,[$did]);
//USELESS			if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		}
*/
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ?';
		$db->Execute($query,[$did]);

		if( $this->_css_members ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.self::CSSTABLE.' (design_id,css_id,css_order) VALUES (?,?,?)';
			for( $i = 0, $n = count($this->_css_members); $i < $n; $i++ ) {
				$css_id = $this->_css_members[$i];
				$dbr = $db->Execute($query,[$did,$css_id,$i+1]);
			}
		}

		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
		$db->Execute($query,[$did]);

		if( $this->_tpl_members ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TPLTABLE.' (design_id,tpl_id,tpl_order) VALUES (?,?,?)';
			for( $i = 0, $n = count($this->_tpl_members); $i < $n; $i++ ) {
				$tpl_id = $this->_tpl_members[$i];
				$dbr = $db->Execute($query,[$did,$tpl_id,$i+1]);
			}
		}

		$this->_dirty = FALSE;
		cms_notice('Design '.$this->get_name().' updated');
	}

	/**
	 * Save this design
	 * This method sends the AddDesignPre and AddDesignPost events before and after saving a new design
	 * or the EditDesignPre and EditDesignPost events before and after saving an existing design.
	 * @deprecated since 2.3 the event originator is 'Core', change to 'DesignManager'
	 */
	public function save()
	{
		if( $this->get_id() ) {
			Events::SendEvent( 'Core', 'EditDesignPre', [ 'CmsLayoutCollection' => &$this ] ); //deprecated since 2.3 key
			$this->_update();
			Events::SendEvent( 'Core', 'EditDesignPost', [ 'CmsLayoutCollection' => &$this ] );
			return;
		}
		Events::SendEvent( 'Core', 'AddDesignPre', [ 'CmsLayoutCollection' => &$this ] );
		$this->_insert();
		Events::SendEvent( 'Core', 'AddDesignPost', [ 'CmsLayoutCollection' => &$this ] );
	}

	/**
	 * Delete the current design
	 * This method normally does nothing if this design has associated templates.
	 * @deprecated since 2.3 the event originator is 'Core', change to 'DesignManager'
	 *
	 * @throws CmsLogicException
	 * @param bool $force Force deleting the design even if there are templates assigned
	 */
	public function delete($force = FALSE)
	{
		$did = $this->get_id();
		if( !$did ) return;
/*
		if( !$force && $this->has_templates() ) {
			throw new CmsLogicException('Cannot delete a design that has templates assigned');
		}
*/
		Events::SendEvent( 'Core', 'DeleteDesignPre', [ 'CmsLayoutCollection' => &$this ] ); //TODO deprecated since 2.3 key
		$db = CmsApp::get_instance()->GetDb();
/*
		if( $this->_css_members ) {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ?';
			$dbr = $db->Execute($query,[$did]);
			$this->_css_members = [];
			$this->_dirty = TRUE;
		}

		if( $this->_tpl_members ) {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
			$dbr = $db->Execute($query,[$did]);
			$this->_tpl_members = [];
			$this->_dirty = TRUE;
		}
*/
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->Execute($query,[$did]);

		cms_notice('Design '.$this->get_name().' deleted');
		Events::SendEvent( 'Core', 'DeleteDesignPost', [ 'CmsLayoutCollection' => &$this ] );
		unset($this->_data['id']);
		$this->_dirty = TRUE;
	}

	/**
	 * @ignore
	 */
	protected static function _load_from_data($row)
	{
		$ob = new self();
		$css = null;
		$tpls = null;
		if( isset($row['css']) ) {
			$css = $row['css'];
			unset($row['css']);
		}
		if( isset($row['templates']) ) {
			$tpls = $row['templates'];
			unset($row['templates']);
		}
		$ob->_data = $row;
		if( $css ) $ob->_css_members = $css;
		if( $tpls ) $ob->_tpl_members = $tpls;

		return $ob;
	}

	/**
	 * Load a design object
	 *
	 * @throws CmsDataNotFoundException
	 * @param mixed $x - Accepts either an integer design id, or a design name,
	 * @return Design
	 */
	public static function load($x)
	{
		$db = CmsApp::get_instance()->GetDb();
		$row = null;
		if( is_numeric($x) && $x > 0 ) {
			if( self::$_raw_cache ) {
				if( isset(self::$_raw_cache[$x]) ) return self::_load_from_data(self::$_raw_cache[$x]);
			}
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->GetRow($query,[(int)$x]);
		}
		else if( is_string($x) && $x !== '' ) {
			if( self::$_raw_cache ) {
				foreach( self::$_raw_cache as $row ) {
					if( $row['name'] == $x ) return self::_load_from_data($row);
				}
			}

			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->GetRow($query,[trim($x)]);
		}

		if( !$row ) throw new CmsDataNotFoundException('Could not find design row identified by '.$x);

		// get assigned stylesheets
		$query = 'SELECT css_id FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ? ORDER BY css_order';
		$tmp = $db->GetCol($query,[(int) $row['id']]);
		if( $tmp ) $row['css'] = $tmp;

		// get assigned templates
		$query = 'SELECT tpl_id FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
		$tmp = $db->GetCol($query,[(int) $row['id']]);
		if( $tmp ) $row['templates'] = $tmp;

		self::$_raw_cache[$row['id']] = $row;
		return self::_load_from_data($row);
	}

	/**
	 * Load all designs
	 *
	 * @param string $quick Do not load the templates and stylesheets.
	 * @return array Array of Design objects.
	 */
	public static function get_all($quick = FALSE)
	{
		$out = null;
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY name ASC';
		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->GetArray($query);
		if( $dbr ) {
			$ids = [];
			$cache = [];
			foreach( $dbr as $row ) {
				$ids[] = $row['id'];
				$cache[$row['id']] = $row;
			}

			if( !$quick ) {
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id IN ('.implode(',',$ids).') ORDER BY design_id,css_order';
				$dbr2 = $db->GetArray($query);
				if( $dbr2 ) {
					foreach( $dbr2 as $row ) {
						if( !isset($cache[$row['design_id']]) ) continue; // orphaned entry, bad.
						$design = &$cache[$row['design_id']];
						if( !isset($design['css']) ) $design['css'] = [];
						if( !in_array($row['css_id'],$design['css']) ) $design['css'][] = $row['css_id'];
					}
				}

				$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id IN ('.implode(',',$ids).') ORDER BY design_id,tpl_order';
				$dbr2 = $db->GetArray($query);
				if( $dbr2 ) {
					foreach( $dbr2 as $row ) {
						if( !isset($cache[$row['design_id']]) ) continue; // orphaned entry, bad.
						$design = &$cache[$row['design_id']];
						if( !isset($design['templates']) ) $design['templates'] = [];
						$design['templates'][] = $row['tpl_id'];
					}
				}
			}

			self::$_raw_cache = $cache;

			$out = [];
			foreach( $cache as $key => $row ) {
				$out[] = self::_load_from_data($row);
			}
			return $out;
		}
	}

	/**
	 * Get a list of designs
	 *
	 * @return array of designs: key=id, value=name
	 */
	public static function get_list()
	{
		$designs = self::get_all(TRUE);
		if( $designs ) {
			$out = [];
			foreach( $designs as $one ) {
				$out[$one->get_id()] = $one->get_name();
			}
			return $out;
		}
	}

	/**
	 * Load the default design
	 * @deprecated since 2.3 there is no such thing as a default design
	 *
	 * @throws CmsInvalidDataException
	 * @return Design
	 */
	public static function load_default()
	{
/*		$tmp = null;
		if( self::$_dflt_id == '' ) {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE dflt = 1';
			$db = CmsApp::get_instance()->GetDb();
			$tmp = (int) $db->GetOne($query);
			if( $tmp > 0 ) self::$_dflt_id = $tmp;
		}

		if( self::$_dflt_id > 0 ) return self::load(self::$_dflt_id);
		throw new CmsInvalidDataException('There is no default design');
*/
	}

	/**
	 * Given a base name, suggest a name for a copied design
	 *
	 * @param string $newname
	 * @return string
	 */
	public static function suggest_name($newname = '')
	{
		if( $newname == '' ) $newname = 'New Design';
		$list = self::get_list();
		$names = array_values($list);

		$origname = $newname;
		$n = 1;
		while( $n < 100 && in_array($newname,$names) ) {
			$n++;
			$newname = $origname.' '.$n;
		}

		if( $n == 100 ) return;
		return $newname;
	}
} // class

class_alias('DesignManager\Design','CmsLayoutCollection',false);
