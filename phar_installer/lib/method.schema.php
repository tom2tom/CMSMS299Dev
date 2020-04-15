<?php

//use CMSMS\Lock;
//use CMSMS\StylesheetOperations;
//use CMSMS\TemplateOperations;
use function cms_installer\GetDataDictionary;
use function cms_installer\lang;

$name = $db->database;
if ($name) {
    $name = '`'.$name.'`';
} else {
    throw new Exception('Unable to retrieve databsase name');
}
/*
TODO if no drop/create authority ...
$db->Execute('DROP DATABASE IF EXISTS '.$name);
$db->Execute('CREATE DATABASE IF NOT EXISTS '.$name); BAD doesn't preserve permissions
*/
$db->Execute('ALTER DATABASE '.$name.' DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
$db->Execute('USE DATABASE '.$name);

$dbdict = GetDataDictionary($db);

status_msg(lang('install_createtablesindexes'));

// NOTE site-content-related changes here must be replicated in the data 'skeleton' and DTD in file lib/iosite.functions.php

$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];
//$innotaboptarray = ['mysqli' => 'CHARACTER SET utf8 COLLATE utf8_general_ci'];

$good = lang('done');
$bad = lang('failed');

//page_id I, removed 2.3 never used
$flds = '
additional_users_id I(2) UNSIGNED AUTO KEY,
user_id I(2) UNSIGNED,
content_id I(2) UNSIGNED
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'additional_users', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'additional_users', $msg_ret));

$flds = '
bookmark_id I(2) UNSIGNED AUTO KEY,
user_id I(2) UNSIGNED,
title C(64),
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

$tbl = CMS_DB_PREFIX.'content';
//parent_id may be < 0 hence signed int
//prop_names X, unused since 2.0, removed 2.3
$flds = '
content_id I(2) UNSIGNED KEY,
content_name C(255),
type C(24) COLLATE "ascii_general_ci" NOT NULL,
default_content I(1) DEFAULT 0,
show_in_menu I(1) DEFAULT 1,
active I(1) DEFAULT 1,
cachable I(1) DEFAULT 1,
secure I(1) DEFAULT 0,
owner_id I(2) UNSIGNED,
parent_id I(4),
template_id I(2) UNSIGNED,
item_order I(1) UNSIGNED DEFAULT 0,
hierarchy C(255),
menu_text C(255),
content_alias C(255),
id_hierarchy C(255),
hierarchy_path X(1024),
metadata X(8192),
titleattribute C(255),
page_url C(255),
tabindex I(1) UNSIGNED DEFAULT 0,
accesskey C(8),
styles C(48),
last_modified_by I(2) UNSIGNED,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'content', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_alias_active', $tbl, 'content_alias, active');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_alias_active', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_default_content', $tbl, 'default_content');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_default_content', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_parent_id', $tbl, 'parent_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_parent_id', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_hier', $tbl, 'hierarchy');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_hier', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_idhier',$tbl, 'content_id, hierarchy');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_idhier', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_by_modified', $tbl, 'modified_date');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_by_modified', $msg_ret));

$tbl = CMS_DB_PREFIX.'content_props';
$flds = '
content_id I(2) UNSIGNED,
type C(24),
prop_name C(255),
param1 C(255),
param2 C(255),
param3 C(255),
content X,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'content_props', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_content_props_by_content', $tbl, 'content_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_content_props_by_content', $msg_ret));

$tbl = CMS_DB_PREFIX.'content_types';
$flds = '
id I(2) UNSIGNED AUTO KEY,
originator C(32) NOTNULL,
name C(24) NOTNULL,
publicname_key C(64),
displayclass C(255) NOTNULL COLLATE ascii_bin,
editclass C(255) COLLATE ascii_bin
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$dbdict->ExecuteSQLArray($sqlarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'content_types', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_typename',$tbl,'name',['UNIQUE']);
$dbdict->ExecuteSQLArray($sqlarray);

// type = C (callable,default) M (module) P (plugin) or U (UDT)
//ex module_name >> (handler)[namespaced]class, tag_name >> (func)method or plugin/UDT name
//deprecated since 2.3 non AUTO handler_id
$flds = '
handler_id I(2) UNSIGNED AUTO KEY,
event_id I(2) UNSIGNED,
class C(96),
func C(64),
type C(1) NOT NULL DEFAULT "C",
removable I(1) DEFAULT 1,
handler_order I(1) UNSIGNED DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'event_handlers', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'event_handlers', $msg_ret));

$tbl = CMS_DB_PREFIX.'events';
//deprecated since 2.3 non AUTO event_id
$flds = '
event_id I(2) UNSIGNED AUTO KEY,
originator C(32) NOT NULL,
event_name C(64) NOT NULL
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'events', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_originator', $tbl, 'originator');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'originator', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_event_name', $tbl, 'event_name');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'event_name', $msg_ret));

