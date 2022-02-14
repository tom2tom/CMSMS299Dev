<?php
/*
DesignManager module installation process
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\Database\DataDictionary;
use CMSMS\Events;
use CMSMS\Group;

if (empty($this) || !($this instanceof DesignManager)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$dict = new DataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MyISAM CHARACTER SET ascii'];

$tbl = CMS_DB_PREFIX.'module_designs'; // aka Design::TABLENAME
$flds = '
id I UNSIGNED AUTO KEY,
name C(50) CHARACTER SET utf8mb4 NOTNULL,
description C(1500) CHARACTER SET utf8mb4,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$sqlarray = $dict->CreateIndexSQL('i_name', $tbl, 'name', ['UNIQUE']);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$tbl = CMS_DB_PREFIX.'module_designs_tpl'; // aka Design::TPLTABLE
$flds = '
id I UNSIGNED AUTO KEY,
design_id I UNSIGNED NOTNULL,
tpl_id I UNSIGNED NOTNULL,
tpl_order I1 UNSIGNED DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$tbl = CMS_DB_PREFIX.'module_designs_css'; // aka Design::CSSTABLE
$flds = '
id I UNSIGNED AUTO KEY,
design_id I UNSIGNED NOTNULL,
css_id I UNSIGNED NOTNULL,
css_order I1 UNSIGNED DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

//$this->SetPreference('lock_timeout', 60);
//$this->SetPreference('lock_refresh', 120);

//$this->CreatePermission('Add Templates', $this->Lang('perm_add'));
$this->CreatePermission('Manage Designs', $this->Lang('perm_designs'));
//$this->CreatePermission('Manage Stylesheets', $this->Lang('perm_styles'));
//$this->CreatePermission('Modify Templates', $this->Lang('perm_modify'));

$group = new Group();
$group->name = 'Designer';
$group->description = $this->Lang('group_desc');
$group->active = 1;
try {
	Events::SendEvent('Core', 'AddGroupPre', ['group'=>&$group]);
	$group->Save();
	Events::SendEvent('Core', 'AddGroupPost', ['group'=>&$group]);

	$group->GrantPermission('Add Templates');
	$group->GrantPermission('Manage All Content'); //CHECKME ContentManager race when 1st installing?
	$group->GrantPermission('Manage Designs');
	$group->GrantPermission('Manage My Account');
	$group->GrantPermission('Manage My Bookmarks');
	$group->GrantPermission('Manage My Settings');
	$group->GrantPermission('Manage Stylesheets');
	$group->GrantPermission('Modify Files');
	$group->GrantPermission('Modify Templates');
	$group->GrantPermission('View Tag Help');
} catch (Throwable $t) {
	//TODO delete group & try again
}
// register events for which other parts of the system may listen
// these have been migrated from the main installer
// pre 2.99 these events' originator was 'Core'
foreach([
	'AddDesignPost',
	'AddDesignPre',
	'DeleteDesignPost',
	'DeleteDesignPre',
	'EditDesignPost',
	'EditDesignPre',
] as $name) {
	Events::CreateEvent('DesignManager',$name);
}
