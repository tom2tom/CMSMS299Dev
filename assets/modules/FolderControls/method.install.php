<?php
/*
FolderControls module method: install
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

if (!function_exists('cmsms')) exit;

$dbdict = NewDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

$flds = '
id I AUTO KEY,
name C(48) NOTNULL INDEX(UNIQUE),
toppath C(255),
data X(16384),
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'module_excontrols', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);

/*
$sqlarray = $dbdict->CreateIndexSQL('idx_excontrols',
    CMS_DB_PREFIX.'module_excontrols', 'name', ['UNIQUE']);
$dbdict->ExecuteSQLArray($sqlarray);
*/
/* TODO migrate old data if present
$sql = 'SELECT * FROM '.CMS_DB_PREFIX.'mod_filepicker_profiles ORDER BY name';
$data = $db->GetArray($sql);
if ($data) {
	$sql = 'INSERT INTO '.CMS_DB_PREFIX.'module_excontrols (name,toppath,data,create_date,modified_date) VALUES (?,?,?,?,?)';
	foreach ($data as $row) {
	    $arr = unserialize($row['data']);
	    $top = $arr['top'] ?? ''; //CHECKME root-relative
	    unset($arr['top']);
	    $raw = json_encode($arr);
	    $created = $db->DbTimeStamp($row['create_date']);
	    $modified = $db->DbTimeStamp($row['modified_date']);
	    $db->Execute($sql,[$row['name'],$top,$raw,$created,$modified]);
	}
}
*/
