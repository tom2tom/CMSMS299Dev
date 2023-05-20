<?php
/*
Template-related methods for modules
Copyright (C) 2004-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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
namespace CMSMS\module_support;

use CMSMS\Lone;
use CMSMS\TemplateOperations;
use CMSMS\Utils;
use const CMS_DB_PREFIX;
use function endswith;

/**
 * Template-related methods for modules.
 *
 * @internal
 * @since   1.0
 * @package CMS
 * @license GPL
 */
/**
 * Return a sorted array of database-stored-template names.
 *
 * @param $mod The current module-object
 * @param string $modname Optional name of a module to use instead of $mod
 * @return array
 */
function ListTemplates($mod, $modname = '')
{
	$db = Lone::get('Db');
	if (!$modname) {
		$modname = $mod->GetName();
	}
	$query = 'SELECT `name` FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE listable!=0 AND originator=? ORDER BY `name`';
	return $db->getCol($query, [$modname]);
}

/**
 * Return the content of a database-stored template.
 * This should be used for admin purposes only, as it doesn't implement smarty caching.
 *
 * @param $mod The current module-object
 * @param string $tpl_name Template name
 * @param string $modname Optional name of a module to use instead of $mod
 * @return string
 */
function GetTemplate($mod, $tpl_name, $modname = '')
{
	$db = Lone::get('Db');
	if (!$modname) {
		$modname = $mod->GetName();
	}
	$query = 'SELECT content FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE `name`=? AND originator=?';
	return $db->getOne($query, [$tpl_name, $modname]);
}

/**
 * Return contents of the template that resides in path-to/ModuleName/templates/{tpl_name}.tpl
 *
 * @param $mod The current module-object
 * @param string $tpl_name Template name
 * @param since 3.0 string $modname Optional name of a module to use instead of $mod
 * @return mixed string | null
 */
function GetTemplateFromFile($mod, $tpl_name, $modname = '')
{
	if (strpos($tpl_name, '..') !== false) return;

	if (!endswith($tpl_name,'.tpl')) $tpl_name .= '.tpl';

	$template = '';
	if ($modname) {
		$myname = $mod->GetName();
		if ($modname != $myname) {
			$ob = Utils::get_module($modname);
			if ($ob) {
				$template = $ob->GetModulePath().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$tpl_name;
			}
		}
	}
	if (!$template) {
		$template = $mod->GetModulePath().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$tpl_name;
	}
	if (is_file($template)) {
		return @file_get_contents($template);
	}
}

/**
 * Save a template
 *
 * @param $mod The current module-object
 * @param string $tpl_name Template name
 * @param string $content
 * @param string $modname Optional name of a module to use instead of $mod
 */
function SetTemplate($mod, $tpl_name, $content, $modname = '')
{
	$db = Lone::get('Db');
	if (!$modname) {
		$modname = $mod->GetName();
	}
	$now = time();
    $longnow = date('Y-M-d H:i:s', $now);
	$pref = CMS_DB_PREFIX;
	$tbl = CMS_DB_PREFIX.TemplateOperations::TABLENAME;

	$query = <<<EOS
SELECT id FROM {$pref}layout_tpl_types WHERE originator=? AND `name`='moduleactions'
EOS;
	$tt = (int)$db->getOne($query, [$modname]);
	if (!$tt) {
		$query = <<<EOS
INSERT INTO {$pref}layout_tpl_types
(originator,`name`,description,owner_id) VALUES (?,'moduleactions',?,1)
EOS;
		$db->execute($query, [$modname, 'Action templates for module: '.$modname, $now, $now]); //TODO timestamp for description, owner_id values ?
		$tt = $db->insert_id();
	}
	// upsert TODO MySQL ON DUPLICATE KEY UPDATE useful here?
	$query = <<<EOS
UPDATE {$tbl} SET content=?,modified_date=? WHERE originator=? AND `name`=?
EOS;
	$db->execute($query, [$content,$longnow,$modname,$tpl_name]);
	//just in case (originator,name) is not unique-indexed by the db
	$query = <<<EOS
INSERT INTO {$tbl} (originator,`name`,content,type_id,create_date,modified_date) SELECT ?,?,?,?,?,?
FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$tbl} T WHERE T.originator=? AND T.`name`=?)
EOS;
	$db->execute($query,
	 [$modname, $tpl_name, $content, $tt, $longnow, $longnow, $modname, $tpl_name]);
}

/**
 * Delete named template or all
 *
 * @param $mod The current module-object
 * @param string $tpl_name Optional template name. Delete all, if this is empty
 * @param string $modname Optional name of a module to use instead of $mod
 * @return bool
 */
function DeleteTemplate($mod, $tpl_name = '', $modname = '')
{
	$db = Lone::get('Db');
	$query = 'DELETE FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE originator=?';
	if (!$modname) {
		$modname = $mod->GetName();
	}
	$vars = [$modname];
	if ($tpl_name) {
		$query .= 'AND `name`=?';
		$vars[] = $tpl_name;
	}
	$result = $db->execute($query, $vars);

	return ($result != false);
}
