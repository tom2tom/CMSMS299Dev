<?php

use CMSMS\Lock;
use CMSMS\StylesheetOperations;
use CMSMS\TemplateOperations;
use function cms_installer\lang;

//force-drop tables which will be created (just in case, should do nothing during install)
//status_msg(lang('install_dropping_tables'));
$db->DropSequence(CMS_DB_PREFIX.'additional_users_seq'); //deprecated since 2.3
$db->DropSequence(CMS_DB_PREFIX.'admin_bookmarks_seq');
$db->DropSequence(CMS_DB_PREFIX.'content_props_seq');
$db->DropSequence(CMS_DB_PREFIX.'content_seq');
$db->DropSequence(CMS_DB_PREFIX.'event_handler_seq'); //deprecated since 2.3
$db->DropSequence(CMS_DB_PREFIX.'events_seq'); //deprecated since 2.3
$db->DropSequence(CMS_DB_PREFIX.'group_perms_seq'); //deprecated since 2.3
$db->DropSequence(CMS_DB_PREFIX.'groups_seq');
$db->DropSequence(CMS_DB_PREFIX.'permissions_seq');
$db->DropSequence(CMS_DB_PREFIX.'users_seq');

$dbdict = GetDataDictionary($db);

$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'additional_users');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'admin_bookmarks');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'content');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'content_props');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'events');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'event_handlers');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'group_perms');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'groups');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'modules');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'module_deps');
$dbdict->ExecuteSQLArray($sqlarray);
//$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'module_templates'); now in layout_templates
//$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'permissions');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'siteprefs');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'user_groups');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'userprefs');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'users');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'version');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'module_smarty_plugins');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'routes');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.Lock::LOCK_TABLE);
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.TemplateOperations::TABLENAME);
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.TemplateOperations::ADDUSERSTABLE);
$dbdict->ExecuteSQLArray($sqlarray);
// these are used mainly by DesignManager module (but some other modules too)
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME);
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME);
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE);
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.StylesheetOperations::TABLENAME);
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME);
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE);
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE);
$dbdict->ExecuteSQLArray($sqlarray);

status_msg(lang('install_createtablesindexes'));
if ($db->dbtype == 'mysqli') {
    @$db->Execute('ALTER DATABASE `' . $db->database . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
}

// NOTE site-content-related changes here must be replicated in the data 'skeleton' and DTD in file lib/iosite.functions.php

$dbdict = GetDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];
//$innotaboptarray = ['mysqli' => 'CHARACTER SET utf8 COLLATE utf8_general_ci'];

$good = lang('done');
$bad = lang('failed');

//non AUTO additional_users_id deprecated since 2.3
//page_id I, removed 2.3 never used
$flds = '
additional_users_id I KEY NOT NULL,
user_id I,
content_id I
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'additional_users', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'additional_users', $msg_ret));

$flds = '
bookmark_id I KEY,
user_id I,
title C(255),
url C(255)
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'admin_bookmarks', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'admin_bookmarks', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_admin_bookmarks_by_user_id',
CMS_DB_PREFIX.'admin_bookmarks', 'user_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'admin_bookmarks', $msg_ret));

$flds = '
content_id I KEY,
content_name C(255),
type C(25),
default_content I(1) DEFAULT 0,
show_in_menu I(1) DEFAULT 1,
active I(1) DEFAULT 1,
cachable I(1) DEFAULT 1,
secure I(1) DEFAULT 0,
owner_id I,
parent_id I,
template_id I,
item_order I(4) DEFAULT 0,
hierarchy C(255),
menu_text C(255),
content_alias C(255),
id_hierarchy C(255),
hierarchy_path X(2048),
prop_names X(16384),
metadata X(16384),
titleattribute C(255),
page_url C(255),
tabindex C(10),
accesskey C(5),
last_modified_by I,
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'content', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'content', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_alias_active',
CMS_DB_PREFIX.'content', 'content_alias, active');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_alias_active', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_default_content',
CMS_DB_PREFIX.'content', 'default_content');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_default_content', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_parent_id',
CMS_DB_PREFIX.'content', 'parent_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_parent_id', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_hier',
CMS_DB_PREFIX.'content', 'hierarchy');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_hier', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_idhier',
CMS_DB_PREFIX.'content', 'content_id, hierarchy');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_idhier', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_modified',
CMS_DB_PREFIX.'content', 'modified_date');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_modified', $msg_ret));

