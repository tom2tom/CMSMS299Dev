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

use cms_config;
use CmsApp;
use CmsInvalidDataException;
use CmsLayoutStylesheet;
use CmsLogicException;
use CMSMS\AdminUtils;
use CMSMS\Events;
use CMSMS\StylesheetsGroup;
use CmsSQLErrorException;
use Exception;
use const CMS_DB_PREFIX;
use function check_permission;
use function cms_join_path;
use function cms_notice;
use function endswith;
use function file_put_contents;
use function get_userid;
use function munge_string_to_url;

/**
 * A class of static methods for dealing with CmsLayoutStylesheet objects.
 *
 * This class is for stylesheet administration. It is not used for runtime stylesheet
 * retrieval, except when a WYSIWWYG is used in an admin page, in which case
 * get_bulk_stylesheets() is called.
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
	* Validate the specified stylesheet.
	* Each stylesheet must have a valid name (unique, only certain characters accepted),
    * and must have at least some content.
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
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$tmp = $db->GetOne($sql,[$sht->get_name(),$sht->get_id()]);
		} else {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$tmp = $db->GetOne($sql,[$sht->get_name()]);
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
		$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.'SET
name = ?,
description = ?,
media_type = ?,
media_query = ?,
contentfile = ?,
WHERE id = ?';
		if( isset($sht->_data['media_type']) ) $tmp = implode(',',$sht->_data['media_type']); //TODO access
		else $tmp = '';
		$sid = $sht->get_id();
		$db = CmsApp::get_instance()->GetDb();
//		$dbr =
		$db->Execute($sql,[
			$sht->get_name(),
			$sht->get_description(),
			$tmp,
			$sht->get_media_query(),
			$sht->get_content_file(),
			$sid
		]);
//USELESS		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());

        if( ($fp = $sht->get_content_filename()) ) {
            file_put_contents($fp,$sht->get_content(),LOCK_EX);
		}
		else {
	        $sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=? WHERE id=?';
	        $db->Execute($sql,[$sht->get_content(),$sid]);
        }
/*
		// get the designs that include the specified stylesheet again.
		$sql = 'SELECT design_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WhERE css_id = ?'; DISABLED
		$design_list = $db->GetCol($sql,[$sid]);
		if( !is_array($design_list) ) $design_list = [];

		// cross reference design_list with $dl ... find designs in this object that aren't already known.
		$dl = $sht->get_designs(); DISABLED
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
			$sql1 = 'SELECT css_order FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ? AND design_id = ?';
			$sql2 = 'UPDATE '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' SET css_order = css_order - 1 WHERE design_id = ? AND css_order > ?';
			$sql3 = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE design_id = ? AND css_id = ?';
			foreach( $del_dl as $design_id ) {
				$design_id = (int)$design_id;
				$css_order = (int)$db->GetOne($sql1,[$sid,$design_id]);
				$dbr = $db->Execute($sql2,[$design_id,$css_order]);
				if( !$dbr ) dir($db->sql.' '.$db->ErrorMsg());
				$dbr = $db->Execute($sql3,[$design_id,$sid]);
				if( !$dbr ) dir($db->sql.' '.$db->ErrorMsg());
			}
		}

		if( $new_dl ) {
			// add new items
			$sql1 = 'SELECT MAX(css_order) FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE design_id = ?';
			$sql2 = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' (css_id,design_id,css_order) VALUES(?,?,?)';
			foreach( $new_dl as $one ) {
				$one = (int)$one;
				$num = (int)$db->GetOne($sql1,[$one])+1;
				$dbr = $db->Execute($sql2,[$sid,$one,$num]);
				if( !$dbr ) die($db->sql.' -- '.$db->ErrorMsg());
			}
		}
*/
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
		$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.'
