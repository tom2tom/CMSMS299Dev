<?php
#Methods for administering stylesheet objects
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
use CmsInvalidDataException;
use CmsLayoutCollection;
use CmsLayoutStylesheet;
use CmsLogicException;
use CMSMS\AdminUtils;
use CMSMS\Events;
use CmsSQLErrorException;
use const CMS_DB_PREFIX;
use function cms_notice;
use function endswith;

/**
 * A class of static methods for dealing with CmsLayoutStylesheet objects.
 * This class is for stylesheet administration, by DesignManager module
 * and the like. It is not used for runtime stylesheet retrieval (except when WYSIWWYG wanted ?).
 *
 * @since 2.3
 * @package CMS
 * @license GPL
 */
class StylesheetOperations
{
   /**
	* @ignore
	*/
	const TABLENAME = 'layout_stylesheets';

   /**
	* Validate the specified stylesheet object for suitability for saving to the database
	* Stylesheet objects must have a valid name (only certain characters accepted, and must have at least some css content)
	*
	* @throws CmsInvalidDataException
	*/
	protected static function validate_stylesheet($sht)
	{
		if( !$sht->get_name() ) throw new CmsInvalidDataException('Each stylesheet must have a name');
		if( endswith($sht->get_name(),'.css') ) throw new CmsInvalidDataException('Invalid name for a stylesheet');
		if( !AdminUtils::is_valid_itemname($sht->get_name()) ) {
			throw new CmsInvalidDataException('There are invalid characters in the stylesheet name.');
		}
		if( !$sht->get_content() ) throw new CmsInvalidDataException('Each stylesheet must have some content');

		$db = CmsApp::get_instance()->GetDb();
		// double check the name
		if( $sht->get_id() ) {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$tmp = $db->GetOne($query,[$sht->get_name(),$sht->get_id()]);
		} else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$tmp = $db->GetOne($query,[$sht->get_name()]);
		}
		if( $tmp ) {
			throw new CmsInvalidDataException('Stylesheet with the same name already exists.');
		}
	}

   /**
	* @ignore
	*/
	protected static function update_stylesheet($sht)
	{
		$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.'SET
name = ?,
content = ?,
description = ?,
media_type = ?,
media_query = ?,
contentfile = ?,
modified = ?
WHERE id = ?';
		if( isset($sht->_data['media_type']) ) $tmp = implode(',',$sht->_data['media_type']);
		else $tmp = '';
		$sid = $sht->get_id();
		$db = CmsApp::get_instance()->GetDb();
//		$dbr =
		$db->Execute($query,[
			$sht->get_name(),
			$sht->get_content(),
			$sht->get_description(),
			$tmp,
			$sht->get_media_query(),
			$sht->get_content_file(),
			time(),
			$sid
		]);
//USELESS		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

		// get the designs that have the specified stylesheet from the database again.
		$query = 'SELECT design_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WhERE css_id = ?';
		$design_list = $db->GetCol($query,[$sid]);
		if( !is_array($design_list) ) $design_list = [];

		// cross reference design_list with $dl ... find designs in this object that aren't already known.
		$dl = $sht->get_designs();
		$new_dl = [];
		$del_dl = [];
		foreach( $dl as $one ) {
			if( !in_array($one,$design_list) ) $new_dl[] = $one;
		}
		foreach( $design_list as $one ) {
			if( !in_array($one,$dl) ) $del_dl[] = $one;
		}

		if( $del_dl ) {
			// delete deleted items
			$query1 = 'SELECT item_order FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ? AND design_id = ?';
			$query2 = 'UPDATE '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' SET item_order = item_order - 1 WHERE design_id = ? AND item_order > ?';
			$query3 = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE design_id = ? AND css_id = ?';
			foreach( $del_dl as $design_id ) {
				$design_id = (int)$design_id;
				$item_order = (int)$db->GetOne($query1,[$sid,$design_id]);
				$dbr = $db->Execute($query2,[$design_id,$item_order]);
				if( !$dbr ) dir($db->sql.' '.$db->ErrorMsg());
				$dbr = $db->Execute($query3,[$design_id,$sid]);
				if( !$dbr ) dir($db->sql.' '.$db->ErrorMsg());
			}
		}

		if( $new_dl ) {
			// add new items
			$query1 = 'SELECT MAX(item_order) FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE design_id = ?';
			$query2 = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' (css_id,design_id,item_order) VALUES(?,?,?)';
			foreach( $new_dl as $one ) {
				$one = (int)$one;
				$num = (int)$db->GetOne($query1,[$one])+1;
				$dbr = $db->Execute($query2,[$sid,$one,$num]);
				if( !$dbr ) die($db->sql.' -- '.$db->ErrorMsg());
			}
		}

//		global_cache::clear('LayoutStylesheets');
		cms_notice('Stylesheet '.$sht->get_name().' Updated');
	}

   /**
	* @ignore
	*/
	protected static function insert_stylesheet($sht)
	{
		$now = time();
		// insert the record
		$tmp = '';
		if( isset($sht->_data['media_type']) ) $tmp = implode(',',$sht->_data['media_type']);
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.
' (name,content,description,media_type,media_query,contentfile,created,modified)
VALUES (?,?,?,?,?,?,?,?)';
		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->Execute($query,	[
			$sht->get_name(),
			$sht->get_content(),
			$sht->get_description(),
			$tmp,
			$sht->get_media_query(),
			$sht->get_content_file(),
			$now,
			$now,
		]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$sid = $sht->_data['id'] = $db->Insert_ID();

		$t = $sht->get_designs();
		if( $t ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' (css_id,design_id) VALUES(?,?)';
			foreach( $t as $one ) {
				$dbr = $db->Execute($query,[$sid,(int)$one]);
			}
		}

//		global_cache::clear('LayoutStylesheets');
		cms_notice('Stylesheet '.$sht->get_name().' Updated');
	}

   /**
	* Save the specified stylesheet to the database
	* Objects are only saved if they are dirty (have been modified in some way, or have no id)
	*
	* This method sends events before and after saving.
	* EditStylesheetPre is sent before an existing stylesheet is saved to the database
	* EditStylesheetPost is sent after an existing stylesheet is saved to the database
	* AddStylesheetPre is sent before a new stylesheet is saved to the database
	* AddStylesheetPost is sent after a new stylesheet is saved to the database
	*
	* @throws CmsSQLErrorException
	*/
	public static function save_stylesheet(CmsLayoutStylesheet $sht)
	{
		self::validate_stylesheet($sht);

		if( $sht->get_id() ) {
			Events::SendEvent('Core', 'EditStylesheetPre',[get_class($sht)=>&$sht]);
			self::update_stylesheet($sht);
			Events::SendEvent('Core', 'EditStylesheetPost',[get_class($sht)=>&$sht]);
		}
		else {
			Events::SendEvent('Core', 'AddStylesheetPre',[get_class($sht)=>&$sht]);
			self::insert_stylesheet($sht);
			Events::SendEvent('Core', 'AddStylesheetPost',[get_class($sht)=>&$sht]);
		}
	}

   /**
	* Delete the specified stylesheet object from the database (and the associated file, if any)
	* This method deletes the appropriate records from the database,
	* deletes the id from this object, and marks the object as dirty so that it can be saved again
	*
	* This method triggers the DeleteStylesheetPre and DeleteStylesheetPost events
	*/
	public static function delete_stylesheet(CmsLayoutStylesheet $sht)
	{
		$sid = $sht->get_id();
		if( !$sid ) return;

		Events::SendEvent('Core', 'DeleteStylesheetPre',[get_class($sht)=>&$sht]);
		$db = CmsApp::get_instance()->GetDb();
		$query = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ?';
		$dbr = $db->Execute($query,[$sid]);

		$query = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->Execute($query,[$sid]);

		@unlink($sht->get_content_filename());

//		global_cache::clear('LayoutStylesheets');
		cms_notice('Stylesheet '.$sht->get_name().' Deleted');
		Events::SendEvent('Core', 'DeleteStylesheetPost',[get_class($sht)=>&$sht]);
	}

   /**
 	* @ignore
	* @param array $row
	* @param mixed $design_list Optional array|null Default null
	* @return CmsLayoutStylesheet
	*/
	protected static function construct_stylesheet(array $row, $design_list = null) : CmsLayoutStylesheet
	{
		$sht = new CmsLayoutStylesheet();
		$row['media_type'] = explode(',',$row['media_type']);;
		$sht->_data = $row;
		$fn = $sht->get_content_filename();
		if( is_file($fn) && is_readable($fn) ) {
			$sht->_data['content'] = file_get_contents($fn);
			$sht->_data['modified'] = filemtime($fn);
		}
		if( is_array($design_list) ) $sht->_design_assoc = $design_list;

		return $sht;
	}

   /**
	* Get the specified stylesheet object
	*
	* @param mixed $a Either an integer stylesheet id, or a string stylesheet name.
	* @return CmsLayoutStylesheet
	* @throws CmsInvalidDataException
	*/
	public static function get_stylesheet($a)
	{
		$db = CmsApp::get_instance()->GetDb();
		$row = null;
		if( is_numeric($a) && (int)$a > 0 ) {
			$query = 'SELECT id,name,content,description,media_type,media_query,created,modified FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->GetRow($query,[(int)$a]);
		}
		elseif( is_string($a) && $a !== '' ) {
			$query = 'SELECT id,name,content,description,media_type,media_query,created,modified FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->GetRow($query,[$a]);
		}
		if( $row ) return self::construct_stylesheet($row);
		throw new CmsInvalidDataException('Could not find stylesheet identified by '.$a);
	}

   /**
	* Get multiple stylesheets
	*
	* This method does not throw exceptions if one requested id, or name does not exist.
	*
	* @param array $ids Array of integer stylesheet ids or an array of string stylesheet names.
	* @param bool $deep whether or not to load associated data
	* @return array Array of CmsLayoutStylesheet objects
	* @throws CmsInvalidDataException
	*/
	public static function get_bulk_stylesheets($ids,$deep = true)
	{
		if( !$ids ) return;

		// clean up the input data
		$is_ints = FALSE;
		if( is_numeric($ids[0]) && (int)$ids[0] > 0 ) {
			$is_ints = TRUE;
			for( $i = 0, $n = count($ids); $i < $n; $i++ ) {
				$ids[$i] = (int)$ids[$i];
			}
		}
		else if( is_string($ids[0]) && $ids[0] !== '' ) {
			for( $i = 0, $n = count($ids); $i < $n; $i++ ) {
				$ids[$i] = "'".trim($ids[$i])."'";
			}
		}
		else {
			// what ??
			throw new CmsInvalidDataException('Invalid data passed to '.__CLASS__.'::'.__METHOD__);
		}
		$ids = array_unique($ids);

		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT id,name,content,description,media_type,media_query,created,modified FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.implode(',',$ids).')';
		if( !$is_ints ) $query = 'SELECT id,name,content,description,media_type,media_query,created,modified FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name IN ('.implode(',',$ids).')';

		$dbr = $db->GetArray($query);
		$out = [];
		if( $dbr ) {
			$designs_by_css = [];
			if( $deep ) {
				$ids2 = [];
				foreach( $dbr as $row ) {
					$ids2[] = $row['id'];
					$designs_by_css[$row['id']] = [];
				}
				$dquery = 'SELECT design_id,css_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id IN ('.implode(',',$ids2).') ORDER BY css_id';
				$dbr2 = $db->GetArray($dquery);
				foreach( $dbr2 as $row ) {
					$designs_by_css[$row['css_id']][] = $row['design_id'];
				}
			}

			// this makes sure that the returned array matches the order specified.
			foreach( $ids as $one ) {
				$found = null;
				if( $is_ints ) {
					// find item in $dbr by id
					foreach( $dbr as $row ) {
						if( $row['id'] == $one ) {
							$found = $row;
							break;
						}
					}
				}
				else {
					$one = trim($one,"'");
					// find item in $dbr by name
					foreach( $dbr as $row ) {
						if( $row['name'] == $one ) {
							$found = $row;
							break;
						}
					}
				}

				$id = $found['id'];
				$tmp = self::construct_stylesheet($found,($designs_by_css[$id] ?? null));
				if( is_object($tmp) ) $out[] = $tmp;
			}
		}

		if( $out ) return $out;
	}

   /**
	* Get all stylesheet objects
	*
	* @param bool $as_list a flag indicating the output format
	* @return mixed If $as_list is true then the output will be an associated array of stylesheet id and stylesheet name suitable for use in an html select element
	*   otherwise, an array of CmsLayoutStylesheet objects is returned
	*/
	public static function get_all_stylesheets($as_list = FALSE)
	{
		$db = CmsApp::get_instance()->GetDb();

		$out = [];
		if( $as_list ) {
			$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified DESC';
			$dbr = $db->GetArray($query);
			foreach( $dbr as $row ) {
				$out[$row['id']] = $row['name'];
			}
			return $out;
		}
		else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified DESC';
			$ids = $db->GetCol($query);
			return self::load_bulk($ids,FALSE);
		}
	}

   /**
	* Get a unique name for a stylesheet
	*
	* @param string $prototype A prototype stylesheet name
	* @param string $prefix An optional name prefix
	* @return string
	* @throws CmsInvalidDataException
	* @throws CmsLogicException
	*/
	public static function get_unique_name($prototype,$prefix = '')
	{
		if( !$prototype ) throw new CmsInvalidDataException('Prototype name cannot be empty');

		$db = CmsApp::get_instance()->GetDb();
		$sql = 'SELECT name FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name LIKE %?%';
		$all = $db->GetCol($sql,[ $prototype ]);
		if( $all ) {
			$name = $prototype;
			$i = 0;
			while( in_array($name, $all) ) {
				$name = $prefix.$prototype.'_'.++$i;
			}
			return $name;
		}
		return $prototype;
	}
} //class
