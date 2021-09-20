<?php
/*
Methods for interacting with template objects
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
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
use CMSMS\SingleItem;
use CMSMS\SQLException;
use CMSMS\Template;
use CMSMS\TemplateQuery;
use CMSMS\TemplatesGroup;
use CMSMS\TemplateType;
use CMSMS\User;
use LogicException;
use RuntimeException;
use Throwable;
use UnexpectedValueException;
use const CMS_ASSETS_PATH;
use const CMS_DB_PREFIX;
use const CMSSAN_FILE;
use const CMSSAN_NAME;
use function audit;
use function check_permission;
use function cms_join_path;
use function CMSMS\sanitizeVal;
use function endswith;
use function file_put_contents;
use function get_userid;

/**
 * A class of static methods for dealing with Template objects.
 * This class is for template administration, via the admin console
 * or by DesignManager module etc.
 * The class is not involved with intra-request template processing.
 *
 * @since 2.99
 * @package CMS
 * @license GPL
 */
class TemplateOperations
{
	/**
	 * @ignore
	 */
	const TABLENAME = 'layout_templates';

	/**
	 * @ignore
	 */
	const ADDUSERSTABLE = 'layout_tpl_addusers';

	// static properties here >> SingleItem property|ies ?
	/**
	 * @ignore
	 */
	protected static $identifiers;

	/**
	 * Check whether template properties are valid
	 *
	 * @param Template $tpl (or perhaps a deprecated CmsLayoutTemplate)
	 * @throws DataException or UnexpectedValueException or RuntimeException or LogicException
	 */
	public static function validate_template($tpl)
	{
		$name = $tpl->get_name();
		if (!$name) {
			throw new DataException('Each template must have a name');
		}
		if (endswith($name, '.tpl')) {
			throw new LogicException('Invalid name for a database template');
		}
		$tmp = sanitizeVal($name, CMSSAN_NAME);
		if ($tmp != $name || !AdminUtils::is_valid_itemname($name)) {
			throw new UnexpectedValueException('There are invalid characters in the template name');
		}

		if (!$tpl->get_content()) {
			throw new RuntimeException('Each template must have some content');
		}
		if ($tpl->get_type_id() <= 0) {
			throw new LogicException('Each template must be associated with a type');
		}
		$db = SingleItem::Db();
		$tid = $tpl->get_id();
		// check unique name
		if ($tid) {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ? AND id != ?';
			$dbr = $db->getOne($sql, [$name, $tid]);
		} else {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name = ?';
			$dbr = $db->getOne($sql, [$name]);
		}
		if ($dbr) {
			throw new LogicException('A template with the same name already exists.');
		}
	}

	/**
	 * Save a template
	 *
	 * @param Template $tpl The template object. (or perhaps a deprecated CmsLayoutTemplate)
	 */
	public static function save_template($tpl)
	{
		self::validate_template($tpl);

		$key = get_class($tpl);
		// TODO pre-event handlers must not change the name in a bad way
		$name = $tpl->name;
		if ($tpl->id) {
			Events::SendEvent('Core', 'EditTemplatePre', [$key => &$tpl]);
			if ($tpl->name != $name) {
				//TODO $tpl->name = func($tpl->name);
			}
			$tpl = self::update_template($tpl); // $tpl might now be different, thanks to event handler
			Events::SendEvent('Core', 'EditTemplatePost', [$key => &$tpl]);
		} else {
			Events::SendEvent('Core', 'AddTemplatePre', [$key => &$tpl]);
			if ($tpl->name != $name) {
				//TODO $tpl->name = func($tpl->name);
			}
			$tpl = self::insert_template($tpl);
			Events::SendEvent('Core', 'AddTemplatePost', [$key => &$tpl]);
		}
	}

	/**
	 * Delete a template
	 * This does not modify the template object itself, nor any pages which use the template.
	 *
	 * @param Template $tpl (or perhaps a deprecated CmsLayoutTemplate)
	 */
	public static function delete_template($tpl)
	{
		if (!($tid = $tpl->get_id())) {
			return;
		}

		$key = get_class($tpl);
		Events::SendEvent('Core', 'DeleteTemplatePre', [$key => &$tpl]);
		$db = SingleItem::Db();
		$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id = ?';
		$db->execute($sql, [$tid]);

		if (($fp = $tpl->get_content_filename())) {
			@unlink($fp);
		}

		audit($tid, 'CMSMS', 'Template \''.$tpl->get_name().'\' Deleted');
		Events::SendEvent('Core', 'DeleteTemplatePost', [$key => &$tpl]);
		//		SingleItem::LoadedData()->refresh('LayoutTemplates'); if that cache exists
	}