$flds = '
content_id I,
type C(25),
prop_name C(255),
param1 C(255),
param2 C(255),
param3 C(255),
content X2,
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'content_props', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'content_props', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_props_by_content',
CMS_DB_PREFIX.'content_props', 'content_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_props_by_content', $msg_ret));

// type = C (callable,default) M (module) P (plugin) or U (UDT)
//ex module_name >> (handler)[namespaced]class, tag_name >> (func)method or plugin/UDT name
//deprecated since 2.3 non AUTO handler_id
$flds = '
handler_id I(4) KEY,
event_id I(4),
class C(96),
func C(64),
type C(1) NOT NULL DEFAULT "C",
removable I(1) DEFAULT 1,
handler_order I(4) DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'event_handlers', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'event_handlers', $msg_ret));

//deprecated since 2.3 non AUTO event_id
$flds = '
event_id I(4) KEY,
originator C(48) NOT NULL,
event_name C(48) NOT NULL
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'events', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'events', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_originator',
CMS_DB_PREFIX.'events', 'originator');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'originator', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_event_name',
CMS_DB_PREFIX.'events', 'event_name');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'event_name', $msg_ret));

//deprecated since 2.3 non AUTO group_perm_id
$flds = '
group_perm_id I KEY,
group_id I,
permission_id I,
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'group_perms', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'group_perms', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_grp_perms_by_grp_id_perm_id',
    CMS_DB_PREFIX.'group_perms', 'group_id, permission_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_grp_perms_by_grp_id_perm_id', $msg_ret));

$flds = '
group_id I KEY,
group_name C(25),
group_desc C(255),
active I(1) DEFAULT 1,
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'groups', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'groups', $msg_ret));

$flds = '
module_name C(32) KEY,
status C(255),
version C(255),
admin_only I(1) DEFAULT 0,
active I(1) DEFAULT 1,
allow_fe_lazyload I(1) DEFAULT 1,
allow_admin_lazyload I(1) DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'modules', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'modules', $msg_ret));
//deleted here: a duplicate index on the module_name field

$flds = '
parent_module C(25),
child_module C(25),
minimum_version C(25),
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'module_deps', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'module_deps', $msg_ret));

/* merged with layout_templates
$flds = '
module_name C(160),
template_name C(160),
content X(TODO),
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.'module_templates',
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'module_templates', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_module_templates_1',
    CMS_DB_PREFIX.'module_templates', 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_module_templates_1', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_module_templates_2',
    CMS_DB_PREFIX.'module_templates', 'type_id,type_dflt');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_module_templates_2', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_module_templates_3',
    CMS_DB_PREFIX.'module_templates', 'module_name,template_name');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_module_templates_by_module_and_tpl_name', $msg_ret));
*/
$flds = '
permission_id I KEY,
permission_name C(255),
permission_text C(255),
permission_source C(255),
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'permissions', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'permissions', $msg_ret));

$flds = '
sitepref_name C(255) KEY,
sitepref_value text,
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'siteprefs', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'siteprefs', $msg_ret));

$flds = '
group_id I KEY,
user_id I KEY,
create_date DT,
modified_date DT
';
//CHECKME separate index on user_id field ?
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'user_groups', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'user_groups', $msg_ret));

$flds = '
user_id I KEY,
preference C(50) KEY,
value X(16384),
type C(25)
';
//CHECKME separate index on preference field ?
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'userprefs', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'userprefs', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_userprefs_by_user_id',
    CMS_DB_PREFIX.'userprefs', 'user_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_userprefs_by_user_id', $msg_ret));

$flds = '
user_id I KEY,
username C(80),
password C(128),
first_name C(50),
last_name C(50),
email C(255),
admin_access I(1) DEFAULT 1,
active I(1) DEFAULT 1,
create_date DT,
modified_date DT
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'users', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'users', $msg_ret));

$flds = '
version I
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.'version',
    $flds,
    ['mysqli' => 'ENGINE=MYISAM COLLATE ascii_general_ci']
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'version', $msg_ret));

$flds = '
sig C(80) KEY NOT NULL,
name C(48) NOT NULL,
module C(32) NOT NULL,
type C(32) NOT NULL,
callback C(255) NOT NULL,
available I(1) DEFAULT 1
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'module_smarty_plugins', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'module_smarty_plugins', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_smp_module',
    CMS_DB_PREFIX.'module_smarty_plugins', 'module');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_smp_module', $msg_ret));

