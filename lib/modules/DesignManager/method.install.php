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

//use CMSMS\Database\DataDictionary;
use CMSMS\Events;
use CMSMS\Group;

if (!isset($gCms)) {
    exit;
}

/* these tables are mainly, but not exclusively, used by this module, so processed with core
$dict = new DataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

$flds = '
id I KEY AUTO,
originator C(32) NOT NULL,
name C(96) NOT NULL,
dflt_contents X2,
description X(1024),
lang_cb C(255),
dflt_content_cb C(255),
help_content_cb C(255),
has_dflt I(1) DEFAULT 0,
requires_contentblocks I(1),
one_only I(1),
owner I,
created I,
modified I
';
$sqlarray = $dict->CreateTableSQL(
	CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME,
	$flds,
	$taboptarray
);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$sqlarray = $dict->CreateIndexSQL('idx_layout_tpl_type_1',
	CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME, 'originator,name', ['UNIQUE']);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$flds = '
id I KEY AUTO,
name C(96) NOT NULL,
description X(1024),
item_order I(4) DEFAULT 0,
modified I
';
$sqlarray = $dict->CreateTableSQL(
	CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME,
	$flds,
	$taboptarray
);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$sqlarray = $dict->CreateIndexSQL('idx_layout_tpl_cat_1',
	CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME, 'name', ['UNIQUE']);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$flds = '
category_id I NOT NULL,
tpl_id I NOT NULL,
tpl_order I(4) DEFAULT 0
';
$sqlarray = $dict->CreateTableSQL(
	CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE,
	$flds,
	$taboptarray
);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$sqlarray = $dict->CreateIndexSQL('idx_layout_cat_tplasoc_1',
	CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE, 'tpl_id');
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$flds = '
id I KEY AUTO,
name C(96) NOT NULL,
content X2,
description X(1024),
media_type C(255),
media_query X(16384),
created I,
modified I
';
$sqlarray = $dict->CreateTableSQL(
	CMS_DB_PREFIX.CmsLayoutStylesheet::TABLENAME,
	$flds,
	$taboptarray
);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$sqlarray = $dict->CreateIndexSQL('idx_layout_css_1',
	CMS_DB_PREFIX.CmsLayoutStylesheet::TABLENAME, 'name', ['UNIQUE']);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$flds = '
id I KEY AUTO,
name C(96) NOT NULL,
description X(1024),
dflt I(1) DEFAULT 0,
created I,
modified I
';
$sqlarray = $dict->CreateTableSQL(
	CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME,
	$flds,
	$taboptarray
);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$sqlarray = $dict->CreateIndexSQL('idx_layout_dsn_1',
	CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME, 'name', ['UNIQUE']);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$flds = '
design_id I KEY NOT NULL,
tpl_id I KEY NOT NULL,
tpl_order I(4) DEFAULT 0
';
//CHECKME separate index on tpl_id field ?
$sqlarray = $dict->CreateTableSQL(
	CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE,
	$flds,
	$taboptarray
);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$sqlarray = $dict->CreateIndexSQL('idx_dsnassoc1',
	CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE, 'tpl_id');
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;

$flds = '
design_id I KEY NOT NULL,
css_id I KEY NOT NULL,
item_order I(4) DEFAULT 0
';
//CHECKME separate index on css_id field ?
$sqlarray = $dict->CreateTableSQL(
	CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE,
	$flds,
	$taboptarray
);
$res = $dict->ExecuteSQLArray($sqlarray);
if ($res != 2) return false;
*/

$this->SetPreference('lock_timeout', 60);
$this->SetPreference('lock_refresh', 120);

$this->CreatePermission('Add Templates', $this->Lang('perm_add'));
$this->CreatePermission('Manage Designs', $this->Lang('perm_designs'));
$this->CreatePermission('Manage Stylesheets', $this->Lang('perm_styles'));
$this->CreatePermission('Modify Templates', $this->Lang('perm_modify'));

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

 'AddStylesheetPost',
 'AddStylesheetPre',
 'AddTemplatePost',
 'AddTemplatePre',
 'AddTemplateTypePost',
 'AddTemplateTypePre',

 'DeleteDesignPost',
 'DeleteDesignPre',

 'DeleteStylesheetPost',
 'DeleteStylesheetPre',
 'DeleteTemplatePost',
 'DeleteTemplatePre',
 'DeleteTemplateTypePost',
 'DeleteTemplateTypePre',

 'EditDesignPost',
 'EditDesignPre',

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
] as $name) {
    Events::CreateEvent('Core',$name);
}
