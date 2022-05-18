<?php
/*
Class of utility functions for a design a.k.a. LayoutCollection.
Copyright (C) 2014-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace DesignManager;

use CMSMS\AdminUtils;
use CMSMS\Events;
use CMSMS\Lone;
use CMSMS\SQLException;
use CMSMS\Stylesheet;
use CMSMS\Template;
use InvalidArgumentException;
use LogicException;
use UnexpectedValueException;
use const CMS_DB_PREFIX;
use function CMSMS\log_notice;
use function cms_to_stamp;

/**
 * A class to manage a design's assigned template(s) and/or stylesheet(s)
 *
 * @package CMS
 * @license GPL
 * @since 2.0 as CmsLayoutCollection
 * @since 3.0
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
	private $dirty;

	/**
	 * @ignore
	 */
	private $props = [];

	/**
	 * @ignore
	 */
	private $css_members = [];

	/**
	 * @ignore
	 */
	private $tpl_members = [];

    // static properties here >> Lone property|ies ?
	/**
	 * @ignore
	 */
	private static $raw_cache;

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
		return $this->props['id'] ?? 0;
	}

	/**
	 * Get the design name
	 * @return string
	 */
	public function get_name()
	{
		return $this->props['name'] ?? '';
	}

	/**
	 * Set the design name
	 * This marks the design as dirty
	 *
	 * @param string $str
	 * @throws UnexpectedValueException
	 */
	public function set_name($str)
	{
		if( !AdminUtils::is_valid_itemname($str) ) {
			throw new UnexpectedValueException("Invalid characters in name: $str");
		}
		$this->props['name'] = $str;
		$this->dirty = TRUE;
	}

	/**
	 * Get the default flag
	 * @deprecated since 3.0 there is no such thing as a default design
	 *
	 * @return bool
	 */
	public function get_default()
	{
		return FALSE;
	}

	/**
	 * [Un]set this design as the default.
	 * @deprecated since 3.0 there is no such thing as a default design
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
		return $this->props['description'] ?? '';
	}

	/**
	 * Set the design description
	 *
	 * @param string $str
	 */
	public function set_description($str)
	{
		$str = trim($str);
		$this->props['description'] = $str;
		$this->dirty = TRUE;
	}

	/**
	 * Get the timestamp for when this design was first saved
	 *
	 * @return int UNIX UTC timestamp. Default 1 (i.e. not falsy)
	 */
	public function get_created()
	{
		$str = $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Get the timestamp for when this design was last saved
	 *
	 * @return int UNIX UTC timestamp. Default 1
	 */
	public function get_modified()
	{
		$str = $this->props['modified_date'] ?? $this->props['create_date'] ?? '';
		return ($str) ? cms_to_stamp($str) : 1;
	}

	/**
	 * Return whether this design has stylesheet(s) assigned to it
	 *
	 * @return bool
	 */
	public function has_stylesheets()
	{
		if( $this->css_members ) return TRUE;
		return FALSE;
	}

	/**
	 * Return the id's of stylesheets assigned to this design (if any).
	 * Always reports nil stylesheets if the design is not yet saved.
	 *
	 * @return array integer(s) | empty
	 */
	public function get_stylesheets()
	{
		if( !$this->get_id() ) return [];
		return $this->css_members;
	}

	/**
	 * Set the stylesheet(s) (maybe none) assigned to this design
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

		$this->css_members = $id_array;
		$this->dirty = TRUE;
	}

	/**
	 * Add a stylesheet to the design
	 *
	 * @param mixed $css Either an integer stylesheet id, or a Stylesheet object
	 * @throws LogicException
	 */
	public function add_stylesheet($css)
	{
		$css_t = null;
		if( $css instanceof Stylesheet ) {
			$css_t = $css->get_id();
		}
		elseif( is_numeric($css) && $css > 0 ) {
			$css_t = (int) $css;
		}
		if( $css_t < 1 ) throw new LogicException('Invalid css id provided to '.__METHOD__);

		if( !in_array($css_t,$this->css_members) ) {
			$this->css_members[] = (int)$css_t;
			$this->dirty = TRUE;
		}
	}

	/**
	 * Delete a stylesheet from this design
	 *
	 * @param mixed $css Either an integer stylesheet id, or a Stylesheet object
	 * @throws LogicException
	 */
	public function delete_stylesheet($css)
	{
		$css_t = null;
		if( $css instanceof Stylesheet ) {
			$css_t = $css->id;
		}
		elseif( is_numeric($css) ) {
			$css_t = (int) $css;
		}
		if( $css_t < 1 ) throw new LogicException('Invalid css id provided to '.__METHOD__);

		if( !in_array($css_t,$this->css_members) ) return;
		$t = [];
		foreach( $this->css_members as $one ) {
			if( $css_t != $one ) {
				$t[] = $one;
			}
			else {
				// do we want to delete this css from the database?
			}
		}
		$this->css_members = $t;
		$this->dirty = TRUE;
	}

	/**
	 * Test if this design has templates assigned to it
	 *
	 * @return bool
	 */
	public function has_templates()
	{
		if( $this->tpl_members ) return TRUE;
		return FALSE;
	}

	/**
	 * Return the id's of templates assigned to this design (if any).
	 * Always reports nil templates if the design is not yet saved.
	 *
	 * @return array integer(s) | empty
	 */
	public function get_templates()
	{
		if( !$this->get_id() ) return [];
		return $this->tpl_members;
	}

	/**
	 * Set the template(s) (maybe none) assigned to this design
	 *
	 * @throws InvalidArgumentException
	 * @param array $id_array Array of integer template id's, or maybe empty
	 */
	public function set_templates($id_array)
	{
		if( !($id_array) ) return;

		foreach( $id_array as $one ) {
			if( !is_numeric($one) && $one < 1 ) throw new InvalidArgumentException(__METHOD__.' expects an array of integers');
		}

		$this->tpl_members = $id_array;
		$this->dirty = TRUE;
	}

	/**
	 * Add a template to this design
	 *
	 * @throws LogicException
	 * @param mixed $tpl Accepts either an integer template id, or an instance of a Template object
	 */
	public function add_template($tpl)
	{
		$tpl_id = null;
		if( $tpl instanceof Template ) {
			$tpl_id = $tpl->get_id();
		}
		elseif( is_numeric($tpl) ) {
			$tpl_id = (int) $tpl;
		}
		if( $tpl_id < 1 ) throw new LogicException('Invalid template id specified to '.__METHOD__);

		if( !is_array($this->tpl_members) ) $this->tpl_members = [];
		if( !in_array($tpl_id,$this->tpl_members) ) $this->tpl_members[] = (int) $tpl_id;
		$this->dirty = TRUE;
	}

	/**
	 * Remove a template from this design
	 *
	 * @throws LogicException
	 * @param mixed $tpl Either an integer template id, or a Template object
	 */
	public function delete_template($tpl)
	{
		$tpl_id = null;
		if( $tpl instanceof Template ) {
			$tpl_id = $tpl->get_id();
		}
		elseif( is_numeric($tpl) ) {
			$tpl_id = (int) $tpl;
		}
		if( $tpl_id <= 0 ) throw new LogicException('Invalid template id specified to '.__METHOD__);

		if( !in_array($tpl_id,$this->tpl_members) ) return;
		$t = [];
		foreach( $this->tpl_members as $one ) {
			if( $tpl_id != $one ) {
				$t[] = $one;
			}
			else {
				// do we want to delete this css from the database?
			}
		}
		$this->tpl_members = $t;
		$this->dirty = TRUE;
	}

	/**
	 * Validate this object before saving.
	 *
	 * @throws LogicException or UnexpectedValueException
	 */
	protected function validate()
	{
		if( $this->get_name() == '' ) throw new LogicException('A design must have a name.');
		if( !AdminUtils::is_valid_itemname($this->get_name()) ) {
			throw new UnexpectedValueException('There are invalid character(s) in the design name.');
		}

		if( $this->css_members ) {
			$t1 = array_unique($this->css_members);
			if( count($t1) != count($this->css_members) ) throw new LogicException('Duplicate CSS ids exist in the design.');
		}

		$db = Lone::get('Db');
		$tmp = null;
		if( $this->get_id() ) {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$tmp = $db->getOne($query,[$this->get_name(),$this->get_id()]);
		}
		else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$tmp = $db->getOne($query,[$this->get_name()]);
		}
		if( $tmp ) {
			throw new LogicException('A design with the same name already exists.');
		}
	}

	/**
	 * @ignore
	 * @throws SQLException
	 */
	private function _insert()
	{
		if( !$this->dirty ) return;
		$this->validate();

		$db = Lone::get('Db');
		//TODO DT fields for created, modified
		// ,dflt,created,modified
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,description) VALUES (?,?)'; //,?,?,?
//		$now = time();
		$dbr = $db->execute($query,[$this->get_name(),$this->get_description()]); //,($this->get_default())?1:0, $now, $now
		if( !$dbr ) {
			throw new SQLException($db->sql.' --1 '.$db->errorMsg());
		}

		$did = $this->props['id'] = $db->Insert_ID();
