<?php
/*
DesignManager module uninstallation process.
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\Database\DataDictionary;
use CMSMS\Events;
use CMSMS\Group;
use DesignManager\Design;

if (empty($this) || !($this instanceof DesignManager)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$dict = new DataDictionary($db);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.Design::TABLENAME);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.Design::TPLTABLE);
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.Design::CSSTABLE);
$dict->ExecuteSQLArray($sqlarray);

$group = new Group();
$group->name = 'Designer';
try {
	Events::SendEvent('Core', 'DeleteGroupPre', ['group'=>&$group]);
	if ($group->Delete()) {
		Events::SendEvent('Core', 'DeleteGroupPost', ['group'=>&$group]);
	}
} catch (Throwable $t) {
	return $t->GetMessage();
}

//$this->RemovePreference();

$this->RemovePermission('Manage Designs');

// unregister events
// pre 2.99 these events' originator was 'Core'
foreach([
 'AddDesignPost',
 'AddDesignPre',
 'DeleteDesignPost',
 'DeleteDesignPre',
 'EditDesignPost',
 'EditDesignPre',
] as $name) {
	Events::RemoveEvent('DesignManager',$name);
}
