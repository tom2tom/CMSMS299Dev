<?php
#Template-related methods for modules
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
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

use CMSMS\TemplateOperations;

/**
 * Template-related methods for modules
 *
 * @since		1.0
 * @package		CMS
 * @license GPL
 */

/**
 * Return a sorted array of database-stored-template names.
 * @access private
 *
 * @param CMSModule $modinstance Current module
 * @param string $mod_name Optional enables override of $modinstance
 * @return array
 */

function cms_module_ListTemplates(&$modinstance, $mod_name = '')
{
	$db = CmsApp::get_instance()->GetDb();
	if (!$mod_name) {
		$mod_name = $modinstance->GetName();
	}
	$query = 'SELECT name FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE listable!=0 AND originator=? ORDER BY name';
	return $db->GetCol($query, [$mod_name]);
}

/**
 * Return the content of a database-stored template.
 * This should be used for admin purposes only, as it doesn't implement smarty caching.
 * @access private
 *
 * @param CMSModule $modinstance Current module
 * @param string $tpl_name Template name
 * @param string $mod_name Optional enables override of $modinstance
 * @return string
 */
function cms_module_GetTemplate(&$modinstance, $tpl_name, $mod_name = '')
{
	$db = CmsApp::get_instance()->GetDb();
	if (!$mod_name) {
		$mod_name = $modinstance->GetName();
	}
	$query = 'SELECT content FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE name=? AND originator=?';
	return $db->GetOne($query, [$tpl_name, $mod_name]);
}

/**
 * Return contents of the template that resides in path-to/ModuleName/templates/{template_name}.tpl
 * @access private
 *
 * @param CMSModule $modinstance Current module
 * @param string $tpl_name Template name
 * @param since 2.3 string $mod_name Optional enables override of $modinstance
 * @return string or false
 */
function cms_module_GetTemplateFromFile(&$modinstance, $tpl_name, $mod_name = '')
{
	if (strpos($tpl_name, '..') !== false) return;

	if (!endswith($tpl_name,'.tpl')) $tpl_name .= '.tpl';

	$template = '';
	if ($mod_name) {
		$myname = $modinstance->GetName();
		if ($mod_name != $myname) {
			$ob = cms_utils::get_module($mod_name);
			if ($ob) {
				$template = $ob->GetModulePath().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$tpl_name;
			}
		}
	}
	if (!$template) {
		$template = $modinstance->GetModulePath().DIRECTORY_SEPARATOR.'templates'.DIRECTORY_SEPARATOR.$tpl_name;
	}
	if (is_file($template)) {
		return @file_get_contents($template);
	}
}

/**
 * @access private
 *
 * @param CMSModule $modinstance Current module
 * @param string $tpl_name Template name
 * @param string $content
 * @param string $mod_name Optional enables override of $modinstance
 */
function cms_module_SetTemplate(&$modinstance, $tpl_name, $content, $mod_name = '')
{
	$db = CmsApp::get_instance()->GetDb();
	if (!$mod_name) {
		$mod_name = $modinstance->GetName();
	}
	$now = time();
	$pref = CMS_DB_PREFIX;
	$tbl = CMS_DB_PREFIX.TemplateOperations::TABLENAME;

	$query = <<<EOS
SELECT id FROM {$pref}layout_tpl_type WHERE originator=? AND name='Moduleaction'
EOS;
	$tt = (int)$db->GetOne($query, [$mod_name]);
	if (!$tt) {
		$query = <<<EOS
INSERT INTO {$pref}layout_tpl_type (
originator,
name,
description,
owner) VALUES (?,'Moduleaction',?,-1)
EOS;
		$db->Execute($query, [$mod_name, 'Action templates for module: '.$mod_name, $now, $now]);
		$tt = $db->insert_id();
	}
	// upsert TODO MySQL ON DUPLICATE KEY UPDATE useful here?
	$query = <<<EOS
UPDATE {$tbl} SET content=?,modified=? WHERE originator=? AND name=?
EOS;
	$db->Execute($query, [$content,$now,$mod_name,$tpl_name]);
	$query = <<<EOS
INSERT INTO {$tbl} (originator,name,content,type_id,created,modified) SELECT ?,?,?,?,?,?
FROM (SELECT 1 AS dmy) Z
WHERE NOT EXISTS (SELECT 1 FROM {$tbl} T WHERE T.originator=? AND T.name=?)
EOS;
	$db->Execute($query,
	 [$mod_name, $tpl_name, $content, $tt, $now, $now, $mod_name, $tpl_name]);
}

/**
 * Delete named template
 * @access private
 *
 * @param CMSModule $modinstance Current module
 * @param string $tpl_name Optional template name. Delete all, if name is empty
 * @param string $mod_name Optional enables override of $modinstance
 * @return bool
 */
function cms_module_DeleteTemplate(&$modinstance, $tpl_name = '', $mod_name = '')
{
	$db = CmsApp::get_instance()->GetDb();
	if (!$mod_name) {
		$mod_name = $modinstance->GetName();
	}
	$query = 'DELETE FROM '.CMS_DB_PREFIX.TemplateOperations::TABLENAME.' WHERE originator=?';
	$vars = [$mod_name];
	if ($tpl_name) {
		$query .= 'AND name=?';
		$vars[] = $tpl_name;
	}
	$result = $db->Execute($query, $vars);

	return ($result !== false);
}