$tbl = CMS_DB_PREFIX.'group_perms';
$flds = '
group_perm_id I(2) UNSIGNED AUTO KEY,
group_id I(2) UNSIGNED,
permission_id I(2) UNSIGNED,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'group_perms', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_grp_perms_by_grp_id_perm_id', $tbl, 'group_id, permission_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_grp_perms_by_grp_id_perm_id', $msg_ret));

$flds = '
group_id I(2) UNSIGNED AUTO KEY,
group_name C(32),
group_desc C(255),
active I(1) DEFAULT 1,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'groups', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'groups', $msg_ret));

//allow_fe_lazyload I(1) DEFAULT 1,
//allow_admin_lazyload I(1) DEFAULT 0
$flds = '
module_name C(32) KEY,
status C(255),
version C(255),
admin_only I(1) DEFAULT 0,
active I(1) DEFAULT 1
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'modules', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'modules', $msg_ret));
//deleted here: a duplicate index on the module_name field

$flds = '
parent_module C(32),
child_module C(32),
minimum_version C(24),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'module_deps', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'module_deps', $msg_ret));

$tbl = CMS_DB_PREFIX.'module_smarty_plugins';
// name field is case-sensitive, to support liberal indexing
$flds = '
id I(2) UNSIGNED AUTO KEY,
name C(48) COLLATE "utf8_bin" NOT NULL,
module C(32) NOT NULL,
type C(32) DEFAULT "function" COLLATE "ascii_general_ci" NOT NULL,
callback C(255) COLLATE "ascii_bin" NOT NULL,
available I(1) DEFAULT 1,
cachable I(1) DEFAULT 1
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'module_smarty_plugins', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_tagname', $tbl, 'name,module', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_tagname', $msg_ret));

// UDT's continue to be dB-storable, as well as file-storable UDTfiles
// code field mostly/entirely ASCII, but might include UTF8 text for UI
$flds = '
id I(2) UNSIGNED AUTO KEY,
name C(48),
code X(16383),
description X(1023),
parameters X(1023),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'simpleplugins', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'simpleplugins', $msg_ret));

/* merged with layout_templates
$flds = '
module_name C(32),
template_name C(160),
content X(TODO),
create_date DT,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
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
permission_id I(2) UNSIGNED AUTO KEY,
permission_name C(48),
permission_text C(255),
permission_source C(64),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'permissions', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'permissions', $msg_ret));

// name-field sized to support max 32-char space-name (c.f. module) + 2-char \\ separator + 62 char varname
$flds = '
sitepref_name C(48) KEY,
sitepref_value X(2048),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'siteprefs', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'siteprefs', $msg_ret));

$flds = '
group_id I(2) UNSIGNED KEY,
user_id I(2) UNSIGNED KEY,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
//CHECKME separate index on user_id field ?
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'user_groups', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'user_groups', $msg_ret));

$tbl = CMS_DB_PREFIX.'userprefs';
$flds = '
user_id I(2) UNSIGNED KEY,
preference C(48) KEY,
value X(2048),
type C(24)
';
//CHECKME separate index on preference field ?
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'userprefs', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_userprefs_by_user_id', $tbl, 'user_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_userprefs_by_user_id', $msg_ret));

$flds = '
user_id I(2) UNSIGNED AUTO KEY,
username C(80),
password C(128),
first_name C(64),
last_name C(64),
email C(255),
admin_access I(1) DEFAULT 1,
active I(1) DEFAULT 1,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'users', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'users', $msg_ret));

/* schema-version cache replaced by siteprefs table entry
$flds = '
version I(4) UNSIGNED
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'version', $flds,
    ['mysqli' => 'ENGINE=MYISAM COLLATE ascii_general_ci']
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'version', $msg_ret));
*/