/*
		if( $this->get_default() ) {
			$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET dflt = 0 WHERE id != ?';
//			$dbr = useless for updates
			$db->execute($query,[$did]);
			if( $db->errorNo() > 0 ) throw new SQLException($db->sql.' --1 '.$db->errorMsg());
		}
*/
		if( $this->css_members ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.self::CSSTABLE.' (design_id,css_id,css_order) VALUES (?,?,?)';
			for( $i = 0, $n = count($this->css_members); $i < $n; $i++ ) {
				$css_id = $this->css_members[$i];
				$dbr = $db->execute($query,[$did,$css_id,$i+1]);
			}
		}
		if( $this->tpl_members ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TPLTABLE.' (design_id,tpl_id,tpl_order) VALUES(?,?,?)';
			for( $i = 0, $n = count($this->tpl_members); $i < $n; $i++ ) {
				$tpl_id = $this->tpl_members[$i];
				$dbr = $db->execute($query,[$did,$tpl_id,$i+1]);
			}
		}

		$this->dirty = FALSE;
		log_notice('Design created',$this->get_name());
	}

	/**
	 * @ignore
	 * @throws SQLException
	 */
	private function _update()
	{
		if( !$this->dirty ) return;
		$this->validate();

		$did = $this->get_id();
		$db = Lone::get('Db');
		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET name = ?, description = ? WHERE id = ?'; //, dflt = ?, modified = ?
//		$dbr = useless for update
		$db->execute($query,[$this->get_name(),$this->get_description(),$did]); //,($this->get_default())?1:0, time(),
		if( $db->errorNo() > 0 ) throw new SQLException($db->sql.' --2 '.$db->errorMsg());
/*
		if( $this->get_default() ) {
			$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET dflt = 0 WHERE id != ?';
//			$dbr = useless for updates
			$db->execute($query,[$did]);
			if( $db->errorNo() > 0 ) throw new SQLException($db->sql.' --3 '.$db->errorMsg());
		}
*/
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ?';
		$db->execute($query, [$did]);

		if( $this->css_members ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.self::CSSTABLE.' (design_id,css_id,css_order) VALUES (?,?,?)';
			for( $i = 0, $n = count($this->css_members); $i < $n; $i++ ) {
				$css_id = $this->css_members[$i];
				$dbr = $db->execute($query,[$did,$css_id,$i+1]);
			}
		}

		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
		$db->execute($query,[$did]);

		if( $this->tpl_members ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TPLTABLE.' (design_id,tpl_id,tpl_order) VALUES (?,?,?)';
			for( $i = 0, $n = count($this->tpl_members); $i < $n; $i++ ) {
				$tpl_id = $this->tpl_members[$i];
				$dbr = $db->execute($query,[$did,$tpl_id,$i+1]);
			}
		}

		$this->dirty = FALSE;
		log_notice('Design updated',$this->get_name());
	}

	/**
	 * Save this design
	 * This method sends the AddDesignPre and AddDesignPost events before and after saving a new design
	 * or the EditDesignPre and EditDesignPost events before and after saving an existing design.
	 * @deprecated since 3.0 the event originator is 'Core', change to 'DesignManager'
	 */
	public function save()
	{
		if( $this->get_id() ) {
			Events::SendEvent('Core','EditDesignPre',['CmsLayoutCollection' => &$this]); // deprecated since 3.0
			Events::SendEvent('DesignManager','EditDesignPre',['Design' => &$this]);
			$this->_update();
			Events::SendEvent('Core','EditDesignPost',['CmsLayoutCollection' => &$this]); // deprecated since 3.0
			Events::SendEvent('DesignManager','EditDesignPost',['Design' => &$this]);
			return;
		}
		Events::SendEvent('Core','AddDesignPre',['CmsLayoutCollection' => &$this]); // deprecated since 3.0
		Events::SendEvent('DesignManager','AddDesignPre',['Design' => &$this]);
		$this->_insert();
		Events::SendEvent('Core','AddDesignPost',['CmsLayoutCollection' => &$this]); // deprecated since 3.0
		Events::SendEvent('DesignManager','AddDesignPost',['Design' => &$this]);
	}

	/**
	 * Delete the current design
	 * This method normally does nothing if this design has associated templates.
	 * @deprecated since 3.0 event originator 'Core', 3.0+ also from 'DesignManager'
	 *
	 * @throws LogicException
	 * @param bool $force Force deleting the design even if there are templates assigned
	 */
	public function delete($force = FALSE)
	{
		$did = $this->get_id();
		if( !$did ) return;
/*
		if( !$force && $this->has_templates() ) {
			throw new LogicException('Cannot delete a design that has templates assigned');
		}
*/
		Events::SendEvent('Core','DeleteDesignPre',['CmsLayoutCollection' => &$this]); // deprecated since 3.0
		Events::SendEvent('DesignManager','DeleteDesignPre',['Design' => &$this]);
/*
		if( $this->css_members ) {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ?';
			$dbr = $db->execute($query,[$did]);
			$this->css_members = [];
			$this->dirty = TRUE;
		}

		if( $this->tpl_members ) {
			$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
			$dbr = $db->execute($query,[$did]);
			$this->tpl_members = [];
			$this->dirty = TRUE;
		}
*/
		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->execute($query,[$did]);

		log_notice('Design deleted',$this->get_name());
		Events::SendEvent('Core','DeleteDesignPost',['CmsLayoutCollection' => &$this]); // deprecated since 3.0
		Events::SendEvent('DesignManager','DeleteDesignPost',['Design' => &$this]);
		unset($this->props['id']);
		$this->dirty = TRUE;
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
		$ob->props = $row;
		if( $css ) $ob->css_members = $css;
		if( $tpls ) $ob->tpl_members = $tpls;

		return $ob;
	}

	/**
	 * Load a design object
	 *
	 * @throws LogicException
	 * @param mixed $x - Accepts either an integer design id, or a design name,
	 * @return Design
	 */
	public static function load($x)
	{
		$db = Lone::get('Db');
		$row = null;
		if( is_numeric($x) && $x > 0 ) {
			if( self::$raw_cache ) {
				if( isset(self::$raw_cache[$x]) ) return self::_load_from_data(self::$raw_cache[$x]);
			}
			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->getRow($query,[(int)$x]);
		}
		elseif( is_string($x) && $x !== '' ) {
			if( self::$raw_cache ) {
				foreach( self::$raw_cache as $row ) {
					if( $row['name'] == $x ) return self::_load_from_data($row);
				}
			}

			$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->getRow($query,[trim($x)]);
		}

		if( !$row ) throw new LogicException('Could not find design row identified by '.$x);

		// get assigned stylesheets
		$query = 'SELECT css_id FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id = ? ORDER BY css_order';
		$tmp = $db->getCol($query,[(int) $row['id']]);
		if( $tmp ) $row['css'] = $tmp;

		// get assigned templates
		$query = 'SELECT tpl_id FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id = ?';
		$tmp = $db->getCol($query,[(int) $row['id']]);
		if( $tmp ) $row['templates'] = $tmp;

		self::$raw_cache[$row['id']] = $row;
		return self::_load_from_data($row);
	}

	/**
	 * Load all designs
	 *
	 * @param bool $quick Optional flag whether to also load the design's
	 *  templates and stylesheets. Default false.
	 * @return array Each member a Design object
	 */
	public static function get_all($quick = FALSE)
	{
		$out = null;
		$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY name ASC';
		$db = Lone::get('Db');
		$dbr = $db->getArray($query);
		if( $dbr ) {
			$ids = [];
			$cache = [];
			foreach( $dbr as $row ) {
				$ids[] = $row['id'];
				$cache[$row['id']] = $row;
			}

			if( !$quick ) {
				$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::CSSTABLE.' WHERE design_id IN ('.implode(',',$ids).') ORDER BY design_id,css_order';
				$dbr2 = $db->getArray($query);
				if( $dbr2 ) {
					foreach( $dbr2 as $row ) {
						if( !isset($cache[$row['design_id']]) ) continue; // orphaned entry, bad.
						$design = &$cache[$row['design_id']];
						if( !isset($design['css']) ) $design['css'] = [];
						if( !in_array($row['css_id'],$design['css']) ) $design['css'][] = $row['css_id'];
					}
				}

				$query = 'SELECT * FROM '.CMS_DB_PREFIX.self::TPLTABLE.' WHERE design_id IN ('.implode(',',$ids).') ORDER BY design_id,tpl_order';
				$dbr2 = $db->getArray($query);
				if( $dbr2 ) {
					foreach( $dbr2 as $row ) {
						if( !isset($cache[$row['design_id']]) ) continue; // orphaned entry, bad.
						$design = &$cache[$row['design_id']];
						if( !isset($design['templates']) ) $design['templates'] = [];
						$design['templates'][] = $row['tpl_id'];
					}
				}
			}

			self::$raw_cache = $cache;

			$out = [];
			foreach( $cache as $key => $row ) {
				$out[] = self::_load_from_data($row);
			}
			return $out;
		}
	}

	/**
	 * Get all designs
	 *
	 * @return array Each member like id => 'name'
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
	 * @deprecated since 3.0 there is no such thing as a default design
	 *
	 * @throws LogicException
	 * @return null
	 */
	public static function load_default()
	{
/*		$tmp = null;
		if( self::$_dflt_id == '' ) {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE dflt = 1';
			$db = Lone::get('Db');
			$tmp = (int) $db->getOne($query);
			if( $tmp > 0 ) self::$_dflt_id = $tmp;
		}

		if( self::$_dflt_id > 0 ) return self::load(self::$_dflt_id);
		throw new LogicException('There is no default design');
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
		if( $newname == '' ) { $newname = 'New Design'; } // OR translated ?
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

\class_alias(Design::class, 'CmsLayoutCollection', FALSE);