	/**
	 * Get a specific template
	 *
	 * @param mixed $a int template id | string name (possibly as originator::name)
	 *  of the wanted template
	 * @return mixed Template object | null
	 * @throws DataException
	 */
	public static function get_template($a)
	{
		$tid = self::resolve_template($a);

		$db = SingleItem::Db();
		$sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id=?';
		$row = $db->getRow($sql, [$tid]);
		$editors = null; // TODO ignored editors and groups ok ?
		$groups = null;
		if ($row) {
			return self::create_template($row, $editors, $groups);
		}
	}

	/**
	 * [Re]set all properties of a template, using properties from another one
	 *
	 * @since 2.99
	 * @deprecated since 2.99 this enables the deprecated CmsLaoutTemplate::load() method
	 * @param Template $tpl The template to be updated (or perhaps a deprecated CmsLayoutTemplate)
	 * @param mixed $a The id or name of the template from which to source the replacement properties
	 * @throws DataException
	 */
	public static function replicate_template($tpl, $a)
	{
		$src = self::get_template($a);
		$data = $src->get_properties();
		$tpl->set_properties($data);
	}

	/**
	 * Get multiple templates
	 *
	 * @param array $ids Integer template id(s)
	 * @param bool $deep Optional flag whether to load attached data. Default true.
	 * @return array Template object(s) or empty
	 */
	public static function get_bulk_templates(array $ids, bool $deep = true) : array
	{
		if (!$ids) {
			return [];
		}

		$out = [];
		$db = SingleItem::Db();
		$str = implode(',', $ids);
		$sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.$str.')';
		$rows = $db->getArray($sql);
		if ($rows) {
			$sql = 'SELECT * FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
			$alleditors = $db->getArray($sql);
			// table a.k.a. TemplatesGroup::MEMBERSTABLE
			$sql = 'SELECT * FROM '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' WHERE tpl_id IN ('.$str.') ORDER BY tpl_id';
			$allgroups = $db->getArray($sql);

			// put it all together, into object(s)
			foreach ($rows as $row) {
				$id = $row['id'];
				$editors = self::filter_editors($id, $alleditors);
				$groups = self::filter_groups($id, $allgroups);
				$out[] = self::create_template($row, $editors, $groups);
			}
		}
		return $out;
	}

	/**
	 * Get all templates owned by the specified user
	 *
	 * @param mixed $a user id (int) or user name (string)
	 * @return array Template object(s) or empty
	 * @throws LogicException
	 */
	public static function get_owned_templates($a) : array
	{
		$id = self::resolve_user($a);
		if ($id <= 0) {
			throw new LogicException('Invalid user specified to '.__METHOD__);
		}
		$ob = new TemplateQuery(['u' => $id]);
		$list = $ob->GetMatchedTemplateIds();
		if ($list) {
			return self::get_bulk_templates($list);
		}
		return [];
	}

	/**
	 * Get all templates whose originator is the one specified
	 * @since 2.99
	 *
	 * @param string $orig name of originator - core (Template::CORE or '') or a module name
	 * @param bool $by_name Optional flag indicating the output format. Default false.
	 * @return array If $by_name is true then each member will have template id
	 *  and template name. Otherwise, id and Template object. Or empty
	 * @throws DataException
	 */
	public static function get_originated_templates(string $orig, bool $by_name = false) : array
	{
		if (!$orig) {
			$orig = Template::CORE;
		}
		$ob = new TemplateQuery(['o' => $orig]);
		$list = $ob->GetMatchedTemplateIds();
		if ($list) {
			if ($by_name) {
				$db = SingleItem::Db();
				$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN('.implode(',', $list).') ORDER BY name';
				return $db->getAssoc($sql);
			} else {
				return self::get_bulk_templates($list);
			}
		}
		return [];
	}

	/**
	 * Get all templates that the specified user owns or may otherwise edit
	 *
	 * @param mixed $a user id (int) or user name (string)
	 * @return array Template object(s) | empty
	 * @throws LogicException
	 */
	public static function get_editable_templates($a) : array
	{
		$id = self::resolve_user($a);
		if ($id <= 0) {
			throw new LogicException('Invalid user specified to '.__METHOD__);
		}
		$db = SingleItem::Db();
		$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME;
		$parms = $where = [];
		if (!SingleItem::UserOperations()->CheckPermission($id, 'Modify Templates')) {
			$sql .= ' WHERE owner_id = ?';
			$parms[] = $id;
		}
		$list = $db->getCol($sql, $parms);
		if (!$list) {
			$list = [];
		}

		$sql = 'SELECT tpl_id FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE user_id = ?';
		$list2 = $db->getCol($sql, [$id]);
		if (!$list2) {
			$list2 = [];
		}

		$tpl_list = array_merge($list, $list2);
		if ($tpl_list) {
			$tpl_list = array_unique($tpl_list);
			return self::get_bulk_templates($tpl_list);
		}
		return [];
	}