//CHECKME combined index on term,key1 fields ?
//created DT renamed 2.3
$flds = '
id I(2) UNSIGNED AUTO KEY,
term C(255) NOT NULL,
key1 C(48) NOT NULL,
key2 C(48),
key3 C(48),
data X(512),
create_date DT DEFAULT CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'routes', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'routes', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_templates'; //aka TemplateOperations::TABLENAME
$flds = '
id I AUTO KEY,
originator C(32),
name C(64) NOT NULL,
content X,
description X(1024),
type_id I(2) UNSIGNED NOT NULL,
owner_id I(2) UNSIGNED NOT NULL DEFAULT 1,
type_dflt I(1) DEFAULT 0,
listable I(1) DEFAULT 1,
contentfile I(1) DEFAULT 0,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_templates', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_1', $tbl, 'name');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_1', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_2', $tbl, 'type_id,type_dflt');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_2', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_3', $tbl, 'originator,name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_3', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_tpl_addusers'; //aka TemplateOperations::ADDUSERSTABLE
$flds = '
tpl_id I(2) UNSIGNED KEY,
user_id I(2) UNSIGNED KEY
';
//CHECKME separate index on user_id field ?
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_tpl_addusers', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_tpl_type'; // aka CmsLayoutTemplateType::TABLENAME
// these are used mainly by DesignManager module (but some other modules too, must be present before modules installation)
//created I, <<< DT replaced 2.3
//modified I <<< DT ditto
$flds = '
id I(2) UNSIGNED AUTO KEY,
originator C(32) NOT NULL,
name C(48) NOT NULL,
dflt_contents X,
description X(1024),
lang_cb C(255),
dflt_content_cb C(255),
help_content_cb C(255),
has_dflt I(1) DEFAULT 0,
requires_contentblocks I(1) DEFAULT 0,
one_only I(1) DEFAULT 0,
owner I,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_tpl_type', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_type_1', $tbl, 'originator,name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_type_1', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_tpl_groups'; // aka CmsLayoutTemplateCategory::TABLENAME
// item_order I(1) DEFAULT 0, removed 2.3
$flds = '
id I(2) UNSIGNED AUTO KEY,
name C(48) NOT NULL,
description X(1024),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_tpl_groups', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_grp_1', $tbl, 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tpl_type_1', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_tplgroup_members'; // aka CmsLayoutTemplateCategory::MEMBERSTABLE
$flds = '
id I(2) UNSIGNED AUTO KEY,
group_id I(2) UNSIGNED NOT NULL,
tpl_id I(2) UNSIGNED NOT NULL,
item_order I(2) UNSIGNED DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_tplgroup_members', $msg_ret));
/*
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tplgrp_1', $tbl, 'tpl_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_creating_index', 'idx_layout_tplgrp_1', $msg_ret));
*/

$tbl = CMS_DB_PREFIX.'layout_stylesheets'; // aka StylesheetOperations::TABLENAME
$flds = '
id I(2) UNSIGNED AUTO KEY,
name C(48) NOT NULL,
content X,
description X(1024),
media_type C(255),
media_query X,
contentfile I(1) DEFAULT 0,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_stylesheets', $msg_ret));
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_css_1', $tbl, 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'idx_layout_css_1', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_css_groups';  // aka StylesheetsGroup::TABLENAME
$flds = '
id I(2) UNSIGNED AUTO KEY,
name C(48),
description X(1024),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_css_groups', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_cssgroup_members'; // aka StylesheetsGroup::MEMBERSTABLE
$flds = '
id I(2) UNSIGNED AUTO KEY,
group_id I(2) UNSIGNED NOT NULL,
css_id I(2) UNSIGNED NOT NULL,
item_order I(2) UNSIGNED DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_cssgroup_members', $msg_ret));

/*
//TODO consider migrating design-related tables to DesignManager module namespace
$tbl = CMS_DB_PREFIX.'layout_designs'; // aka DesignManager\Design::TABLENAME
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
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_designs', $msg_ret));
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_dsn_1', $tbl, 'name', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'idx_layout_dsn_1', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_design_tplassoc'; // aka DesignManager\Design::TPLTABLE
$flds = '
id I(2) UNSIGNED AUTO KEY,
design_id I(2) UNSIGNED NOT NULL KEY,
tpl_id I(2) UNSIGNED NOT NULL KEY,
tpl_order I(1) UNSIGNED DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_design_tplassoc', $msg_ret));
$sqlarray = $dbdict->CreateIndexSQL('idx_dsnassoc1', $tbl, 'tpl_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'index_dsnassoc1', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_design_cssassoc'; // aka DesignManager\Design::CSSTABLE
//CHECKME separate index on css_id field ?
$flds = '
id I(2) UNSIGNED AUTO KEY,
design_id I(2) UNSIGNED NOT NULL KEY,
css_id I(2) UNSIGNED NOT NULL KEY,
css_order I(1) UNSIGNED DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_design_cssassoc', $msg_ret));
*/

$tbl = CMS_DB_PREFIX.'locks'; // aka Lock::LOCK_TABLE
$flds = '
id I(4) UNSIGNED AUTO KEY,
type C(24) NOT NULL,
oid I NOT NULL,
uid I NOT NULL,
lifetime I(2) UNSIGNED NOT NULL,
expires I,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'locks', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_locks1', $tbl, 'type,oid', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'index_locks1', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_locks2', $tbl, 'expires');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'index_locks2', $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_locks3', $tbl, 'uid');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', 'index_locks3', $msg_ret));
