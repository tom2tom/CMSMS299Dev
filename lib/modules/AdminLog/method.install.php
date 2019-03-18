<?php
/*
AdminLog module action: install process
Copyright (C) 2017-2019 CMS Made Simple Foundation <foundationcmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use AdminLog\storage;
use CMSMS\Database\DataDictionary;

if (!isset($gCms)) {
    exit;
}

$dict = new DataDictionary($db);
$taboptarray = ['mysqli' => 'CHARACTER SET utf8 COLLATE utf8_general_ci'];

$flds = '
timestamp I NOTNULL,
severity  I NOTNULL DEFAULT 0,
uid I,
ip_addr C(40),
username C(50),
subject C(255),
msg X NOTNULL,
item_id I
';
$sqlarr = $dict->CreateTableSQL(storage::TABLENAME, $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarr);

$this->CreatePermission('View Admin Log', 'View Admin Log');
$this->CreatePermission('Clear Admin Log', 'Clear Admin Log');

$this->SetPreference('lifetime', 3600*24*31); //log entries only live for 60 days
