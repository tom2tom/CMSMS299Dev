<?php
# DesignManager module installation process
# Copyright (C) 2012-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
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
use CMSMS\Events;
use CMSMS\Group;

if (!isset($gCms)) {
    exit;
}

$dict = new DataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

$tbl = CMS_DB_PREFIX.'module_designs'; // aka Design::TABLENAME
//created I, <<< DT replaced 2.3
//modified I <<< DT ditto
//dflt I(1) DEFAULT 0, 2.3 removed, irrelevant
$flds = '
id I(1) UNSIGNED AUTO KEY,
name C(64) NOT NULL,
description X(1024),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$sqlarray = $dict->CreateIndexSQL('idx_dsn', $tbl, 'name', ['UNIQUE']);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$tbl = CMS_DB_PREFIX.'module_designs_tpl'; // aka Design::TPLTABLE
$flds = '
id I(2) UNSIGNED AUTO KEY,
design_id I(2) UNSIGNED NOT NULL KEY,
tpl_id I(2) UNSIGNED NOT NULL KEY,
tpl_order I(1) UNSIGNED DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL($tbl, $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);
/* useless
$sqlarray = $dict->CreateIndexSQL('idx_dsntpl', $tbl, 'tpl_id');
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;
*/
$tbl = CMS_DB_PREFIX.'module_designs_css'; // aka Design::CSSTABLE
//CHECKME separate index on css_id field ?
$flds = '
id I(2) UNSIGNED AUTO KEY,
design_id I(2) UNSIGNED NOT NULL KEY,
css_id I(2) UNSIGNED NOT NULL KEY,
css_order I(1) UNSIGNED DEFAULT 0
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
} catch (Exception $e) {
    //TODO delete group & try again
}
// register events for which other parts of the system may listen
// these have been migrated from the main installer
foreach([
 'AddDesignPost',
 'AddDesignPre',
/*
 'AddStylesheetPost',
 'AddStylesheetPre',
 'AddTemplatePost',
 'AddTemplatePre',
 'AddTemplateTypePost',
 'AddTemplateTypePre',
*/
 'DeleteDesignPost',
 'DeleteDesignPre',
/*
 'DeleteStylesheetPost',
 'DeleteStylesheetPre',
 'DeleteTemplatePost',
 'DeleteTemplatePre',
 'DeleteTemplateTypePost',
 'DeleteTemplateTypePre',
*/
 'EditDesignPost',
 'EditDesignPre',
/*
 'EditStylesheetPost',
 'EditStylesheetPre',
 'EditTemplatePost',
 'EditTemplatePre',
 'EditTemplateTypePost',
 'EditTemplateTypePre',

 'StylesheetPostCompile',
 'StylesheetPostRender',
 'StylesheetPreCompile',

 'TemplatePostCompile',
 'TemplatePreCompile',
 'TemplatePreFetch',
*/
] as $name) {
	// deprecated since 2.3 event originator is 'Core', change to 'DesignManager'
    Events::CreateEvent('Core',$name);
}