$flds = '
term C(255) KEY NOT NULL,
key1 C(48) KEY NOT NULL,
key2 C(48),
key3 C(48),
data X(16384),
created DT
';
//CHECKME separate index on key1 field ?
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'routes', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'routes', $msg_ret));

$flds = '
id I KEY AUTO,
originator C(32),
name C(96) NOT NULL,
content X2,
description X(1024),
type_id I NOT NULL,
owner_id I NOT NULL DEFAULT 1,
type_dflt I(1) DEFAULT 0,
listable I(1) DEFAULT 1,
contentfile I(1) DEFAULT 0,
created I,
modified I
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.TemplateOperations::TABLENAME,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', TemplateOperations::TABLENAME, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_1',
    CMS_DB_PREFIX.TemplateOperations::TABLENAME, 'name');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_1', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_2',
    CMS_DB_PREFIX.TemplateOperations::TABLENAME, 'type_id,type_dflt');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_2', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_3',
    CMS_DB_PREFIX.TemplateOperations::TABLENAME, 'originator,name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_3', $msg_ret));

$flds = '
tpl_id I KEY,
user_id I KEY
';
//CHECKME separate index on user_id field ?
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.TemplateOperations::ADDUSERSTABLE,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', TemplateOperations::ADDUSERSTABLE, $msg_ret));

// these are used mainly by DesignManager module (but some other modules too, must be present before modules installation)
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
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', CmsLayoutTemplateType::TABLENAME, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_type_1',
    CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME, 'originator,name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_type_1', $msg_ret));

$flds = '
id I KEY AUTO,
name C(96) NOT NULL,
description X(1024),
item_order I(4) DEFAULT 0,
modified I
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', CmsLayoutTemplateCategory::TABLENAME, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_cat_1',
    CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME, 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_type_1', $msg_ret));

$flds = '
category_id I NOT NULL,
tpl_id I NOT NULL,
tpl_order I(4) DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', CmsLayoutTemplateCategory::TPLTABLE, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_cat_tplasoc_1',
    CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE, 'tpl_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_cat_tplasoc_1', $msg_ret));

$flds = '
id I KEY AUTO,
name C(96) NOT NULL,
content X2,
description X(1024),
media_type C(255),
media_query X(16384),
contentfile I(1) DEFAULT 0,
created I,
modified I
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.StylesheetOperations::TABLENAME,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', StylesheetOperations::TABLENAME, $msg_ret));
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_css_1',
    CMS_DB_PREFIX.StylesheetOperations::TABLENAME, 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'idx_layout_css_1', $msg_ret));

$flds = '
id I KEY AUTO,
name C(96) NOT NULL,
description X(1024),
dflt I(1) DEFAULT 0,
created I,
modified I
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', CmsLayoutCollection::TABLENAME, $msg_ret));
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_dsn_1',
    CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME, 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'idx_layout_dsn_1', $msg_ret));

$flds = '
design_id I KEY NOT NULL,
tpl_id I KEY NOT NULL,
tpl_order I(4) DEFAULT 0
';
//CHECKME separate index on tpl_id field ?
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', CmsLayoutCollection::TPLTABLE, $msg_ret));
$sqlarray = $dbdict->CreateIndexSQL('idx_dsnassoc1',
    CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE, 'tpl_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'index_dsnassoc1', $msg_ret));

$flds = '
design_id I KEY NOT NULL,
css_id I KEY NOT NULL,
item_order I(4) DEFAULT 0
';
//CHECKME separate index on css_id field ?
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', CmsLayoutCollection::CSSTABLE, $msg_ret));

$flds = '
id I AUTO KEY NOT NULL,
type C(24) NOT NULL,
oid I NOT NULL,
uid I NOT NULL,
created I NOT NULL,
modified I NOT NULL,
lifetime I NOT NULL,
expires I NOT NULL
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.Lock::LOCK_TABLE,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', Lock::LOCK_TABLE, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_locks1',
    CMS_DB_PREFIX.'locks', 'type,oid', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'index_locks1', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_locks2',
    CMS_DB_PREFIX.'locks', 'expires');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'index_locks2', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_locks3',
    CMS_DB_PREFIX.'locks', 'uid');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'index_locks3', $msg_ret));
