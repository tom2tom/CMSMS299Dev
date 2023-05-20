<?php
/*
Methods for administering stylesheet objects
Copyright (C) 2019-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace CMSMS;

use CMSMS\AdminUtils;
use CMSMS\DataException;
use CMSMS\Events;
use CMSMS\Lone;
use CMSMS\SQLException;
use CMSMS\Stylesheet;
use CMSMS\StylesheetsGroup;
use LogicException;
use Throwable;
use UnexpectedValueException;
use const CMS_ASSETS_PATH;
use const CMS_DB_PREFIX;
use const CMSSAN_FILE;
use function check_permission;
use function cms_join_path;
use function CMSMS\log_info;
use function CMSMS\log_notice;
use function CMSMS\sanitizeVal;
use function endswith;
use function file_put_contents;
use function get_userid;

/**
 * A class of static methods for dealing with Stylesheet objects.
 *
 * This class is for stylesheet administration. It is not used for runtime
 * stylesheet retrieval, except when a WYSIWWYG is used in an admin page,
 * in which case get_bulk_stylesheets() is called.
 *
 * @since 3.0
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
	 * @throws DataException or UnexpectedValueException
	 */
	public static function validate_stylesheet($sht)
	{
		if (!$sht->get_name()) {
			throw new DataException('Each stylesheet must have a name');
		}
		if (endswith($sht->get_name(), '.css')) {
			throw new UnexpectedValueException('Invalid name for a stylesheet');
		}
		if (!AdminUtils::is_valid_itemname($sht->get_name())) {
			throw new UnexpectedValueException('There are invalid characters in the stylesheet name.');
		}
		if (!$sht->get_content()) {
			throw new DataException('Each stylesheet must have some content');
		}
		$db = Lone::get('Db');
		// double check the name
		if ($sht->get_id()) {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE `name` = ? AND id != ?';
			$tmp = $db->getOne($sql, [$sht->get_name(), $sht->get_id()]);
		} else {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE `name` = ?';
			$tmp = $db->getOne($sql, [$sht->get_name()]);
		}
		if ($tmp) {
			throw new LogicException('A stylesheet with the same name already exists.');
		}
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
	 * @param Stylesheet $sht (or deprecated CmsLayoutStylesheet)
	 * @throws SQLException or DataException
	 */
	public static function save_stylesheet($sht)
	{
		self::validate_stylesheet($sht);

		if ($sht->get_id()) {
			Events::SendEvent('Core', 'EditStylesheetPre', [get_class($sht) => &$sht]);
			self::update_stylesheet($sht);
			Events::SendEvent('Core', 'EditStylesheetPost', [get_class($sht) => &$sht]);
		} else {
			Events::SendEvent('Core', 'AddStylesheetPre', [get_class($sht) => &$sht]);
			self::insert_stylesheet($sht);
			Events::SendEvent('Core', 'AddStylesheetPost', [get_class($sht) => &$sht]);
		}
	}

	/**
	 * Delete the specified stylesheet.
	 * This method deletes the appropriate records from the database,
	 * deletes a content-file if any, deletes the id from the stylesheet
	 * object, and marks the object as dirty so it can be saved again.
	 *
	 * This method triggers the DeleteStylesheetPre and DeleteStylesheetPost events
	 * @param Stylesheet $sht (or deprecated CmsLayoutStylesheet)
	 */
	public static function delete_stylesheet($sht)
	{
		$sid = $sht->get_id();
		if (!$sid) {
			return;
		}

		Events::SendEvent('Core', 'DeleteStylesheetPre', [get_class($sht) => &$sht]);
		$db = Lone::get('Db');

		$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$dbr = $db->execute($sql, [$sid]); // TODO check $dbr, handle error

		if (($fp = $sht->get_content_filename())) {
			@unlink($fp);
		}

//TODO	Lone::get('LoadedData')->refresh('LayoutStylesheets'); if that cache exists
		$str = 'Stylesheet \''.$sht->get_name().'\' Deleted';
		log_notice($str);
		Events::SendEvent('Core', 'DeleteStylesheetPost', [get_class($sht) => &$sht]);
		log_info($sid, $orig, $str);
	}

	/**
	 * Get the specified stylesheet
	 *
	 * @param mixed $a stylesheet identifier, (int|numeric string) id or (other string) name
	 * @return Stylesheet
	 * @throws LogicException
	 */
	public static function get_stylesheet($a)
	{
		$db = Lone::get('Db');
		if (is_numeric($a) && (int)$a > 0) {
			$sql = 'SELECT id,originator,`name`,description,media_type,media_query,owner_id,type_id,type_dflt,listable,contentfile,content,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
			$row = $db->getRow($sql, [(int)$a]);
		} elseif (is_string($a) && $a !== '') {
			$sql = 'SELECT id,originator,`name`,description,media_type,media_query,owner_id,type_id,type_dflt,listable,contentfile,content,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE `name` = ?';
			$row = $db->getRow($sql, [$a]);
		} else {
			$row = [];
		}
		if ($row) {
			return self::create_stylesheet($row);
		}
		throw new LogicException('Could not find stylesheet identified by '.$a);
	}

	/**
	 * Get multiple stylesheets
	 *
	 * This method does not throw exceptions if any requested id or name does not exist.
	 *
	 * @param array $ids stylesheet identifiers, all of them (int|numeric string) id's or (other string) names
	 * @param bool $deep whether or not to load associated data
	 * @return array Stylesheet object(s) | empty
	 * @throws LogicException
	 */
	public static function get_bulk_stylesheets(array $ids, bool $deep = true) : array
	{
		if (!$ids) {
			return [];
		}

		$db = Lone::get('Db');
		// clean up the input data
		if (is_numeric($ids[0]) && (int)$ids[0] > 0) {
			$is_ints = true;
			for ($i = 0, $n = count($ids); $i < $n; ++$i) {
				$ids[$i] = (int)$ids[$i];
			}
			$ids = array_unique($ids);
			$where = ' WHERE id IN ('.implode(',', $ids).')';
		} elseif (is_string($ids[0]) && $ids[0] !== '') {
			$is_ints = false;
			for ($i = 0, $n = count($ids); $i < $n; ++$i) {
				$ids[$i] = $db->qStr(trim($ids[$i]));
			}
			$ids = array_unique($ids);
			$where = ' WHERE `name` IN ('.implode(',', $ids).')';
		} else {
			// what ??
			throw new LogicException('Invalid identifier provided to '.__METHOD__);
		}
		$sql = 'SELECT id,originator,`name`,description,media_type,media_query,owner_id,type_id,type_dflt,listable,contentfile,content,create_date,modified_date FROM '.CMS_DB_PREFIX.self::TABLENAME.$where;
		$dbr = $db->getArray($sql);
		$out = [];
		if ($dbr) {
			if ($deep) {
				$ids2 = [];
				foreach ($dbr as $row) {
					$ids2[] = $row['id'];
				}
			}

			// this makes sure that the returned array matches the order specified.
			foreach ($ids as $one) {
				$found = [];
				if ($is_ints) {
					// find item in $dbr by id
					foreach ($dbr as $row) {
						if ($row['id'] == $one) {
							$found = $row;
							break;
						}
					}
				} else {
					$one = trim($one, "'"); //assume mysqli quotes names like this
					// find item in $dbr by name
					foreach ($dbr as $row) {
						if ($row['name'] == $one) {
							$found = $row;
							break;
						}
					}
				}

				$obj = self::create_stylesheet($found);
				if (is_object($obj)) {
					$out[] = $obj;
				}
			}
		}
		return $out;
	}

	/**
	 * Return numeric id and name for all or specified stylesheets
	 * @see also StylesheetOperations::get_all_stylesheets(false);
	 *
	 * @param mixed $ids array of integer sheet id's, or falsy to process all recorded sheets
	 * @return array Each row has a stylesheet id and name
	 */
	public static function get_bulk_sheetsnames($ids = [], $sorted = true) : array
	{
		$db = Lone::get('Db');
		$sql = 'SELECT id,`name` FROM '.CMS_DB_PREFIX.self::TABLENAME;
		if ($ids) {
			$sql .= ' WHERE id IN ('.implode(',', $ids).')';
		}
		if ($sorted) {
			$sql .= ' ORDER BY `name`';
		}
		return $db->getArray($sql);
	}

	/**
	 * Get all recorded stylesheets
	 *
	 * @param bool $by_name Optional flag indicating the output format. Default false.
	 * @return array If $by_name is true then each member is an array like
	 *  id => 'name'. Otherwise, each member is a Stylesheet object. Or empty
	 */
	public static function get_all_stylesheets(bool $by_name = false) : array
	{
		$db = Lone::get('Db');
		if ($by_name) {
			$sql = 'SELECT id,`name` FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY IF(modified_date, modified_date, create_date) DESC';
			return $db->getAssoc($sql);
		} else {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY IF(modified_date, modified_date, create_date) DESC';
			$ids = $db->getCol($sql);
			return self::get_bulk_stylesheets($ids, false);
		}
	}

	/**
	 * Return summary information for all or selected groups.
	 *
	 * @param mixed $ids array of integer group id's, or falsy to process all recorded groups
	 * @return array Each row has a group id, name and comma-separated member id's
	 */
	public static function get_groups_summary($ids = [], bool $sorted = true)
	{
		$tbl1 = CMS_DB_PREFIX.StylesheetsGroup::TABLENAME;
		$tbl2 = CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE;
		$sql = <<<EOS
SELECT G.id,G.`name`,COALESCE(list,'') AS members FROM $tbl1 G
LEFT JOIN (SELECT group_id, GROUP_CONCAT(css_id ORDER BY item_order) AS list FROM $tbl2 GROUP BY group_id) MS
ON G.id = MS.group_id
EOS;
		if ($ids) {
			$sql .= ' WHERE G.id IN ('.implode(',', $ids).')';
		}
		if ($sorted) {
			$sql .= ' ORDER BY G.`name`';
		}
		$db = Lone::get('Db');
		return $db->getArray($sql);
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
		if ($grps) {
			foreach ($grps as &$row) {
/*				if (strpos($row['members'],',') === FALSE) {
					$row = null; //ignore single-member groups
				} else {
*/
				$row['id'] = -(int)$row['id']; // group id's < 0
//				}
			}
			unset($row);
//			$grps = array_filter($grps);
		}

		$sheets = self::get_bulk_sheetsnames(null, false);
		if ($sheets) {
			foreach ($sheets as &$row) {
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
		$db = Lone::get('Db');
		if ($prefix) {
			$wm = $db->escStr($prefix).'%';
			$sql = 'SELECT id,`name` FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE `name` LIKE ? ORDER BY `name`';
			$dbr = $db->getAssoc($sql, [$wm]);
		} else {
			$sql = 'SELECT id,`name` FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' ORDER BY `name`';
			$dbr = $db->getAssoc($sql);
		}
		if ($dbr) {
			if ($by_name) {
				$out = $dbr;
			} else {
				foreach ($dbr as $id => $name) {
					$id = (int)$id;
					try {
						$out[$id] = StylesheetsGroup::load($id);
					} catch (Throwable $t) {
						//ignore problem
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
		$db = Lone::get('Db');
		if (is_numeric($a) && (int)$a > 0) {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE id=?';
			$dbr = $db->getOne($sql, [(int)$a]);
		} elseif (is_string($a) && $a !== '') {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE `name`=?';
			$dbr = $db->getOne($sql, [$a]);
		} else {
			$dbr = false;
		}
		if ($dbr) {
			return StylesheetsGroup::load($dbr);
		}
	}

	/**
	 * Get a unique name for a stylesheet
	 *
	 * @param string $prototype A prototype stylesheet name
	 * @param string $prefix An optional name prefix. Default ''.
	 * @return string
	 * @throws LogicException
	 */
	public static function get_unique_name(string $prototype, string $prefix = '') : string
	{
		if (!$prototype) {
			throw new LogicException('Prototype name cannot be empty');
		}
		$db = Lone::get('Db');
		$wm = $db->escStr($prototype);
		$sql = 'SELECT name FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE `name` LIKE ?';
		$all = $db->getCol($sql, ['%'.$wm.'%']);
		if ($all) {
			$name = $prototype;
			$i = 0;
			while (in_array($name, $all)) {
				$name = $prefix.$prototype.'_'.++$i;
			}
			return $name;
		}
		return $prototype;
	}

	//============= STYLESHEET-OPERATION BACKENDS ============

	/**
	 * Clone stylesheet(s) and/or group(s)
	 * @param mixed $ids int | int[] stylesheet identifier(s), any id < 0 means a group
	 * @return int No of stylesheets cloned
	 */
	public static function operation_copy($ids) : int
	{
		$n = 0;
		$db = Lone::get('Db');
		list($shts, $grps) = self::items_split($ids);
		if ($shts) {
			$sql = 'SELECT originator,`name`,description,media_type,media_query,owner_id,type_id,type_dflt,listable,contentfile,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($shts) - 1).'?)';
			$from = $db->getArray($sql, $shts);
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (
originator,
`name`,
description,
media_type,
media_query,
owner_id,
type_id,
type_dflt,
listable,
contentfile,
content) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
			foreach ($from as $row) {
				if ($row['name']) {
					$row['name'] = self::get_unique_name($row['name']);
				} else {
					$row['name'] = self::get_unique_name('Unnamed Stylesheet');
				}
				$row['type_dflt'] = 0;
				$db->execute($sql, array_values($row));
				if ($row['contentfile']) {
					$id = $db->Insert_ID();
					$fn = santitizeVal($row['name'], 3).'.'.$id.'.css';
					if (!isset($config)) {
						$config = Lone::get('Config');
					}
					$from = cms_join_path(CMS_ASSETS_PATH, 'styles', $row['content']);
					$to = cms_join_path(CMS_ASSETS_PATH, 'styles', $fn);
					if (copy($from, $to)) {
						$db->execute('UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=? WHERE id=?', [$fn, $id]);
					} else {
						//TODO handle error
					}
				}
			}
			$n = count($from);
		}
		if ($grps) {
			$sql = 'SELECT id,`name`,description FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$from = $db->getArray($sql, $grps);
			$sql = 'SELECT group_id,css_id,item_order FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$members = $db->execute($sql, $grps);
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' (`name`,description) VALUES (?,?)';
			$sql2 = 'INSERT INTO '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' (group_id,css_id,item_order) VALUES (?,?,?)';
			foreach ($from as $row) {
				if ($row['name']) {
					$name = self::get_unique_name($row['name']);
				} else {
					$name = '';
				}
				$db->execute($sql, [$name, $row['description']]);
				$to = $db->Insert_ID();
				$from = $row['id'];
				foreach ($members as $grprow) {
					if ($grprow['group_id'] == $from) {
						$db->execute($sql2, [$to, $grprow['css_id'], $grprow['item_order']]);
					}
				}
			}
			$n += count($from);
		}
		return $n;
	}

	/**
	 * Delete stylesheet(s) and/or group(s) but not group members (unless also specified individually)
	 * @param mixed $ids int | int[] stylesheet identifier(s), any id < 0 means a group
	 * @return int No of items affected e.g. pages modified | groups deleted
	 */
	public static function operation_delete($ids) : int
	{
		$db = Lone::get('Db');
		$c = 0;
		list($shts, $grps) = self::items_split($ids);
		if ($grps) {
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$db->execute($sql, $grps);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$c = (int)$db->execute($sql, $grps);
		}
		if ($shts) {
			list($pages, $skips) = self::affected_pages($shts);
			if ($pages) {
				$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
				foreach ($pages as &$row) {
					$s = self::filter($row['styles'], $shts);
					$db->execute($sql, [$s, $row['content_id']]);
				}
				unset($row);
				$n = count($pages);
			} else {
				$n = 0;
			}

			if ((is_array($ids))) {
				$fillers = '('.str_repeat('? OR ', count($ids) - 1).'?)';
				$args = array_map(function($v) { return '%'.$v.'%'; }, $ids); //numeric values only, no wildcard escaping
			} else {
				$fillers = '?';
				$args = ['%'.$ids.'%']; //numeric value only
			}
			$sql = 'SELECT DISTINCT styles FROM '.CMS_DB_PREFIX.'content WHERE styles LIKE '.$fillers;
			$keeps = $db->getCol($sql, $args);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($shts) - 1).'?)';
			if ($keeps) {
				$t = [];
				foreach ($keeps as $s) {
					$tmp = explode(',', $s);
					$t = array_merge($t, array_intersect($shts, $tmp)); //any grp id < 0 will be ignored
				}
				if ($t) {
					$sql .= ' AND id NOT IN ('.implode(',', $t).')';
				}
			}
			$n = (int)$db->execute($sql, $shts);
			return ($c > 0) ? $c : $n;
		}
		return $c;
	}

	/**
	 * Delete stylesheet(s) and/or group(s) and group-member(s)
	 * @param mixed $ids int | int[] stylesheet identifier(s), any id < 0 means a group
	 * @return int No of pages modified
	 */
	public static function operation_deleteall($ids) : int
	{
		$db = Lone::get('Db');
		list($shts, $grps) = self::items_split($ids);
		if ($grps) {
			$sql = 'SELECT DISTINCT tpl_id FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$members = $db->getCol($sql, $grps);
			$shts = array_unique(array_merge($shts, $members));
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$db->execute($sql, $grps);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$db->execute($sql, $grps);
		}
		if ($shts) {
			list($pages, $skips) = self::affected_pages($shts);
			if ($pages) {
				$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
				foreach ($pages as &$row) {
					$s = self::filter($row['styles'], $shts);
					$db->execute($sql, [$s, $row['content_id']]);
				}
				unset($row);
				$n = count($pages);
			} else {
				$n = 0;
			}
			if (is_array($ids)) {
				$fillers = '('.str_repeat('? OR ', count($ids) - 1).'?)';
				$args = array_map(function($v) { return '%'.$v.'%'; }, $ids); //numeric values only, no wildcard escaping
			} else {
				$fillers = '?';
				$args = ['%'.$ids.'%']; //numeric value only
			}
			$sql = 'SELECT DISTINCT styles FROM '.CMS_DB_PREFIX.'content WHERE styles LIKE '.$fillers;
			$keeps = $db->getCol($sql, $args);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($shts) - 1).'?)';
			if ($keeps) {
				$t = [];
				foreach ($keeps as $s) {
					$tmp = explode(',', $s);
					$t = array_merge($t, array_intersect($shts, $tmp)); //any grp id < 0 will be ignored
				}
				if ($t) {
					$sql .= ' AND id NOT IN ('.implode(',', $t).')';
				}
			}
			$db->execute($sql, $shts);

			return $n;
		}
		return 0;
	}

	/**
	 * Replace the stylesheet wherever used and the user is authorized
	 * @param mixed $from int|int[] stylesheet identifier(s), < 0 for a group
	 * @param mixed $to int|int[] stylesheet identifier(s), < 0 for a group
	 * @return int No of pages modified
	 */
	public static function operation_replace($from, $to) : int
	{
		list($pages, $skips) = self::affected_pages($from);
		if ($pages) {
			$db = Lone::get('Db');
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
			foreach ($pages as &$row) {
				$s = self::filter2($row['styles'], $from, $to);
				$db->execute($sql, [$s, $row['content_id']]);
			}
			unset($row);
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
			$db = Lone::get('Db');
			if (is_array($ids)) {
				$to = ','.implode(',', $ids);
			} else {
				$to = ','.$ids;
			}
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
			foreach ($pages as &$row) {
				$s = self::filter($row['styles'], $ids);
				$s = trim($s.$to, ' ,');
				$db->execute($sql, [$s, $row['content_id']]);
			}
			unset($row);
			return count($pages);
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
			$db = Lone::get('Db');
			if (is_array($ids)) {
				$to = implode(',', $ids).',';
			} else {
				$to = $ids.',';
			}
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
			foreach ($pages as &$row) {
				$s = self::filter($row['styles'], $ids);
				$s = trim($to.$s, ' ,');
				$db->execute($sql, [$s, $row['content_id']]);
			}
			unset($row);
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
			$db = Lone::get('Db');
			$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET styles=? WHERE content_id=?';
			foreach ($pages as &$row) {
				$s = self::filter($row['styles'], $ids);
				$db->execute($sql, [$s, $row['content_id']]);
			}
			unset($row);
			return count($pages);
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
		list($shts, $grps) = self::items_split($ids);
		if ($shts) {
			$db = Lone::get('Db');
			$sql = 'SELECT id,`name`,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE contentfile=0 AND id IN ('.str_repeat('?,', count($shts) - 1).'?)';
			$from = $db->getArray($sql, $shts);
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=?,contentfile=1 WHERE id=?';
			$config = Lone::get('Config');
			foreach ($from as $row) {
				if ($row['name']) {
					//replicate object::set_content_file()
					$fn = sanitizeVal($row['name'], CMSSAN_FILE).'.'.$row['id'].'.css';
					//replicate object::get_content_filename()
					$outfile = cms_join_path(CMS_ASSETS_PATH, 'styles', $fn);
					$res = file_put_contents($outfile, $row['content'], LOCK_EX);
					if ($res !== false) {
						$db->execute($sql, [$fn, $row['id']]);
						++$n;
					} else {
						//TODO some signal needed
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
		list($shts, $grps) = self::items_split($ids);
		if ($shts) {
			$db = Lone::get('Db');
			$sql = 'SELECT id,`name`,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE contentfile=1 AND id IN ('.str_repeat('?,', count($shts) - 1).'?)';
			$from = $db->getArray($sql, $shts);
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=?,contentfile=0 WHERE id=?';
			$config = Lone::get('Config');
			foreach ($from as $row) {
				if ($row['name']) {
					//replicate object::set_content_file()
					$fn = sanitizeVal($row['name'], CMSSAN_FILE).'.'.$row['id'].'.css';
					//replicate object::get_content_filename()
					$outfile = cms_join_path(CMS_ASSETS_PATH, 'styles', $fn);
					$content = file_get_contents($outfile);
					if ($content !== false) {
						$db->execute($sql, [$content, $row['id']]);
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
	protected static function contentfile_operations($sht)
	{
		if (($ops = $sht->fileoperations)) {
			foreach ($ops as $row) {
				if ($row[1]) {
					$fp = cms_join_path(CMS_ASSETS_PATH, 'styles', $row[1]);
					switch ($row[0]) {
						case 'store': // row = ['store',$tobasename,$content] $content may be empty
							if ($row[2]) {
								file_put_contents($fp, $row[2], LOCK_EX);
							} elseif (!is_file($fp)) {
								//TODO handle error
							} else {
								$content = @file_get_contents($fp);
								if (!$content) {
									//TODO handle error
								}
							}
							break;
						case 'delete': // row = ['delete',$thebasename]
							unlink($fp);
							break;
						case 'rename': // row = ['rename',$frombasename, $tobasename]
							if ($row[2]) {
								$tp = cms_join_path(CMS_ASSETS_PATH, 'styles', $row[2]);
								rename($fp, $tp);
							} else {
								//TODO handle error
							}
							break;
					}
				} else {
					$here = 1;
					//TODO handle error
				}
			}
			$sht->fileoperations = [];
		}
	}

	/**
	 * @ignore
	 */
	protected static function update_stylesheet($sht)
	{
		$sid = $sht->id;
		$orig = $sht->originator;
		$name = $sht->name;
		$desc = $sht->description;
		$tmp = $sht->media_types;
		$mt = ($tmp) ? implode(',', $tmp) : null;
		$mq = $sht->media_query;

		$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.'SET
originator = ?,
name = ?,
description = ?,
media_type = ?,
media_query = ?,
owner_id = ?,
type_id = ?,
type_dflt = ?,
listable = ?,
contentfile = ?,
content = ?
WHERE id = ?';
		$db = Lone::get('Db');
		$args = [
			($orig) ? $orig : null,
			$name,
			($desc) ? $desc : null,
			$mt,
			($mq) ? $mq : null,
			$sht->owner_id,
			$sht->type_id,
			$sht->type_dflt,
			$sht->listable,
			$sht->contentfile,
			$sht->content,
			$sid
		];
		//ensure file-stored sheet has its name as content
		if ($args[9] && !$args[10]) {
			$args[10] = $sht->filecontent;
		}
        $db->execute($sql, $args);
		if ($db->errorNo() > 0) { throw new SQLException($db->sql.' -- '.$db->errorMsg()); }

		self::contentfile_operations($sht);

		if ($sht->type_dflt) {
			// if it's default for its type, clear default flag for all other records with this type
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
			$db->execute($sql, [$sht->type_id, $sid]);
//			if ($db->errorNo() > 0) throw new SQLException($db->sql.' -- '.$db->errorMsg());
		}

		$sql = 'DELETE FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE css_id = ?';
		$db->execute($sql, [$sid]);
		$all = $sht->groups;
		if ($all) {
			$stmt = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' (group_id,css_id,item_order) VALUES (?,?,?)');
			$i = 1;
			foreach ($all as $id) {
				$db->execute($stmt, [(int)$id, $sid, $i]);
				++$i;
			}
			$stmt->close();
		}

//TODO	Lone::get('LoadedData')->refresh('LayoutStylesheets'); if that cache exists
		log_info($sid, $orig, 'Stylesheet \''.$name.'\' Updated');
	}

	/**
	 * @ignore
	 */
	protected static function insert_stylesheet($sht)
	{
		$orig = $sht->originator;
		$name = $sht->name;
		$desc = $sht->description;
		$tmp = $sht->get_media_types();
		$mt = ($tmp) ? implode(',', $tmp) : null;
		$mq = $sht->media_query;

		$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (
originator,
`name`,
description,
media_type,
media_query,
owner_id,
type_id,
type_dflt,
listable,
contentfile,
content) VALUES (?,?,?,?,?,?,?,?,?,?,?)';
		$db = Lone::get('Db');
		$args = [
			($orig) ? $orig : null,
			$name,
			($desc) ? $desc : null,
			$mt,
			($mq) ? $mq : null,
			$sht->owner_id,
			$sht->type_id,
			$sht->type_dflt,
			$sht->listable,
			$sht->contentfile,
			$sht->content
		];
		//ensure file-stored sheet has its name as content
		if ($args[9] && !$args[10]) {
			$args[10] = $sht->filecontent;
		}
		$dbr = $db->execute($sql, $args);
		if (!$dbr) {
			throw new SQLException($db->sql.' -- '.$db->errorMsg());
		}
		$sid = $db->Insert_ID();
		$sht->set_id($sid);

		self::contentfile_operations($sht);

		if ($sht->type_dflt) {
			// if it's default for its type, clear default flag for all other records with this type
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
			$db->execute($sql, [$sht->type_id, $sid]);
//			if ($db->errorNo() > 0) { throw new SQLException($db->sql.' -- '.$db->errorMsg()); }
		}

		$all = $sht->groups;
		if ($all) {
			$stmt = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' (group_id,css_id,item_order) VALUES (?,?,?)');
			$i = 1;
			foreach ($all as $id) {
				$db->execute($stmt, [(int)$id, $sid, $i]);
				++$i;
			}
			$stmt->close();
		}

//TODO	Lone::get('LoadedData')->refresh('LayoutStylesheets'); if that cache exists
		log_info($sid, $orig, 'Stylesheet \''.$name.'\' Created');
	}

	/**
	 * @ignore
	 * @param array $row
	 * @return Stylesheet
	 */
	protected static function create_stylesheet(array $row) : Stylesheet
	{
		$db = Lone::get('Db');
		$query = 'SELECT group_id FROM '.CMS_DB_PREFIX.StylesheetsGroup::MEMBERSTABLE.' WHERE css_id = ?';
		$row['groups'] = $db->getCol($query, [$row['id']]);
		// no support (yet?) for additional editors c.f. Template

		$sht = new Stylesheet();
		$sht->set_properties($row);
		return $sht;
	}

	/**
	 * Get data for pages to be operated on or skipped
	 * @param mixed $ids  int stylesheet id | int[] id's | string '*'
	 * @return 2-member array
	 *  [0] = array, each row having 'content_id', 'styles'
	 *  [1] = no. of unusable pages
	 */
	protected static function affected_pages($ids)
	{
		$uid = get_userid();
		$modify_all = check_permission($uid, 'Manage All Content') || check_permission($uid, 'Modify Any Page');
		$sql = 'SELECT content_id,styles FROM '.CMS_DB_PREFIX.'content';
		if ($ids != '*') {
			if (is_array($ids)) {
				$fillers = '('.str_repeat('? OR ', count($ids) - 1).'?)';
				$args = array_map(function($v) { return '%'.$v.'%'; }, $ids); //numeric values only, no wildcard escaping
			} else {
				$fillers = '?';
				$args = ['%'.$ids.'%']; //numeric value only
			}
			$sql .= ' WHERE styles LIKE '.$fillers;
			if (!$modify_all) {
				$sql .= ' AND owner_id=?';
				$args[] = $uid;
			}
		} elseif (!$modify_all) {
			$sql .= ' WHERE owner_id=?';
			$args = [$uid];
		} else {
			$args = [];
		}
		$db = Lone::get('Db');
		$valid = $db->getArray($sql, $args);

		if (!$modify_all) {
			if ($ids != '*') {
				if (is_array($ids)) {
					$fillers = '('.str_repeat('? OR ', count($ids) - 1).'?)';
					$args = array_map(function($v) { return '%'.$v.'%'; }, $ids); //numeric values only, no wildcard escaping
				} else {
					$fillers = '?';
					$args = ['%'.$ids.'%']; //numeric value only
				}
				$sql = 'SELECT COUNT(1) AS num FROM '.CMS_DB_PREFIX.'content WHERE styles LIKE '.$fillers;
			} else {
				$sql = 'SELECT COUNT(1) AS num FROM '.CMS_DB_PREFIX.'content';
				$args = [];
			}
			$all = $db->getOne($sql, $args);
			$other = $all - count($valid);
		} else {
			$other = 0;
		}
		return [$valid, $other];
	}

	/**
	 * Partition $ids into sheet id(s) and/or sheet-group id(s)
	 * @ignore
	 * $param mixed $ids int | int[], any id < 0 means a group
	 * @return 2-member array [0] = sheet ids, [1] = group ids (now > 0)
	 */
	protected static function items_split($ids)
	{
		$sngl = [];
		$grp = [];
		if (is_array($ids)) {
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
	 * Remove anything in $ids from $s
	 * @ignore
	 * @param string $s Stylesheets value (possibly comma-separated)
	 * @param mixed $ids int | int[]
	 * @return string (possibly comma-separated)
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
	 * Replace $from by corresponding $to in $s
	 * @ignore
	 * @param string $s Stylesheets value (possibly comma-separated)
	 * @param mixed $from int | int[]
	 * @param mixed $to int | int[]
	 * @return string (possibly comma-separated)
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