	/**
	 * Get all recorded templates
	 * @since 2.99
	 *
	 * @param bool $by_name Optional flag indicating the output format. Default false.
	 * @return array If $by_name is true then each value will have template id
	 * and template name. Otherwise, id and a Template object
	 */
	public static function get_all_templates(bool $by_name = false) : array
	{
		$db = SingleItem::Db();

		if ($by_name) {
			$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY IF(modified_date,modified_date,create_date) DESC';
			return $db->getAssoc($sql);
		} else {
			$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY IF(modified_date,modified_date,create_date) DESC';
			$ids = $db->getCol($sql);
			return self::get_bulk_templates($ids, false);
		}
	}

	/**
	 * Get all templates of the specified type
	 *
	 * @param TemplateType $type (or perhaps a deprecated CmsLayoutTemplateType)
	 * @return array Template object(s) | empty
	 * @throws DataException
	 */
	public static function get_all_templates_by_type($type)
	{
		$db = SingleItem::Db();
		$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE type_id=?';
		$list = $db->getCol($sql, [$type->get_id()]);
		if ($list) {
			return self::get_bulk_templates($list);
		}
		return [];
	}

	/**
	 * Get the default template of the specified type
	 *
	 * @param mixed $a template-type name (like originator::name) |
	 *  (numeric) template-type id | TemplateType object
	 * @return mixed Template object | null
	 * @throws LogicException
	 */
	public static function get_default_template_by_type($a)
	{
		if (is_numeric($a)) {
			$tid = (int)$a;
		} elseif (is_string($a)) {
			$db = SingleItem::Db();
			$corename = TemplateType::CORE;
			$parts = explode('::', $a, 2);
			if (count($parts) == 1) {
				$sql = 'SELECT id FROM '.CMS_DB_PREFIX."layout_tpl_types WHERE name=? AND (originator='$corename' OR originator='' OR originator IS NULL)";
				$tid = $db->getOne($sql, [$a]);
			} else {
				if (!$parts[0] || $parts[0] == 'Core') {
					$parts[0] = $corename;
				}
				$sql = 'SELECT id FROM '.CMS_DB_PREFIX.'layout_tpl_types WHERE name=? AND originator=?';
				$tid = $db->getOne($sql, [$parts[1], $parts[0]]);
			}
		} elseif ($a instanceof TemplateType) {
			$tid = $a->get_id();
		} else {
			$tid = null;
		}

		if (!$tid) {
			throw new LogicException('Invalid identifier provided to '.__METHOD__);
		}
		if (!isset($db)) {
			$db = SingleItem::Db();
		}
		$sql = 'SELECT id FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE type_dflt=1 AND type_id=?';
		$id = $db->getOne($sql, [$tid]);
		if ($id) {
			return self::get_template($id);
		}
	}

	/**
	 * Return a set of groups or group-names
	 * @since 2.99
	 *
	 * @param string $prefix Optional group-name prefix to be matched. Default ''.
	 * @param bool   $by_name Optional flag whether to return group names. Default false
	 * @return assoc. array of TemplatesGroup objects or name strings
	 */
	public static function get_bulk_groups($prefix = '', $by_name = false)
	{
		$out = [];
		$db = SingleItem::Db();
		if ($prefix) {
			$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.TemplatesGroup::TABLENAME.' WHERE name LIKE ? ORDER BY name';
			$wm = $db->escStr($prefix).'%';
			$res = $db->getAssoc($sql, [$wm]);
		} else {
			$sql = 'SELECT id,name FROM '.CMS_DB_PREFIX.TemplatesGroup::TABLENAME.' ORDER BY name';
			$res = $db->getAssoc($sql);
		}
		if ($res) {
			if ($by_name) {
				$out = $res;
			} else {
				foreach ($res as $id => $name) {
					$id = (int)$id;
					try {
						$out[$id] = TemplatesGroup::load($id);
					} catch (Throwable $t) {
						//ignore problem
					}
				}
			}
		}
		return $out;
	}

	/**
	 * Return a list of all template-originators
	 * @since 2.99
	 * $param bool $friendly Optional flag whether to report in UI-friendly format. Default false.
	 * @return array name-strings, maybe empty
	 */
	public static function get_all_originators(bool $friendly = false) : array
	{
		$db = SingleItem::Db();
		$sql = 'SELECT DISTINCT originator FROM '.CMS_DB_PREFIX.self::TABLENAME;
		if ($friendly) {
			$sql .= ' ORDER BY originator';
		}
		$list = $db->getCol($sql);
		if ($list) {
			if ($friendly) {
				$p = array_search(Template::CORE, $list);
				if ($p !== false) {
					unset($list[$p]);
					$list = [-1 => 'Core'] + $list;
					return array_values($list);
				}
			}
			return $list;
		}
		return [];
	}

	//============= OTHER FORMER Template METHODS ============

	/**
	 * Get a new template of the specified type
	 *
	 * @param mixed $a template-type name (like originator::name) |
	 * (numeric) template-type id | TemplateType object
	 * @return Template
	 * @throws LogicException
	 */
	public static function get_template_by_type($a)
	{
		if (is_int($a) || is_string($a)) {
			$tt = TemplateType::load($a);
			if ($tt) {
				return $tt->create_new_template();
			}
		} elseif ($a instanceof TemplateType) {
			return $a->create_new_template();
		}
		throw new LogicException('Invalid identifier provided to '.__METHOD__);
	}