(name,content,description,media_type,media_query,contentfile)
VALUES (?,?,?,?,?,?)';
		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->Execute($sql,	[
			$sht->get_name(),
			$sht->get_content(), // maybe changed to a filename
			$sht->get_description(),
			$tmp,
			$sht->get_media_query(),
			$sht->get_content_file(),
		]);
		if( !$dbr ) throw new CmsSQLErrorException($db->sql.' -- '.$db->ErrorMsg());
		$sid = $sht->_data['id'] = $db->Insert_ID();

		if( $sht->get_content_file() ) {
			$fn = munge_string_to_url($sht->get_name()).'.'.$sid.'.tpl';
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=? WHERE id=?';
			$db->Execute($sql,[$fn,$sid]);
			$tmp = $sht->get_content();
			$sht->set_content($fn);
			$fp = $sht->get_content_filename();
			file_put_contents($fp,$tmp,LOCK_EX);
		}
/*
		$t = $sht->get_designs(); DISABLED
		if( $t ) {
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' (css_id,design_id) VALUES(?,?)';
			foreach( $t as $one ) {
				$dbr = $db->Execute($sql,[$sid,(int)$one]);
			}
		}
*/
//		global_cache::clear('LayoutStylesheets');
		cms_notice('Stylesheet '.$sht->get_name().' Updated');
	}

   /**
	* Save the specified stylesheet, if it is 'dirty' (has been modified in some way,
    * or has no id)
	*
	* This method sends events before and after saving.
	* EditStylesheetPre is sent before an existing stylesheet is saved to the database
	* EditStylesheetPost is sent after an existing stylesheet is saved to the database
	* AddStylesheetPre is sent before a new stylesheet is saved to the database
	* AddStylesheetPost is sent after a new stylesheet is saved to the database
	*
	* @throws CmsSQLErrorException
	* @throws CmsInvalidDataException
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
	* Delete the specified stylesheet.
	* This method deletes the appropriate records from the database, deletes a
    * content-file if any, deletes the id from the stylesheet object, and marks
    * the object as dirty so it can be saved again.
	*
	* This method triggers the DeleteStylesheetPre and DeleteStylesheetPost events
	*/
	public static function delete_stylesheet(CmsLayoutStylesheet $sht)
	{
		$sid = $sht->get_id();
		if( !$sid ) return;

		Events::SendEvent('Core', 'DeleteStylesheetPre',[get_class($sht)=>&$sht]);
		$db = CmsApp::get_instance()->GetDb();
/*		$sql = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ?'; DesignManager\Design
		$dbr = $db->Execute($sql,[$sid]); // just in case ...
*/
		$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->Execute($sql,[$sid]);

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
//			$sht->_data['create_date'] = N/A from file - keep db value
//			$sht->_data['modified_date'] = filemtime($fn); //TODO as Y-m-d H:i:s c.f. $db->dbTimestamp()
		}
		if( is_array($design_list) ) $sht->_design_assoc = $design_list;

		return $sht;
	}

   /**
	* Get the specified stylesheet
	*
	* @param mixed $a stylesheet identifier, (int|numeric string) id or (other string) name
	* @return CmsLayoutStylesheet
	* @throws CmsInvalidDataException
	*/
	public static function get_stylesheet($a)
	{
		$db = CmsApp::get_instance()->GetDb();
		if( is_numeric($a) && (int)$a > 0 ) {
			$sql = 'SELECT id,name,content,description,media_type,media_query,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->GetRow($sql,[(int)$a]);
		}
		elseif( is_string($a) && $a !== '' ) {
			$sql = 'SELECT id,name,content,description,media_type,media_query,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->GetRow($sql,[$a]);
		}
		else {
			$row = null;
		}
		if( $row ) return self::construct_stylesheet($row);
		throw new CmsInvalidDataException('Could not find stylesheet identified by '.$a);
	}

   /**
	* Get multiple stylesheets
	*
	* This method does not throw exceptions if any requested id or name does not exist.
	*
	* @param array $ids stylesheet identifiers, all of them (int|numeric string) id's or (other string) names
	* @param bool $deep whether or not to load associated data
	* @return mixed array of CmsLayoutStylesheet objects | null
	* @throws CmsInvalidDataException
	*/
	public static function get_bulk_stylesheets($ids,bool $deep = true)
	{
		if( !$ids ) return;

		$db = CmsApp::get_instance()->GetDb();
		// clean up the input data
		if( is_numeric($ids[0]) && (int)$ids[0] > 0 ) {
			$is_ints = TRUE;
			for( $i = 0, $n = count($ids); $i < $n; $i++ ) {
				$ids[$i] = (int)$ids[$i];
			}
			$ids = array_unique($ids);
			$where = ' WHERE id IN ('.implode(',',$ids).')';
		}
		else if( is_string($ids[0]) && $ids[0] !== '' ) {
			$is_ints = false;
			for( $i = 0, $n = count($ids); $i < $n; $i++ ) {
				$ids[$i] = $db->qStr(trim($ids[$i]));
			}
			$ids = array_unique($ids);
			$where = ' WHERE name IN ('.implode(',',$ids).')';
		}
		else {
			// what ??
			throw new CmsInvalidDataException('Invalid data passed to '.__CLASS__.'::'.__METHOD__);
		}

		$sql = 'SELECT id,name,content,description,media_type,media_query,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.$where;
		$dbr = $db->GetArray($sql);
		$out = [];
		if( $dbr ) {
			$designs_by_css = [];
			if( $deep ) {
				$ids2 = [];
				foreach( $dbr as $row ) {
					$ids2[] = $row['id'];
					$designs_by_css[$row['id']] = [];
				}
/*				$dquery = 'SELECT design_id,css_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id IN ('.implode(',',$ids2).') ORDER BY css_id';
				$dbr2 = $db->GetArray($dquery);
				foreach( $dbr2 as $row ) {
					$designs_by_css[$row['css_id']][] = $row['design_id'];
				}
*/
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
					$one = trim($one,"'"); //assume mysqli quotes names like this
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
	* Return numeric id and name for all or specified stylesheets
	* @see also StylesheetOperations::get_all_stylesheets(false);
	*
	* @param mixed $ids array of integer sheet id's, or falsy to process all recorded sheets
	* @return array Each row has a stylesheet id and name
	*/
	public static function get_bulk_sheetsnames($ids = null, $sorted = true) : array
	{
		$db = CmsApp::get_instance()->GetDb();
		$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME;
		if( !empty($ids) ) {
			$sql .= ' WHERE id IN ('.implode(',',$ids).')';
		}
		if( $sorted ) {
			$sql .= ' ORDER BY name';
		}
		return $db->GetArray($sql);
	}

   /**
	* Get all recorded stylesheets
	*
	* @param bool $by_name Optional flag indicating the output format. Default false.
	* @return mixed If $by_name is true then the output will be an array of rows
    *  each with stylesheet id and stylesheet name. Otherwise, id and
    *  CmsLayoutStylesheet object
	*/
	public static function get_all_stylesheets(bool $by_name = false) : array
	{
		$db = CmsApp::get_instance()->GetDb();

		$out = [];
		if( $by_name ) {
			$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified_date DESC';
			return $db->GetAssoc($sql);
		}
		else {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified_date DESC';
			$ids = $db->GetCol($sql);
			return self::get_bulk_stylesheets($ids,false);
		}
	}

   /**
	* Return summary information for all or selected groups.
	*
	* @param mixed $ids array of integer group id's, or falsy to process all recorded groups
	* @return array Each row has a group id, name and comma-separated member id's
	*/
	public static function get_groups_summary($ids = null, $sorted = true)
	{
		$db = CmsApp::get_instance()->GetDb();
		$sql = 'SELECT G.id,G.name,GROUP_CONCAT(M.css_id ORDER BY M.item_order) AS members FROM '.
        CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' G LEFT JOIN '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' M ON G.id = M.group_id';
		if( !empty($ids) ) {
			$sql .= ' WHERE G.id IN ('.implode(',',$ids).')';
		}
		$sql .= ' GROUP BY M.group_id';
		if( $sorted ) {
			$sql .= ' ORDER BY G.name';
		}
		return $db->GetArray($sql);
	}

   /**
	* Return summary information covering groups and individual sheets, for UI display
	*
	* @return array unsorted, each row has a group(<0)|sheet id, name and
	*  (possibly) comma-separated member id's (if any)
	*/
	public static function get_displaylist()
	{
		$grps = self::get_groups_summary(null, false);
		if( $grps ) {
			foreach( $grps as &$row ) {
/*				if( strpos($row['members'],',') === false ) {
					$row = null; //ignore single-member groups
				}
				else {
*/
					$row['id'] = -(int)$row['id']; // group id's < 0
//				}
			}
			unset($row);
//			$grps = array_filter($grps);
		}

		$sheets = self::get_bulk_sheetsnames(null, false);
		if( $sheets ) {
			foreach( $sheets as &$row ) {
				$row['members'] = null;
			}
			unset($row);
		}
		return array_merge($grps, $sheets);
	}

   /**
	* Return a set of groups or group-names sourced from the database
	*
	* @param string $prefix Optional group-name prefix to be matched. Default ''.
	* @param bool   $by_name Optional flag whether to return group names. Default false.
	* @return array of StylesheetsGroup objects or name strings
	*/
	public static function get_bulk_groups($prefix = '', bool $by_name = false)
	{
		$out = [];
		$db = CmsApp::get_instance()->GetDb();
		if( $prefix ) {
			$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE name LIKE ? ORDER BY name';
			$res = $db->GetAssoc($sql,[$prefix.'%']);
		}
		else {
			$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' ORDER BY name';
			$res = $db->GetAssoc($sql);
		}
		if( $res ) {
			if( $by_name ) {
				$out = $res;
			}
			else {
				foreach($res as $id => $name ) {
					$id = (int)$id;
					try {
						$out[$id] = StylesheetsGroup::load($id);
					}
					catch (Exception $e) {
						//ignore
					}
				}
			}
		}
		return $out;
	}

   /**
	* Get the specified stylesheet group
	*
	* @param mixed $a group identifier, (int|numeric string) id or (other string) name
	* @return mixed StylesheetsGroup object | null
	*/
	public static function get_group($a)
	{
		$db = CmsApp::get_instance()->GetDb();
		if( is_numeric($a) && (int)$a > 0 ) {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE id=?';
			$res = $db->GetOne($sql,[(int)$a]);
		}
		elseif( is_string($a) && $a !== '' ) {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE name=?';
			$res = $db->GetOne($sql,[$a]);
		}
		else {
			$res = false;
		}
		if( $res) {
			return StylesheetsGroup::load($res);
		}
	}

   /**
	* Get a unique name for a stylesheet
	*
	* @param string $prototype A prototype stylesheet name
	* @param string $prefix An optional name prefix. Default ''.
	* @return string
	* @throws CmsInvalidDataException
	* @throws CmsLogicException
	*/
	public static function get_unique_name(string $prototype,string $prefix = '') : string
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

	//============= STYLESHEET-OPERATION BACKENDS ============

	/**
	 * Clone stylesheet(s) and/or group(s)
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return int No of stylesheets cloned
	 */
    public static function operation_copy($ids) : int
	{
		$n = 0;
		$db = CmsApp::get_instance()->GetDb();
		list($shts,$grps) = self::items_split($ids);
		if ($shts) {
			$sql = 'SELECT name,content,description,media_type,media_query,contentfile FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($shts)-1).'?)';
			$from = $db->GetArray($sql, $shts);
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,content,description,media_type,media_query,contentfile) VALUES (?,?,?,?,?,?)';
			foreach ($from as $row) {
				if ($row['name']) {
					$row['name'] = self::get_unique_name($row['name']);
				} else {
					$row['name'] = null;
				}
				if ($row['contentfile']) {
					//TODO clone file
					$row['content'] = TODOfunc($row['name']);
				}
				$db->Execute($sql, $row);
			}
			$n = count($from);
		}
		if ($grps) {
			$sql = 'SELECT id,name,description FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$from = $db->GetArray($sql, $grps);
			$sql = 'SELECT group_id,css_id,item_order FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$members = $db->Execute($sql, $grps);
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' (name,description) VALUES (?,?)';
			$sql2 = 'INSERT INTO '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' (group_id,css_id,item_order) VALUES (?,?,?)';
			foreach ($from as $row) {
				if ($row['name']) {
					$name = self::get_unique_name($row['name']);
				} else {
					$name = null;
				}
				$db->Execute($sql, [$name, $row['description']]);
				$to = $db->Insert_ID();
				$from = $row['id'];
				foreach ($members as $grprow) {
					if ($grprow['group_id'] == $from) {
						$db->Execute($sql2, [$to, $grprow['css_id'], $grprow['item_order']]);
					}
				}
			}
			$n += count($from);
		}
		return $n;
	}

	/**
	 * Delete stylesheet(s) and/or group(s) but not group members (unless also specified individually)
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return int No of pages modified
	 */
	public static function operation_delete($ids) : int
	{
		$db = CmsApp::get_instance()->GetDb();
		list($shts,$grps) = self::items_split($ids);
		if ($grps) {
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$db->Execute($sql, $grps);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$db->Execute($sql, $grps);
		}
		if ($shts) {
			list($pages, $skips) = self::affected_pages($shts);
			if ($pages) {
				$sql = 'SELECT content_id,styles FROM '.CMS_DB_PREFIX.' WHERE content_id IN ('.implode(',',$pages).')';
				$rows = $db->GetArray($sql, $spages);
				$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
				foreach($rows as $row) {
					$s = self::filter($row['styles'], $shts);
					$db->Execute($sql, [$row['content_id'],$s]);
				}
				$n = count($rows);
			}
			else {
				$n = 0;
			}
			$sql = 'SELECT DISTINCT styles FROM '.CMS_DB_PREFIX.'content WHERE styles LIKE ('.$TODO.')';
			$keeps = $db->GetCol($sql, $shts);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($shts)-1).'?)';
			if ($keeps) {
				$t = [];
				foreach ($keeps as $s) {
					$tmp = explode(',',$s);
					$t = array_merge($t, array_intersect($shts,$tmp)); //any grp id < 0 will be ignored
				}
				if ($t) {
					$sql .= ' AND id NOT IN ('.implode(',',$t).')';
				}
			}
			$db->Execute($sql, $tpls);

			return $n;
		}
		return 0;
	}

	/**
	 * Delete stylesheet(s) and/or group(s) and group-member(s)
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return int No of pages modified
	 */
	public static function operation_deleteall($ids) : int
	{
		$db = CmsApp::get_instance()->GetDb();
		list($shts,$grps) = self::items_split($ids);
		if ($grps) {
			$sql = 'SELECT DISTINCT tpl_id FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$members = $db->GetCol($sql, $grps);
			$shts = array_unique(array_merge($shts, $members));
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$db->Execute($sql, $grps);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($grps)-1).'?)';
			$db->Execute($sql, $grps);
		}
		if ($shts) {
			list($pages, $skips) = self::affected_pages($shts);
			if ($pages) {
				$sql = 'SELECT content_id,styles FROM '.CMS_DB_PREFIX.' WHERE content_id IN ('.implode(',',$pages).')';
				$rows = $db->GetArray($sql, $pages);
				$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
				foreach ($rows as $row) {
					$s = self::filter($row['styles'], $shts);
					$db->Execute($sql, [$row['content_id'],$s]);
				}
				$n = count($rows);
			}
			else {
				$n = 0;
			}
			$sql = 'SELECT DISTINCT styles FROM '.CMS_DB_PREFIX.'content WHERE styles LIKE ('.$TODO.')';
			$keeps = $db->GetCol($sql, $shts);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,',count($shts)-1).'?)';
			if ($keeps) {
				$t = [];
				foreach ($keeps as $s) {
					$tmp = explode(',',$s);
					$t = array_merge($t, array_intersect($shts,$tmp)); //any grp id < 0 will be ignored
				}
				if ($t) {
					$sql .= ' AND id NOT IN ('.implode(',',$t).')';
				}
			}
			$db->Execute($sql, $shts);

			return $n;
		}
		return 0;
	}

	/**
	 * Replace the stylesheet wherever used and the user is authorized
	 * @param mixed $from int|int[] stylesheet identifier(s), <0 for a group
	 * @param mixed $to int|int[] stylesheet identifier(s), <0 for a group
	 * @return int No of pages modified
	 */
	public static function operation_replace($from, $to) : int
	{
		list($pages, $skips) = self::affected_pages($from);
		if ($pages) {
			$db = CmsApp::get_instance()->GetDb();
			$sql = 'SELECT content_id,styles FROM '.CMS_DB_PREFIX.' WHERE content_id IN ('.implode(',',$pages).')';
			$rows = $db->GetArray($sql, $pages);
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
			foreach($rows as $row) {
				$s = self::filter2($row['styles'], $from, $to);
				$db->Execute($sql, [$row['content_id'], $s]);
			}
			return count($pages);
		}
		return 0;
	}

	/**
	 * Append stylesheet(s) to all pages where the user is authorized
	 * @param mixed $ids int|int[] stylesheet identifier(s), < 0 for a group
	 * @return int No of pages modified
	 */
	public static function operation_append($ids) : int
	{
		list($pages, $skips) = self::affected_pages('*');
		if ($pages) {
			$db = CmsApp::get_instance()->GetDb();
			$sql = 'SELECT content_id,styles FROM '.CMS_DB_PREFIX.' WHERE content_id IN ('.implode(',',$pages).')';
			$rows = $db->GetArray($sql, $pages);
			$to = ','.implode(',',$ids);
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
			foreach($rows as $row) {
				$s = self::filter($row['styles'], $ids);
				$s = trim($s.$to,' ,');
				$db->Execute($sql, [$row['content_id'],$s]);
			}
			return count($rows);
		}
		return 0;
	}

	/**
	 * Prepend stylesheet(s) to all pages where the user is authorized
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 for a group
	 * @return int No of pages modified
	 */
	public static function operation_prepend($ids) : int
	{
		list($pages, $skips) = self::affected_pages('*');
		if ($pages) {
			$db = CmsApp::get_instance()->GetDb();
			$sql = 'SELECT content_id,styles FROM '.CMS_DB_PREFIX.' WHERE content_id IN ('.implode(',',$pages).')';
			$rows = $db->GetArray($sql, $pages);
			$to = implode(',',$ids).',';
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
			foreach($rows as $row) {
				$s = self::filter($row['styles'], $ids);
				$s = trim($to.$s,' ,');
				$db->Execute($sql, [$row['content_id'],$s]);
			}
			return count($pages);
		}
		return 0;
	}

	/**
	 * Remove stylesheet(s) from all pages where the user is authorized
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 for a group
	 * @return int No of pages modified
	 */
	public static function operation_remove($ids) : int
	{
		list($pages, $skips) = self::affected_pages('*');
		if ($pages) {
			$db = CmsApp::get_instance()->GetDb();
			$sql = 'SELECT content_id,styles FROM '.CMS_DB_PREFIX.' WHERE content_id IN ('.implode(',',$pages).')';
			$rows = $db->GetArray($sql, $pages);
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
			foreach($rows as $row) {
				$s = self::filter($row['styles'], $ids);
				$db->Execute($sql, [$row['content_id'],$s]);
			}
			return count($rows);
		}
		return 0;
	}

	/**
	 * Migrate stylesheet(s) from database storage to file
	 * @param mixed $ids int | int[] stylesheet identifier(s)
	 * @return int No. of files processed
	 */
	public static function operation_export($ids) : int
	{
		$n = 0;
		list($shts,$grps) = self::items_split($ids);
		if ($shts) {
			$db = CmsApp::get_instance()->GetDb();
			$sql = 'SELECT id,name,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE contentfile=0 AND id IN ('.str_repeat('?,',count($shts)-1).'?)';
			$from = $db->GetArray($sql, $shts);
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=?,contentfile=1 WHERE id=?';
			$config = cms_config::get_instance();
			foreach ($from as $row) {
				if ($row['name']) {
					//replicate object::set_content_file()
					$fn = munge_string_to_url($row['name']).'.'.$row['id'].'.css';
					//replicate object::get_content_filename()
					$outfile = cms_join_path($config['assets_path'],'css',$fn);
					$res = file_put_contents($outfile,$row['content'],LOCK_EX);
					if ($res !== false) {
						$db->Execute($sql, [$fn,$row['id']]);
						++$n;
					} else {
						//some signal needed
					}
				}
			}
		}
		return $n;
	}

	/**
	 * Migrate stylesheet(s) from file storage to database
	 * @param mixed $ids int | int[] stylesheet identifier(s)
	 * @return int No. of files processed
	 */
	public static function operation_import($ids) : int
	{
		$n = 0;
		list($shts,$grps) = self::items_split($ids);
		if ($shts) {
			$db = CmsApp::get_instance()->GetDb();
			$sql = 'SELECT id,name,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE contentfile=1 AND id IN ('.str_repeat('?,',count($shts)-1).'?)';
			$from = $db->GetArray($sql, $shts);
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=?,contentfile=0 WHERE id=?';
			$config = cms_config::get_instance();
			foreach ($from as $row) {
				if ($row['name']) {
					//replicate object::set_content_file()
					$fn = munge_string_to_url($row['name']).'.'.$row['id'].'.css';
					//replicate object::get_content_filename()
					$outfile = cms_join_path($config['assets_path'],'css',$fn);
					$content = file_get_contents($outfile);
					if ($content !== false) {
						$db->Execute($sql, [$content,$row['id']]);
						++$n;
					} else {
						//some signal needed
					}
				}
			}
		}
		return $n;
	}

	/**
	 * @ignore
	 */
	protected static function affected_pages($ids)
	{
		$uid = get_userid();
		$modify_all = check_permission($uid,'Manage All Content') || check_permission($uid,'Modify Any Page');
		$sql = 'SELECT content_id,styles FROM '.CMS_DB_PREFIX.'content WHERE styles LIKE ';
		$fillers = (is_array($ids)) ? '('.str_repeat('%?% OR ',count($ids)-1).'%?%)' : '%?%';
		$sql .= $fillers;
		$args = (is_array($ids)) ? $ids : [$ids];
		if (!$modify_all) {
			$sql .= ' AND owner_id=?';
			$args[] = $uid;
		}
        $db = CmsApp::get_instance()->GetDb();
		$valid = $db->getArray($sql, $args);

		if (!$modify_all) {
			$sql = 'SELECT COUNT(1) AS num FROM '.CMS_DB_PREFIX.'content WHERE styles LIKE '.$fillers;
			$args = (is_array($ids)) ? $ids : [$ids];
			$all = $db->getOne($sql, $args);
			$other = $all - count($valid);
		} else {
			$other = 0;
		}
		return [$valid, $other];
	}

	/**
	 * @ignore
	 */
	protected static function items_split($ids)
	{
		$sngl = [];
		$grp = [];
		if(is_array($ids)) {
			foreach ($ids as $id) {
				if ($id > 0) {
					$sngl[] = $id;
				} else {
					$grp[] = -$id;
				}
			}
		} elseif ($ids > 0) {
			$sngl[] = $ids;
		} else {
			$grp[] = -$ids;
		}
		return [$sngl, $grp];
	}

	/**
	 * @ignore
	 * @return string
	 */
	protected static function filter(string $s, $ids) : string
	{
		$tmp = explode(',', $s);
		if (!is_array($ids)) {
			$ids = [$ids];
		}
		$tmp = array_diff($tmp, $ids);
		return implode(',', $tmp);
	}

	/**
	 * @ignore
	 * @return string
	 */
	protected static function filter2(string $s, $from, $to) : string
	{
		if (!is_array($from)) {
			$from = [$from];
		}
		if (!is_array($to)) {
			$to = [$to];
		}
		$tmp = explode(',', $s);
		foreach ($tmp as &$one) {
			if (($p = array_search($one, $from)) !== false) {
				if (isset($to[$p])) {
					$one = $to[$p];
				}
			}
		}
		unset($one);
		return implode(',', $tmp);
	}
} //class
