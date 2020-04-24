<?php
# DesignManager module upgrade process.
# Copyright (C) 2019-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\Database\DataDictionary;
use DesignManager\Design;

if (!function_exists('cmsms')) exit;

if (version_compare($oldversion, '2.0') < 0) {
	$dict = new DataDictionary($db);

	$tbl = CMS_DB_PREFIX.Design::TABLENAME;
	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_designs', $tbl);
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->DropColumnSQL($tbl, 'dflt');
	$dict->ExecuteSQLArray($sqlarray);
	// migrate timestamps to datetime
	$sqlarray = $dict->AddColumnSQL($tbl, 'create_date DT DEFAULT CURRENT_TIMESTAMP');
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->AddColumnSQL($tbl, 'modified_date DT ON UPDATE CURRENT_TIMESTAMP');
	$dict->ExecuteSQLArray($sqlarray);
	$data = $db->GetArray('SELECT id,created,modified FROM '.$tbl);
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
			$db->Execute($sql, [$created,$modified,$row['id']]);
		}
		unset($row);
	}
	$sqlarray = $dict->DropColumnSQL($tbl, 'created');
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->DropColumnSQL($tbl, 'modified');
	$dict->ExecuteSQLArray($sqlarray);

	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_design_tplassoc', CMS_DB_PREFIX.Design::TPLTABLE);
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->AddColumnSQL(CMS_DB_PREFIX.Design::TPLTABLE, 'tpl_order I(1) UNSIGNED DEFAULT 0');
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->RenameTableSQL(CMS_DB_PREFIX.'layout_design_cssassoc', CMS_DB_PREFIX.Design::CSSTABLE);
	$dict->ExecuteSQLArray($sqlarray);
	$sqlarray = $dict->RenameColumnSQL(CMS_DB_PREFIX.Design::CSSTABLE, 'item_order', 'css_order I(1) UNSIGNED DEFAULT 0');
	$dict->ExecuteSQLArray($sqlarray);
}
