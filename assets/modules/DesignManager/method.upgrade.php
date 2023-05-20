<?php
/*
DesignManager module upgrade process.
Copyright (C) 2019-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

//use CMSMS\Database\DataDictionary;
//use DesignManager\Design;

if (empty($this) || !($this instanceof DesignManager)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

if (version_compare($oldversion, '2.0') < 0) {
	// remove invalid members from designs tables e.g. non-core templates
	$pre = CMS_DB_PREFIX;
	$sql = <<<EOS
SELECT DT.tpl_id FROM {$pre}module_designs_tpl DT
LEFT JOIN {$pre}layout_templates LT ON DT.tpl_id = LT.id
WHERE LT.originator != '__CORE__' AND LT.originator IS NOT NULL
EOS;
	$ids = $db->getCol($sql);
	if ($ids) {
		$sql = "DELETE FROM {$pre}module_designs_tpl WHERE tpl_id IN (".implode(',', $ids).')';
		$db->execute($sql);
	}

/* ALL THIS IS ALREADY DONE IN CMSMS2.99 UPGRADE
	$dict = new DataDictionary($db);

	$tbl = CMS_DB_PREFIX.Design::TABLENAME;
	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_designs', $tbl);
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->DropColumnSQL($tbl, 'dflt');
	$dict->ExecuteSQLArray($sqlarray);
	// migrate timestamps to datetime
	$sqlarray = $dict->AddColumnSQL($tbl, 'create_date DT NOTNULL DEFAULT CURRENT_TIMESTAMP');
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->AddColumnSQL($tbl, 'modified_date DT ON UPDATE CURRENT_TIMESTAMP');
	$dict->ExecuteSQLArray($sqlarray);
	$data = $db->getArray('SELECT id,created,modified FROM '.$tbl);
	if ($data) {
		$sql = 'UPDATE '.$tbl.' SET create_date=?,modified_date=? WHERE id=?';
		$dt = new DateTime('@0',NULL);
		$fmt = 'Y-m-d H:i:s';
		$now = time();
		foreach ($data as &$row) {
			$t1 = (int)$row['created'];
			$t2 = max($t1,(int)$row['modified']);
			if ($t1 == 0) { $t1 = $t2; }
			if ($t1 == 0) { $t1 = $t2 = $now; }
			$dt->setTimestamp($t1);
			$created = $dt->format($fmt);
			$dt->setTimestamp($t2);
			$modified = $dt->format($fmt);
			$db->execute($sql, [$created,$modified,$row['id']]);
		}
		unset($row);
	}
	$sqlarray = $dict->DropColumnSQL($tbl, 'created');
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->DropColumnSQL($tbl, 'modified');
	$dict->ExecuteSQLArray($sqlarray);

	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_design_tplassoc', CMS_DB_PREFIX.Design::TPLTABLE);
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.Design::TPLTABLE, 'tpl_order I1 UNSIGNED DEFAULT 0');
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_design_cssassoc', CMS_DB_PREFIX.Design::CSSTABLE);
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->RenameColumnSQL(CMS_DB_PREFIX.Design::CSSTABLE, 'item_order', 'css_order', 'I1 UNSIGNED DEFAULT 0');
	$dict->ExecuteSQLArray($sqlarray);
*/
}
