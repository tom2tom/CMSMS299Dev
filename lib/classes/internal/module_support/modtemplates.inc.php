<?php
#Template-related methods for modules
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
/*
	$retresult = [];

	$query = 'SELECT * from '.CMS_DB_PREFIX.'module_templates WHERE module_name = ? ORDER BY template_name ASC';
	$result = $db->Execute($query, [$mod_name != ''?$mod_name:$modinstance->GetName()]);

	while (isset($result) && !$result->EOF) {
		$retresult[] = $result->fields['template_name'];
		$result->MoveNext();
	}

	return $retresult;
*/
	if (!$mod_name) {
		$mod_name = $modinstance->GetName();
	}
	$tbl = CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME;
	$query = <<<EOS
SELECT name FROM {$tbl} WHERE listable!=0 AND originator=? ORDER BY name
EOS;
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
/*
	$query = 'SELECT * from '.CMS_DB_PREFIX.'module_templates WHERE module_name = ? and template_name = ?';
	$result = $db->Execute($query, [$mod_name != ''?$mod_name:$modinstance->GetName(), $tpl_name]);

	if ($result && $result->RecordCount() > 0) {
		$row = $result->FetchRow();
		return $row['content'];
	}

	return '';
*/
	if (!$mod_name) {
		$mod_name = $modinstance->GetName();
	}
	$tbl = CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME;
	$query = <<<EOS
SELECT content FROM {$tbl} WHERE name=? AND originator=?
EOS;
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
/*
	$query = 'SELECT module_name FROM '.CMS_DB_PREFIX.'module_templates WHERE module_name = ? and template_name = ?';
	$result = $db->Execute($query, [$mod_name != ''?$mod_name:$modinstance->GetName(), $tpl_name]);

	$time = $db->DbTimeStamp(time());
	if ($result && $result->RecordCount() < 1) {
		$query = 'INSERT INTO '.CMS_DB_PREFIX.'module_templates (module_name, template_name, content, create_date, modified_date) VALUES (?,?,?,'.$time.','.$time.')';
		$db->Execute($query, [$mod_name != ''?$mod_name:$modinstance->GetName(), $tpl_name, $content]);
	}
	else {
		$query = 'UPDATE '.CMS_DB_PREFIX.'module_templates SET content = ?, modified_date = '.$time.' WHERE module_name = ? AND template_name = ?';
		$db->Execute($query, [$content, $mod_name != ''?$mod_name:$modinstance->GetName(), $tpl_name]);
	}
*/
	if (!$mod_name) {
		$mod_name = $modinstance->GetName();
	}
	$now = time();
	$insert = false;
	$pref = CMS_DB_PREFIX;
	$tbl = CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME;

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
owner,
created,
modified) VALUES (?,'Moduleaction',?,-1,?,?)
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
/*
	$parms = [$mod_name != ''?$mod_name:$modinstance->GetName()];
	$query = "DELETE FROM ".CMS_DB_PREFIX."module_templates WHERE module_name = ?";
	if( $tpl_name != '' ) {
		$query .= 'AND template_name = ?';
	    $parms[] = $tpl_name;
	}
	$result = $db->Execute($query, $parms);
*/
	if (!$mod_name) {
		$mod_name = $modinstance->GetName();
	}
	$tbl = CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME;
	$query = <<<EOS
DELETE FROM {$tbl} WHERE originator=?
EOS;
	$vars = [$mod_name];
	if ($tpl_name) {
		$query .= 'AND name=?';
		$vars[] = $tpl_name;
	}
	$result = $db->Execute($query, $vars);

	return ($result !== false);
}

