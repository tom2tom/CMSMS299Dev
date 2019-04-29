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

//use CmsLayoutCollection;
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
use function cms_notice;
use function endswith;
use function file_put_contents;
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
		$db->Execute($query,[
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
	        $query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=? WHERE id=?';
	        $db->Execute($query,[$sht->get_content(),$sid]);
        }
/*
		// get the designs that include the specified stylesheet again.
		$query = 'SELECT design_id FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WhERE css_id = ?'; DISABLED
		$design_list = $db->GetCol($query,[$sid]);
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
			$query1 = 'SELECT css_order FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ? AND design_id = ?';
			$query2 = 'UPDATE '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' SET css_order = css_order - 1 WHERE design_id = ? AND css_order > ?';
			$query3 = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE design_id = ? AND css_id = ?';
			foreach( $del_dl as $design_id ) {
				$design_id = (int)$design_id;
				$css_order = (int)$db->GetOne($query1,[$sid,$design_id]);
				$dbr = $db->Execute($query2,[$design_id,$css_order]);
				if( !$dbr ) dir($db->sql.' '.$db->ErrorMsg());
				$dbr = $db->Execute($query3,[$design_id,$sid]);
				if( !$dbr ) dir($db->sql.' '.$db->ErrorMsg());
			}
		}

		if( $new_dl ) {
			// add new items
			$query1 = 'SELECT MAX(css_order) FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE design_id = ?';
			$query2 = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' (css_id,design_id,css_order) VALUES(?,?,?)';
			foreach( $new_dl as $one ) {
				$one = (int)$one;
				$num = (int)$db->GetOne($query1,[$one])+1;
				$dbr = $db->Execute($query2,[$sid,$one,$num]);
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
		$query = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.'
(name,content,description,media_type,media_query,contentfile)
VALUES (?,?,?,?,?,?)';
		$db = CmsApp::get_instance()->GetDb();
		$dbr = $db->Execute($query,	[
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
			$query = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=? WHERE id=?';
			$db->Execute($query,[$fn,$sid]);
			$tmp = $sht->get_content();
			$sht->set_content($fn);
			$fp = $sht->get_content_filename();
			file_put_contents($fp,$tmp,LOCK_EX);
		}
/*
		$t = $sht->get_designs(); DISABLED
		if( $t ) {
			$query = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' (css_id,design_id) VALUES(?,?)';
			foreach( $t as $one ) {
				$dbr = $db->Execute($query,[$sid,(int)$one]);
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
/*		$query = 'DELETE FROM '.CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE.' WHERE css_id = ?'; DesignManager\Design
		$dbr = $db->Execute($query,[$sid]); // just in case ...
*/
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
			$query = 'SELECT id,name,content,description,media_type,media_query,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->GetRow($query,[(int)$a]);
		}
		elseif( is_string($a) && $a !== '' ) {
			$query = 'SELECT id,name,content,description,media_type,media_query,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$row = $db->GetRow($query,[$a]);
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

		$query = 'SELECT id,name,content,description,media_type,media_query,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.$where;
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
		$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME;
		if( !empty($ids) ) {
			$query .= ' WHERE id IN ('.implode(',',$ids).')';
		}
		if( $sorted ) {
			$query .= ' ORDER BY name';
		}
		return $db->GetArray($query);
	}

   /**
	* Get all recorded stylesheets
	*
	* @param bool $as_list a flag indicating the output format
	* @return mixed If $as_list is true then the output will be an array of rows
    *  each with stylesheet id and stylesheet name. Otherwise, id and
    *  CmsLayoutStylesheet object
	*/
	public static function get_all_stylesheets(bool $as_list = false) : array
	{
		$db = CmsApp::get_instance()->GetDb();

		$out = [];
		if( $as_list ) {
			$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified_date DESC';
			return $db->GetAssoc($query);
		}
		else {
			$query = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY modified_date DESC';
			$ids = $db->GetCol($query);
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
		$query = 'SELECT G.id,G.name,GROUP_CONCAT(M.css_id ORDER BY M.item_order) AS members FROM '.
        CMS_DB_PREFIX.self::TABLENAME.' G LEFT JOIN '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' M ON G.id = M.category_id';
		if( !empty($ids) ) {
			$query .= ' WHERE G.id IN ('.implode(',',$ids).')';
		}
		$query .= ' GROUP BY M.category_id';
		if( $sorted ) {
			$query .= ' ORDER BY G.name';
		}
		return $db->GetArray($query);
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
	* @param string $prefix An optional group-name prefix to be matched. Default ''.
	* @param bool   $as_list Whether to return group names. Default false
	* @return array of StylesheetsGroup objects or name strings
	*/
	public static function get_bulk_groups($prefix = '', bool $as_list = false)
	{
		$out = [];
		$db = CmsApp::get_instance()->GetDb();
		if( $prefix ) {
			$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE name LIKE ? ORDER BY name';
			$res = $db->GetAssoc($query,[$prefix.'%']);
		}
		else {
			$query = 'SELECT id,name FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' ORDER BY name';
			$res = $db->GetAssoc($query);
		}
		if( $res ) {
			if( $as_list ) {
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
	* Get a unique name for a stylesheet
	*
	* @param string $prototype A prototype stylesheet name
	* @param string $prefix An optional name prefix
	* @return string
	* @throws CmsInvalidDataException
	* @throws CmsLogicException
	*/
	public static function get_unique_name(string $prototype,string $prefix = '') : string
	{
		if( !$prototype ) throw new CmsInvalidDataException('Prototype name cannot be empty');

		$db = CmsApp::get_instance()->GetDb();
		$query = 'SELECT name FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name LIKE %?%';
		$all = $db->GetCol($query,[ $prototype ]);
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
	 * @since 2.3
	 * @param int $id stylesheet identifier, < 0 means a group
	 * @return type Description
	 *
	 */
	public static function operation_copy(int $id)
	{
/*
if( !isset($_REQUEST['css']) ) {
	$themeObject->ParkNotice('error',lang_by_realm('layout','error_missingparam'));
	redirect('liststyles.php'.$urlext);
}

cleanArray($_REQUEST);

try {
	$orig_css = StylesheetOperations::get_stylesheet($_REQUEST['css']);
	if( isset($_REQUEST['dosubmit']) || isset($_REQUEST['apply']) ) {
		try {
			$new_css = clone($orig_css);
			$new_css->set_name(trim($_REQUEST['new_name']));
			$new_css->set_designs([]);
			$new_css->save();

			if( isset($_REQUEST['apply']) ) {
				$themeObject->ParkNotice('info',lang_by_realm('layout','msg_stylesheet_copied_edit'));
				redirect('editstylesheet.php'.$urlext.'&css='.$new_css->get_id());
			}
			else {
				$themeObject->ParkNotice('info',lang_by_realm('layout','msg_stylesheet_copied'));
				redirect('liststyles.php'.$urlext);
			}
		}
		catch( Exception $e ) {
			$themeObject->RecordNotice('error',$e->GetMessage());
		}
	}

	$selfurl = basename(__FILE__);

	// build a display
	$smarty = CmsApp::get_instance()->GetSmarty();
	$smarty->assign('css',$orig_css)
	 ->assign('selfurl',$selfurl)
	 ->assign('urlext',$urlext);

	include_once 'header.php';
	$smarty->display('copystylesheet.tpl');
	include_once 'footer.php';
}
catch( CmsException $e ) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('liststyles.php'.$urlext);
}
 */
	}

	/**
	 * @since 2.3
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return type Description
	 */
	public static function operation_delete($ids)
	{
		if (is_array($ids) ) {

		} else {

		}
/*
try {
	if( !isset($_REQUEST['css']) ) throw new CmsException(lang_by_realm('layout','error_missingparam'));

	$css_ob = StylesheetOperations::get_stylesheet($_REQUEST['css']);

	if( isset($_REQUEST['dosubmit']) ) {
		if( !isset($_REQUEST['check1']) || !isset($_REQUEST['check2']) ) {
			$themeObject->RecordNotice('error',lang_by_realm('layout','error_notconfirmed'));
		}
		else {
			$css_ob->delete();
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_stylesheet_deleted'));
			redirect('liststyles.php'.$urlext);
		}
	}

	$selfurl = basename(__FILE__);

	$smarty = CmsApp::get_instance()->GetSmarty();
	$smarty->assign('css',$css_ob)
	 ->assign('selfurl',$selfurl)
	 ->assign('urlext',$urlext);

	include_once 'header.php';
	$smarty->display('deletestylesheet.tpl');
	include_once 'footer.php';
}
catch( CmsException $e ) {
	$themeObject->ParkNotice('error',$e->GetMessage());
	redirect('liststyles.php'.$urlext);
}
*/
	}

	/**
	 * @since 2.3
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return type Description
	 */
	public static function operation_deleteall($ids)
	{
		if (is_array($ids) ) {

		} else {

		}
	}

	/**
	 * @since 2.3
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return type Description
	 */
	public static function operation_replace($ids)
	{
		if (is_array($ids) ) {

		} else {

		}
	}

	/**
	 * @since 2.3
	 * @param int $id stylesheet identifier, < 0 means a group
	 * @return type Description
	 */
 	public static function operation_append(int $id)
	{
	}

	/**
	 * @since 2.3
	 * @param int $id stylesheet identifier, < 0 means a group
	 * @return type Description
	 */
 	public static function operation_prepend(int $id)
	{

	}

	/**
	 * @since 2.3
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return type Description
	 */
 	public static function operation_remove($ids)
	{
		if (is_array($ids) ) {

		} else {

		}
	}
/*
	/**
	 * @since 2.3
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return type Description
	 * /
  	public static function operation_export($ids)
	{
		$bulk_op = 'bulk_action_export_css';
		$first_css = $stylesheets[0];
		$outfile = $first_css->get_content_filename();
		$dn = dirname($outfile);
		if( !is_dir($dn) || !is_writable($dn) ) {
			throw new RuntimeException(lang_by_realm('layout','error_assets_writeperm'));
		}
		if( isset($_REQUEST['dosubmit']) ) {
			$n = 0;
			foreach( $stylesheets as $one ) {
				if( in_array($one->get_id(),$_REQUEST['css_select']) ) {
					$outfile = $one->get_content_filename();
					if( !is_file($outfile) ) {
						file_put_contents($outfile,$one->get_content());
						$n++;
					}
				}
			}
			if( $n == 0 ) throw new RuntimeException(lang_by_realm('layout','error_bulkexport_noneprocessed'));

			audit('','Exported',count($stylesheets).' stylesheets');
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_bulkop_complete'));
			redirect('liststyles.php'.$urlext);
		}
	}

	/**
	 * @since 2.3
	 * @param mixed $ids int | int[] stylesheet identifier(s), < 0 means a group
	 * @return type Description
	 * /
 	public static function operation_import($ids)
	{
		$bulk_op = 'bulk_action_import_css';
		if( isset($_REQUEST['dosubmit']) ) {
			$n=0;
			foreach( $stylesheets as $one ) {
				if( in_array($one->get_id(),$_REQUEST['css_select']) ) {
					$infile = $one->get_content_filename();
					if( is_file($infile) && is_readable($infile) && is_writable($infile) ) {
						$data = file_get_contents($infile);
						$one->set_content($data);
						$one->save();
						unlink($infile);
						$n++;
					}
				}
			}
			if( $n == 0 ) throw new RuntimeException(lang_by_realm('layout','error_bulkimport_noneprocessed'));

			audit('','Imported',count($stylesheets).' stylesheets');
			$themeObject->ParkNotice('info',lang_by_realm('layout','msg_bulkop_complete'));
			redirect('liststyles.php'.$urlext);
		}
	}
*/
} //class