	/**
	 * Generate a unique name for a template
	 *
	 * @param string $prototype A prototype template name
	 * @param string $prefix An optional name-prefix. Default ''.
	 * @return mixed string | null
	 * @throws LogicException
	 */
	public static function get_unique_template_name(string $prototype, string $prefix = '') : string
	{
		if (!$prototype) {
			throw new LogicException('Prototype name cannot be empty');
		}
		$db = SingleItem::Db();
		$sql = 'SELECT name FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE name LIKE ?';
		$wm = $db->escStr($prototype);
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

	/**
	 * Perform an advanced database-query on templates
	 *
	 * @see TemplateQuery
	 * @param array $params
	 * @return type
	 */
	public static function template_query(array $params)
	{
		$ob = new TemplateQuery($params);
		$out = self::get_bulk_templates($ob->GetMatchedTemplateIds());

		if (isset($params['as_list']) && count($out)) {
			$tmp2 = [];
			foreach ($out as $tpl) {
				$tmp2[$tpl->get_id()] = $tpl->get_name();
			}
			return $tmp2;
		}
		return $out;
	}

	/**
	 * Test whether the specified user can edit the specified template.
	 * This is a convenience method that loads the template, and then
	 * tests whether the specified user has edit authority for it.
	 *
	 * @param mixed $a int template id | string template name
	 * @param mixed $userid int user id | string user name | null
	 *  If no userid is specified, the currently logged in userid is used
	 * @return bool
	 */
	public static function user_can_edit_template($a, $userid = null)
	{
		if (is_null($userid)) {
			$userid = get_userid();
		}

		// get the template, and additional users etc
		$tpl = self::get_template($a);
		if ($tpl->get_owner_id() == $userid) {
			return true;
		}

		// check the specific authorised users
		$editors = $tpl->get_additional_editors();
		if ($editors) {
			if (in_array($userid, $editors)) {
				return true;
			}

			$grouplist = SingleItem::UserOperations()->GetMemberGroups();
			if ($grouplist) {
				foreach ($editors as $id) {
					if ($id < 0 && in_array(-((int)$id), $grouplist)) {
						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	 * Process a named template through smarty
	 * @param string $name
	 * @return string (unless fetch fails?)
	 */
	public static function process_named_template(string $name)
	{
		$smarty = SingleItem::Smarty();
		return $smarty->fetch('cms_template:'.$name);
	}

	/**
	 * Process the default template of a specified type
	 *
	 * @param mixed $t int template type id | string template type identifier | TemplateType object
	 * @return string
	 */
	public static function process_default_template($t)
	{
		$smarty = SingleItem::Smarty();
		$tpl = self::get_default_template_by_type($t);
		return $smarty->fetch('cms_template:'.$tpl->get_id());
	}

	//============= TEMPLATE-OPERATION BACKENDS ============

	/**
	 * Clone template(s) and/or group(s)
	 * @since 2.99
	 * @param mixed $ids int | int[] template identifier(s), < 0 means a group
	 * @return int No of templates cloned
	 */
	public static function operation_copy($ids) : int
	{
		$n = 0;
		list($tpls, $grps) = self::items_split($ids);
		if ($tpls) {
			$sql = 'SELECT name,content,description,contentfile FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($tpls) - 1).'?)';
			$from = $db->getArray($sql, $shts);
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (name,content,description,contentfile) VALUES (?,?,?,?)';
			foreach ($from as $row) {
				if ($row['name']) {
					$row['name'] = self::get_unique_name($row['name']);
				} else {
					$row['name'] = self::get_unique_name('Unnamed Template');
				}
				$db->execute($sql, $row);
				if ($row['contentfile']) {
					$id = $db->Insert_ID();
					$fn = sanitizeVal($row['name'], CMSSAN_FILE).'.'.$id.'.css';
					if (!isset($config)) {
						$config = SingleItem::Config();
					}
					$from = cms_join_path($config['assets_path'], 'templates', $row['content']);
					$to = cms_join_path($config['assets_path'], 'templates', $fn);
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
			$sql = 'SELECT id,name,description FROM '.CMS_DB_PREFIX.TemplatesGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$from = $db->getArray($sql, $grps);
			$sql = 'SELECT group_id,tpl_id,item_order FROM '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$members = $db->execute($sql, $grps);
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.TemplatesGroup::TABLENAME.' (name,description) VALUES (?,?)';
			$sql2 = 'INSERT INTO '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' (group_id,tpl_id,item_order) VALUES (?,?,?)';
			foreach ($from as $row) {
				if ($row['name']) {
					$name = self::get_unique_name($row['name']);
				} else {
					$name = null;
				}
				$db->execute($sql, [$name, $row['description']]);
				$to = $db->Insert_ID();
				$from = $row['id'];
				foreach ($members as $grprow) {
					if ($grprow['group_id'] == $from) {
						$db->execute($sql2, [$to, $grprow['tpl_id'], $grprow['item_order']]);
					}
				}
			}
			$n += count($from);
		}
		return $n;
	}

	/**
	 * Delete template(s) and/or group(s) but not group members (unless also specified individually)
	 * @since 2.99
	 * @param mixed $ids int | int[] template identifier(s), < 0 means a group
	 * @return int No of items e.g. pages modified | groups deleted
	 */
	public static function operation_delete($ids) : int
	{
		$db = SingleItem::Db();
		$c = 0;
		list($tpls, $grps) = self::items_split($ids);
		if ($grps) {
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$db->execute($sql, $grps);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.TemplatesGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$c = $db->execute($sql, $grps);
		}
		if ($tpls) {
			list($pages, $skips) = self::affected_pages($tpls);
			if ($pages) {
				$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id=NULL WHERE content_id IN ('.str_repeat('?,', count($pages) - 1).'?)';
				$n = (int)$db->execute($sql, array_column($pages, 'content_id'));
			} else {
				$n = 0;
			}
			/* N/A in some db versions
						$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' T WHERE T.id IN ('.str_repeat('?,',count($tpls)-1).'?)';
						$sql .= ' AND T.id NOT IN (SELECT template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id=T.id LIMIT 1)';
			*/
			/* MAYBE AVAILABLE, SLOWER
						$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' T WHERE T.id IN ('.str_repeat('?,',count($tpls)-1).'?)';
						$sql .= ' AND T.id NOT IN (SELECT DISTINCT template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id=T.id)';
			*/
			//* ULTIMATE FALLBACK
			$sql = 'SELECT DISTINCT template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id IN ('.str_repeat('?,', count($tpls) - 1).'?)';
			$keeps = $db->getCol($sql, $tpls);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($tpls) - 1).'?)';
			if ($keeps) {
				$sql .= ' AND id NOT IN ('.implode(',', $keeps).')';
			}
			//*/
			$n = (int)$db->execute($sql, $tpls);
			return ($c > 0) ? $c : $n;
		}
		return $c;
	}

	/**
	 * Delete template(s) and/or group(s) and group-member(s)
	 * @since 2.99
	 * @param mixed $ids int | int[] template identifier(s), < 0 means a group
	 * @return int No of pages modified
	 */
	public static function operation_deleteall($ids) : int
	{
		$db = SingleItem::Db();
		list($tpls, $grps) = self::items_split($ids);
		if ($grps) {
			$sql = 'SELECT DISTINCT tpl_id FROM '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$members = $db->getCol($sql, $grps);
			$tpls = array_unique(array_merge($tpls, $members));
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' WHERE group_id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$db->execute($sql, $grps);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.TemplatesGroup::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($grps) - 1).'?)';
			$db->execute($sql, $grps);
		}
		if ($tpls) {
			list($pages, $skips) = self::affected_pages($tpls);
			if ($pages) {
				$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id=NULL WHERE content_id IN ('.str_repeat('?,', count($pages) - 1).'?)';
				$n = (int)$db->execute($sql, array_column($pages, 'content_id'));
			} else {
				$n = 0;
			}
			$sql = 'SELECT DISTINCT template_id FROM '.CMS_DB_PREFIX.'content WHERE template_id IN ('.str_repeat('?,', count($tpls) - 1).'?)';
			$keeps = $db->getCol($sql, $tpls);
			$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE id IN ('.str_repeat('?,', count($tpls) - 1).'?)';
			if ($keeps) {
				$sql .= ' AND id NOT IN ('.implode(',', $keeps).')';
			}
			$db->execute($sql, $tpls);

			return $n;
		}
		return 0;
	}

	/**
	 * Replace the template wherever used and the user is authorized
	 * @since 2.99
	 * @param int $from template identifier
	 * @param int $to template identifier
	 * @return int No of pages modified
	 */
	public static function operation_replace(int $from, int $to) : int
	{
		if ($from < 1 || $to < 1) {
			return 0;
		}
		$uid = get_userid();
		$modify_all = check_permission($uid, 'Manage All Content') || check_permission($uid, 'Modify Any Page');
		$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id=? WHERE template_id=?';
		if ($modify_all) {
			$args = [$to, $from];
		} else {
			$sql .= ' AND owner_id=?';
			$args = [$to, $from, $uid];
		}
		$db = SingleItem::Db();
		$n = $db->execute($sql, $args);
		return (int)$n;
	}

	/**
	 * Set the template for all pages where the user is authorized
	 * @since 2.99
	 * @param int $to template identifier
	 * @return int No of pages modified
	 */
	public static function operation_applyall(int $to) : int
	{
		if ($to < 1) {
			return 0;
		}
		$uid = get_userid();
		$modify_all = check_permission($uid, 'Manage All Content') || check_permission($uid, 'Modify Any Page');
		$sql = 'UPDATE '.CMS_DB_PREFIX.'content SET template_id=?';
		if ($modify_all) {
			$args = [$to];
		} else {
			$sql .= ' WHERE owner_id=?';
			$args = [$to, $uid];
		}
		$db = SingleItem::Db();
		$n = $db->execute($sql, $args);
		return (int)$n;
	}

	/**
	 * Migrate template(s) from database storage to file
	 * @since 2.99
	 * @param mixed $ids int | int[] template identifier(s)
	 * @return int No. of files processed
	 */
	public static function operation_export($ids) : int
	{
		$n = 0;
		list($tpls, $grps) = self::items_split($ids);
		if ($tpls) {
			$db = SingleItem::Db();
			$sql = 'SELECT id,name,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE contentfile=0 AND id IN ('.str_repeat('?,', count($tpls) - 1).'?)';
			$from = $db->getArray($sql, $tpls);
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=?,contentfile=1 WHERE id=?';
			$config = SingleItem::Config();
			foreach ($from as $row) {
				if ($row['name']) {
					//replicate object::set_content_file()
					$fn = sanitizeVal($row['name'], CMSSAN_FILE).'.'.$row['id'].'.tpl';
					//replicate object::get_content_filename()
					$outfile = cms_join_path($config['assets_path'], 'templates', $fn);
					$res = file_put_contents($outfile, $row['content'], LOCK_EX);
					if ($res !== false) {
						$db->execute($sql, [$fn, $row['id']]);
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
	 * Migrate template(s) from file storage to database
	 * @since 2.99
	 * @param mixed $ids int | int[] template identifier(s)
	 * @return int No. of files processed
	 */
	public static function operation_import($ids) : int
	{
		$n = 0;
		list($tpls, $grps) = self::items_split($ids);
		if ($tpls) {
			$sql = 'SELECT id,name,content FROM '.CMS_DB_PREFIX.self::TABLENAME.' WHERE contentfile=1 AND id IN ('.str_repeat('?,', count($tpls) - 1).'?)';
			$from = $db->getArray($sql, $shts);
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET content=?,contentfile=0 WHERE id=?';
			$config = SingleItem::Config();
			foreach ($from as $row) {
				if ($row['name']) {
					//replicate object::set_content_file()
					$fn = sanitizeVal($row['name'], CMSSAN_FILE).'.'.$row['id'].'.tpl';
					//replicate object::get_content_filename()
					$outfile = cms_join_path($config['assets_path'], 'templates', $fn);
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
	 * @throws RuntimeException
	 */
	protected static function resolve_user($a)
	{
		if (is_numeric($a) && $a >= 1) {
			return (int)$a;
		}
		if (is_string($a)) {
			$a = trim($a);
			if ($a !== '') {
				$ob = SingleItem::UserOperations()->LoadUserByUsername($a);
				if ($ob instanceof User) {
					return $ob->id;
				}
			}
		}
		if ($a instanceof User) {
			return $a->id;
		}
		throw new RuntimeException('Could not resolve '.$a.' to a user id');
	}

	/**
	 * @ignore
	 * @throws RuntimeException
	 */
	protected static function resolve_template($a)
	{
		if (is_numeric($a) && $a >= 1) {
			return (int)$a;
		}
		if (is_string($a)) {
			$a = trim($a);
			if ($a !== '') {
				if (!isset(self::$identifiers)) {
					$db = SingleItem::Db();
					$sql = 'SELECT id,originator,name FROM '.CMS_DB_PREFIX.self::TABLENAME.' ORDER BY id';
					self::$identifiers = $db->getAssoc($sql);
				}
				$parts = explode('::', $a, 2);
				if (count($parts) == 1) {
					foreach (self::$identifiers as $id => &$row) {
						//aka TemplateType::CORE
						if (strcasecmp($row['name'], $a) == 0 && ($row['originator'] == Template::CORE || $row['originator'] == '')) {
							unset($row);
							return $id;
						}
					}
				} else {
					if (!$parts[0] || $parts[0] == 'Core') {
						$parts[0] = Template::CORE;
					}
					foreach (self::$identifiers as $id => &$row) {
						if (strcasecmp($row['name'], $parts[1]) == 0 && $row['originator'] == $parts[0]) {
							unset($row);
							return $id;
						}
					}
				}
				unset($row);
			} else {
				$a = '<missing name>';
			}
		}
		throw new RuntimeException('Could not resolve '.$a.' to a template id');
	}

	/**
	 * Get the template originator from $tpl data directly, or indirectly from the template-type data for $tpl
	 *
	 * @ignore
	 * @param Template $tpl (or perhaps a deprecated CmsLayoutTemplate)
	 * @return mixed string | null
	 */
	protected static function get_originator($tpl)
	{
		$tmp = $tpl->get_originator();
		if ($tmp) {
			return $tmp;
		}
		$id = $tpl->get_type_id();
		if ($id > 0) {
			$db = SingleItem::Db();
			$sql = 'SELECT originator FROM '.CMS_DB_PREFIX.TemplateType::TABLENAME.' WHERE id = ?';
			$dbr = $db->getOne($sql, [$id]);
			if ($dbr) {
				return $dbr;
			}
		}
		return null;
	}

	/**
	 * Create a new populated template object
	 * @internal
	 *
	 * @param array $props
	 * @param mixed $editors optional array of id's | null
	 * @param mixed $groups  optional array of id's | null
	 * @return Template
	 */
	protected static function create_template(array $props, $editors = null, $groups = null) : Template
	{
		if ($editors == null) {
			$db = SingleItem::Db();
			$sql = 'SELECT user_id FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id=? ORDER BY user_id';
			$editors = $db->getCol($sql, [$props['id']]);
		} elseif (is_numeric($editors)) {
			$editors = [(int)$editors];
		}

		if ($groups == null) {
			if (!isset($db)) {
				$db = SingleItem::Db();
			}
			// table aka TemplatesGroup::MEMBERSTABLE
			$sql = 'SELECT DISTINCT group_id FROM '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' WHERE tpl_id=? ORDER BY group_id';
			$groups = $db->getCol($sql, [$props['id']]);
		} elseif (is_numeric($groups)) {
			$groups = [(int)$groups];
		}

		$params = $props + [
			'editors' => $editors,
			'groups' => $groups,
		];
		$tpl = new Template();
		$tpl->set_properties($params);
		return $tpl;
	}

	/**
	 * @ignore
	 */
	protected static function contentfile_operations($tpl)
	{
		if (($ops = $tpl->fileoperations)) {
			foreach ($ops as $row) {
				$fp = cms_join_path(CMS_ASSETS_PATH, 'templates', $row[1]);
				switch ($row[0]) {
					case 'store':
						file_put_contents($fp, $row[2], LOCK_EX);
						break;
					case 'delete':
						unlink($fp);
						break;
					case 'rename':
						$tp = cms_join_path(CMS_ASSETS_PATH, 'templates', $row[2]);
						rename($fp, $tp);
						break;
				}
			}
			$tpl->fileoperations = [];
		}
	}

	/**
	 * Update the data for a template object in the database
	 *
	 * @internal
	 * @param Template $tpl (or perhaps a deprecated CmsLayoutTemplate)
	 * @return Template (or whatever else was provided)
	 */
	protected static function update_template($tpl)
	{
		$db = SingleItem::Db();
		$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET
originator=?,
name=?,
description=?,
type_id=?,
type_dflt=?,
owner_id=?,
listable=?,
contentfile=?
content=?
WHERE id=?';
		$tid = $tpl->id;
		$orig = $tpl->originator;
		$name = $tpl->name;
		$args = [
		  $orig,
		  $name,
		  $tpl->description,
		  $tpl->type_id,
		  $tpl->type_dflt,
		  $tpl->owner_id,
		  $tpl->listable,
		  $tpl->contentfile,
		  $tpl->content,
		  $tid
		];
		$db->execute($sql, $args);
//		if( db error ) throw new SQLException($db->sql.' -- '.$db->errorMsg());
		self::contentfile_operations($tpl);

		if ($tpl->type_dflt) {
			// if it's default for its type, clear default flag for all other records with this type
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
			$db->execute($sql, [$tpl->type_id, $tid]);
//			if( db error ) throw new SQLException($db->sql.' -- '.$db->errorMsg());
		}

		$sql = 'DELETE FROM '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' WHERE tpl_id = ?';
		$db->execute($sql, [$tid]);
//		if( !db affected rows ) { might validly be 0 affected rows
//			throw new SQLException($db->sql.' --5 '.$db->errorMsg());
//		}

		$all = $tpl->get_additional_editors();
		if ($all) {
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' (tpl_id,user_id) VALUES (?,?)';
			foreach ($all as $id) {
				$db->execute($sql, [$tid, (int)$id]);
			}
		}

		$sql = 'DELETE FROM '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' WHERE tpl_id = ?';
		$db->execute($sql, [$tid]);
		$all = $tpl->get_groups();
		if ($all) {
			$stmt = $db->prepare('INSERT INTO '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' (group_id,tpl_id,item_order) VALUES (?,?,?)');
			$i = 1;
			foreach ($all as $id) {
				$db->execute($stmt, [(int)$id, $tid, $i]);
				++$i;
			}
			$stmt->close();
		}

//		SingleItem::LoadedData()->refresh('LayoutTemplates'); if that cache exists
		audit($tid, $orig, 'Template \''.$name.'\' Updated');
		return $tpl; //TODO what use ? event ? chain-methods?
	}

	/**
	 * Insert the data for a template into the database
	 *
	 * @param Template $tpl (or perhaps a deprecated CmsLayoutTemplate)
	 * @return Template template object representing the inserted template
	 */
	protected static function insert_template($tpl) : Template
	{
		$db = SingleItem::Db();
		$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::TABLENAME.' (
originator,
name,
description,
type_id,
type_dflt,
owner_id,
listable,
contentfile,
content) VALUES (?,?,?,?,?,?,?,?,?)';
		$orig = $tpl->originator;
		$name = $tpl->name;
		$args = [
		  $orig,
		  $name,
		  $tpl->description,
		  $tpl->type_id,
		  $tpl->type_dflt,
		  $tpl->owner_id,
		  $tpl->listable,
		  $tpl->contentfile,
		  $tpl->content
		];
		$dbr = $db->execute($sql, $args);
		if (!$dbr) {
			throw new SQLException($db->sql.' --7 '.$db->errorMsg());
		}

		$tid = $db->Insert_ID();
		$tpl->set_id($tid);

		self::contentfile_operations($tpl);

		if ($tpl->type_dflt) {
			// if it's default for its type, clear default flag for all other records with this type
			$sql = 'UPDATE '.CMS_DB_PREFIX.self::TABLENAME.' SET type_dflt = 0 WHERE type_id = ? AND type_dflt = 1 AND id != ?';
			$db->execute($sql, [$tpl->get_type_id(), $tid]);
//			if( db error ) throw new SQLException($db->sql.' -- '.$db->errorMsg());
		}

		$editors = $tpl->get_additional_editors();
		if ($editors) {
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.self::ADDUSERSTABLE.' (tpl_id,user_id) VALUES (?,?)';
			foreach ($editors as $id) {
				//TODO use prepared statement
				$db->execute($sql, [$tid, (int)$id]);
			}
		}

		$groups = $tpl->get_groups();
		if ($groups) {
			$sql = 'INSERT INTO '.CMS_DB_PREFIX.TemplatesGroup::MEMBERSTABLE.' (group_id,tpl_id,item_order) VALUES (?,?,?)';
			$i = 1;
			foreach ($groups as $id) {
				$db->execute($sql, [(int)$id, $tid, $i]);
				++$i;
			}
		}

//		SingleItem::LoadedData()->refresh('LayoutTemplates'); if that cache exists

		audit($tid, $orig, 'Template \''.$name.'\' Created');

		// return a fresh instance of the object (e.g. to pass to event handlers ??)
		$row = $tpl->get_properties();
		$tpl = self::create_template($row, $editors, $groups);
		return $tpl;
	}

	protected static function filter_editors(int $id, array $all) : array
	{
		$out = [];
		foreach ($all as $row) {
			if ($row['tpl_id'] == $id) {
				$out[] = $row['user_id'];
			}
		}
		return $out;
	}

	protected static function filter_groups(int $id, array $all) : array
	{
		$out = [];
		foreach ($all as $row) {
			if ($row['tpl_id'] == $id) {
				$out[] = $row['group_id'];
			}
		}
		return $out;
	}

	/**
	 * Get data for pages to be operated on or skipped
	 * @param mixed $ids int template id | id's array | string '*'
	 * @return 2-member array
	 *  [0] = array, each row having 'content_id', 'template_id'
	 *  [1] = no. of unusable pages
	 */
	protected static function affected_pages($ids)
	{
		$uid = get_userid();
		$modify_all = check_permission($uid, 'Manage All Content') || check_permission($uid, 'Modify Any Page');
		$sql = 'SELECT content_id,template_id FROM '.CMS_DB_PREFIX.'content';
		if ($ids != '*') {
			$fillers = (is_array($ids)) ? ' IN ('.str_repeat('?,', count($ids) - 1).'?)' : '=?';
			$sql .= ' WHERE template_id'.$fillers;
			$args = (is_array($ids)) ? $ids : [$ids];
			if (!$modify_all) {
				$sql .= ' AND owner_id=?';
				$args[] = $uid;
			}
		} elseif (!$modify_all) {
			$sql .= ' WHERE owner_id=?';
			$args = [$uid];
		} else {
			$args = null;
		}
		$db = SingleItem::Db();
		$valid = $db->getArray($sql, $args);

		if (!$modify_all) {
			if ($ids != '*') {
				$sql = 'SELECT COUNT(1) AS num FROM '.CMS_DB_PREFIX.'content WHERE template_id'.$fillers;
				$args = (is_array($ids)) ? $ids : [$ids];
			} else {
				$sql = 'SELECT COUNT(1) AS num FROM '.CMS_DB_PREFIX.'content';
				$args = null;
			}
			$all = $db->getOne($sql, $args);
			$other = $all - count($valid);
		} else {
			$other = 0;
		}
		return [$valid, $other];
	}

	/**
	 * Partition $ids into template id(s) and/or template-group id(s)
	 * @ignore
	 * $param mixed $ids int | int[], group ids < 0
	 * @return 2-member array [0] = tpl ids, [1] = group ids (> 0)
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
} // class
