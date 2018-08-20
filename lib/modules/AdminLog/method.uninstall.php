<?php
/*
AdminLog module uninstall process
Copyright (C) 2017-2018 CMS Made Simple Foundation <foundationcmsmadesimple.org>
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

if (!isset($gCms)) exit;
$db = $this->GetDb();
$dict = NewDataDictionary($db);

$sqlarr = $dict->DropTableSQL( storage::table_name() );
$dict->ExecuteSQLArray( $sqlarr );

$this->RemovePermission('View Admin Log');
$this->RemovePermission('Clear Admin Log');
