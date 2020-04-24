<?php
/*
AdminLog module uninstall process
Copyright (C) 2017-2020 CMS Made Simple Foundation <foundationcmsmadesimple.org>
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

if (!isset($gCms)) exit;
$dict = new DataDictionary($db);

$sqlarr = $dict->DropTableSQL( CMS_DB_PREFIX.storage::TABLENAME );
$dict->ExecuteSQLArray( $sqlarr );

$this->RemovePermission('View Admin Log');
$this->RemovePermission('Clear Admin Log');

$this->RemovePreference();
