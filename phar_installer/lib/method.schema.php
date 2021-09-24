<?php

//use CMSMS\Lock;
//use CMSMS\StylesheetOperations;
//use CMSMS\TemplateOperations;
use Exception;
use function cms_installer\lang;

$name = $db->database;
if ($name) {
    $name = '`'.$name.'`';
} else {
    throw new Exception('Unable to retrieve databsase name');
}
/*
TODO if no drop/create authority ... see also 'Modify Database' permission
$db->execute('DROP DATABASE IF EXISTS '.$name);
$db->execute('CREATE DATABASE IF NOT EXISTS '.$name); BAD doesn't preserve permissions
*/
// default to UTF content for back-compatibility
$db->execute('ALTER DATABASE '.$name.' DEFAULT CHARACTER SET utf8mb4');
$db->execute('USE DATABASE '.$name);

status_msg(lang('install_createtablesindexes'));

// NOTE site-content-related changes here must be replicated in the data 'skeleton' and DTD in file lib/iosite.functions.php

$dbdict = $db->NewDataDictionary();
$taboptarray = ['mysqli' => 'ENGINE=MyISAM CHARACTER SET ascii'];
//$innotaboptarray = ['mysqli' => 'CHARACTER SET utf8mb4'];
$casedtaboptarray = ['mysqli' => 'ENGINE=MyISAM CHARACTER SET ascii COLLATE ascii_bin'];
//TODO how to also support MariaDB 'Aria' MyISAM-replacement engine if so wanted ?

$good = lang('done');
$bad = lang('failed');

//NOTE: primary keys are blocked (by the Datadictionary) from taking size other than I
//page_id I UNSIGNED, removed 2.99 never used
$flds = '
additional_users_id I UNSIGNED AUTO KEY,
user_id I,
content_id I UNSIGNED
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'additional_users', $flds, $casedtaboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'additional_users', $msg_ret));

//TODO some recorded subject-values might have UTF ?
$flds = '
id I UNSIGNED AUTO KEY,
timestamp I UNSIGNED NOTNULL,
severity I1 UNSIGNED NOTNULL DEFAULT 0,
user_id C(6) DEFAULT "",
username C(80) CHARACTER SET utf8mb4 DEFAULT "",
item_id C(6) DEFAULT "",
subject C(255),
message C(512) CHARACTER SET utf8mb4,
ip_addr C(40)
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'adminlog', $flds, $taboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'adminlog', $msg_ret));

$tbl = CMS_DB_PREFIX.'admin_bookmarks';
//TODO some URL-values might have UTF ?
$flds = '
bookmark_id I UNSIGNED AUTO KEY,
user_id I UNSIGNED XKEY,
title C(40) CHARACTER SET utf8mb4 NOTNULL,
url C(255)
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //admin_bookmarks
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'admin_bookmarks', $msg_ret));

//data field holds a serialized Job-object, size 1000 is probably enough
$flds = '
id I UNSIGNED AUTO KEY,
name C(255) NOTNULL,
module C(50),
created I UNSIGNED NOTNULL,
start I UNSIGNED NOTNULL,
until I UNSIGNED DEFAULT 0,
recurs I2 UNSIGNED,
errors I2 UNSIGNED DEFAULT 0,
data C(2000)
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'asyncjobs', $flds, $casedtaboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'asyncjobs', $msg_ret));

$tbl = CMS_DB_PREFIX.'content';
//parent_id may be < 0 hence signed int
//prop_names X, unused since 2.0, removed 2.99
//default cachable value (1) is contrary to CMSMS pre-2.99
//tpltype_id for themed processing in future, as a non-core prop
//ditto csstype_id
//styles numeric id('s) or design|theme-specific name 2.99+
//titleattribute html descriptor displayed in some contexts
//id_hierarchy is akin to a site-relative URL-path composed of page-id's
// 50 chars =~ 10 levels on a 9999-page site
//hierarchy is akin to a site-relative URL-path composed of display-orders
// with each level 0-padded to 3 places 40 chars = 10 levels, each .NNN
//hierarchy_path is akin to a site-relative URL-path composed
// of page-aliases: 500 chars =~ 10 levels @ 50 each
// NOTE index 'i_contentid_hierarchy' on (content_id,hierarchy) is used in FORCE INDEX queries
$flds = '
content_id I UNSIGNED KEY XKEY,
content_name C(255) CHARACTER SET utf8mb4,
type C(25) NOTNULL,
default_content I1 UNSIGNED DEFAULT 0 INDEX,
show_in_menu I1 UNSIGNED DEFAULT 1,
active I1 UNSIGNED DEFAULT 1,
cachable I1 UNSIGNED DEFAULT 1,
secure I1 UNSIGNED DEFAULT 0,
owner_id I UNSIGNED DEFAULT 1,
parent_id I INDEX,
template_id I UNSIGNED,
item_order I1 UNSIGNED DEFAULT 0,
menu_text C(255) CHARACTER SET utf8mb4,
content_alias C(255),
hierarchy_path C(500),
hierarchy C(40) COLLATE ascii_bin INDEX XKEY,
id_hierarchy C(50) COLLATE ascii_bin,
metadata C(10000),
titleattribute C(255) CHARACTER SET utf8mb4,
page_url C(255),
tabindex I1 UNSIGNED DEFAULT 0,
accesskey C(8),
styles C(50),
last_modified_by I UNSIGNED DEFAULT 0,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP INDEX
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //content
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'content', $msg_ret));
// workaround: a 2nd distinct XKEY index is not supported
$iname = $dbdict->IndexName('content_alias,active');
$sqlarray = $dbdict->CreateIndexSQL($iname, $tbl, 'content_alias,active');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', $iname, $msg_ret));

$tbl = CMS_DB_PREFIX.'content_props';
// param fields are for anything that a page-designer wants, could contain anything.
$flds = '
content_id I UNSIGNED XKEY,
type C(25) NOTNULL,
prop_name C(255) NOTNULL,
param1 C(255) CHARACTER SET utf8mb4,
param2 C(255) CHARACTER SET utf8mb4,
param3 C(255) CHARACTER SET utf8mb4,
content X(65535) CHARACTER SET utf8mb4,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //content_props
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'content_props', $msg_ret));

$tbl = CMS_DB_PREFIX.'content_types';
$flds = '
id I UNSIGNED AUTO KEY,
originator C(50) NOTNULL UKEY,
name C(25) NOTNULL UKEY,
publicname_key C(64),
displayclass C(255) NOTNULL,
editclass C(255)
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //content_types
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'content_types', $msg_ret));

$tbl = CMS_DB_PREFIX.'controlsets';
$flds = '
id I UNSIGNED AUTO KEY,
name C(80) CHARACTER SET utf8mb4 NOTNULL UNIQUE,
reltoppath C(255),
data X(16383) NOTNULL,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //controlsets
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'controlsets', $msg_ret));

$tbl = CMS_DB_PREFIX.'events';
$flds = '
event_id I UNSIGNED AUTO KEY,
originator C(50) NOTNULL XKEY,
event_name C(40) NOTNULL INDEX
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //events
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'events', $msg_ret));

// type = C (callable,default) M (module) P (plugin) or U (UDT)
//ex module_name >> (handler)[namespaced]class, tag_name >> (func)method or plugin/UDT name
//deprecated since 2.99 non AUTO handler_id
$flds = '
handler_id I UNSIGNED AUTO KEY,
event_id I UNSIGNED,
class C(96),
method C(64),
type C(1) NOTNULL DEFAULT "C",
removable I1 UNSIGNED DEFAULT 1,
handler_order I1 UNSIGNED DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'event_handlers', $flds, $casedtaboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'event_handlers', $msg_ret));

$tbl = CMS_DB_PREFIX.'groups';
$flds = '
group_id I UNSIGNED AUTO KEY,
group_name C(50) CHARACTER SET utf8mb4 NOTNULL UKEY,
group_desc C(255) CHARACTER SET utf8mb4,
active I1 UNSIGNED DEFAULT 1,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //groups
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'groups', $msg_ret));

$tbl = CMS_DB_PREFIX.'group_perms';
$flds = '
group_perm_id I UNSIGNED AUTO KEY,
group_id I UNSIGNED XKEY,
permission_id I UNSIGNED XKEY,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //group_perms
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'group_perms', $msg_ret));

$flds = '
token C(16) KEY,
hash C(32) NOTNULL,
create_date DT DEFAULT CURRENT_TIMESTAMP,
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'job_records', $flds, $casedtaboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'job_records', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_css_groups';  // aka StylesheetsGroup::TABLENAME
// name sufficient for module-name + 10
// barf @ name-duplication NOTE field-collation is case-insensitive
$flds = '
id I UNSIGNED AUTO KEY,
name C(60) NOTNULL UKEY,
description C(1500),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, ['mysqli' => 'ENGINE=MyISAM CHARACTER SET utf8mb4']); //layout_css_groups
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_css_groups', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_cssgroup_members'; // aka StylesheetsGroup::MEMBERSTABLE
$flds = '
id I UNSIGNED AUTO KEY,
group_id I UNSIGNED NOTNULL,
css_id I UNSIGNED NOTNULL,
item_order I1 UNSIGNED DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //layout_cssgroup_members
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_cssgroup_members', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_css_types'; // aka StylesheetType::TABLENAME
//originator sufficient for module-name, name ibid  + 10
$flds = '
id I UNSIGNED AUTO KEY,
originator C(50) UKEY,
name C(60) CHARACTER SET utf8mb4 NOTNULL UKEY,
description C(1500) CHARACTER SET utf8mb4,
lang_cb C(255),
dflt_content_cb C(255),
help_content_cb C(255),
owner_id I UNSIGNED DEFAULT 1,
has_dflt I1 UNSIGNED DEFAULT 0,
dflt_content X(65535),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //layout_css_types
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_css_types', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_stylesheets'; // aka StylesheetOperations::TABLENAME
$flds = '
id I UNSIGNED AUTO KEY,
originator C(50) UKEY,
name C(60) CHARACTER SET utf8mb4 NOTNULL UKEY INDEX,
description C(1500) CHARACTER SET utf8mb4,
media_type C(255),
media_query C(255),
owner_id I UNSIGNED DEFAULT 1,
type_id I UNSIGNED XKEY,
type_dflt I1 UNSIGNED DEFAULT 0 XKEY,
listable I1 UNSIGNED DEFAULT 1,
contentfile I1 UNSIGNED DEFAULT 0,
content X(65535),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //layout_stylesheets
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_stylesheets', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_templates'; //aka TemplateOperations::TABLENAME
$flds = '
id I UNSIGNED AUTO KEY,
originator C(50) UKEY,
name C(60) CHARACTER SET utf8mb4 NOTNULL UKEY INDEX,
description C(1500) CHARACTER SET utf8mb4,
hierarchy C(100) COLLATE ascii_bin,
owner_id I UNSIGNED DEFAULT 1,
type_id I UNSIGNED XKEY,
type_dflt I1 UNSIGNED DEFAULT 0 XKEY,
listable I1 UNSIGNED DEFAULT 1,
contentfile I1 UNSIGNED DEFAULT 0,
content X(65535) CHARACTER SET utf8mb4,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //layout_templates
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_templates', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_tpl_addusers'; //aka TemplateOperations::ADDUSERSTABLE
$flds = '
tpl_id I UNSIGNED KEY,
user_id I UNSIGNED KEY
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //layout_tpl_addusers
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_tpl_addusers', $msg_ret));
//CHECKME separate index on layout_tpl_addusers user_id field ?

$tbl = CMS_DB_PREFIX.'layout_tpl_groups'; // aka TemplatesGroup::TABLENAME
// item_order I1 UNSIGNED DEFAULT 0, removed 2.99
// name sufficient for module-name + 10
// barf @f name-duplication NOTE field-collation is case-insensitive
$flds = '
id I UNSIGNED AUTO KEY,
name C(60) NOTNULL UKEY,
description C(1500),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, ['mysqli' => 'ENGINE=MyISAM CHARACTER SET utf8mb4']); //layout_tpl_groups
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_tpl_groups', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_tplgroup_members'; // aka TemplatesGroup::MEMBERSTABLE
$flds = '
id I UNSIGNED AUTO KEY,
group_id I UNSIGNED NOTNULL,
tpl_id I UNSIGNED NOTNULL,
item_order I1 UNSIGNED DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //layout_tplgroup_members
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_tplgroup_members', $msg_ret));

$tbl = CMS_DB_PREFIX.'layout_tpl_types'; // aka TemplateType::TABLENAME
// these are used mainly by DesignManager module (but some other modules too, must be present before modules installation)
//originator sufficient for module-name, name ibid + 10
//owner_id may be 0, for module-templates
$flds = '
id I UNSIGNED AUTO KEY,
originator C(50) UKEY,
name C(60) CHARACTER SET utf8mb4 NOTNULL UKEY,
description C(1500) CHARACTER SET utf8mb4,
lang_cb C(255),
dflt_content_cb C(255),
help_content_cb C(255),
has_dflt I1 UNSIGNED DEFAULT 0,
requires_contentblocks I1 UNSIGNED DEFAULT 0,
one_only I1 UNSIGNED DEFAULT 0,
owner_id I UNSIGNED DEFAULT 1,
dflt_content X(65535) CHARACTER SET utf8mb4,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //layout_tpl_types
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'layout_tpl_types', $msg_ret));

$tbl = CMS_DB_PREFIX.'locks'; // a.k.a. LockOperations::LOCK_TABLE
// lifetime = minutes, expires = timestamp
$flds = '
id I UNSIGNED AUTO KEY,
type C(25) NOTNULL UKEY,
oid I UNSIGNED NOTNULL UKEY,
uid I UNSIGNED NOTNULL XKEY,
lifetime I2 UNSIGNED,
expires I UNSIGNED DEFAULT 0 INDEX,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //locks
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'locks', $msg_ret));

$tbl = CMS_DB_PREFIX.'modules';
$flds = '
module_name C(50) NOTNULL KEY,
version C(16),
admin_only I1 UNSIGNED DEFAULT 0,
active I1 UNSIGNED DEFAULT 1
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); // modules
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'modules', $msg_ret));

$flds = '
parent_module C(50),
child_module C(50),
minimum_version C(16),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'module_deps', $flds, $casedtaboptarray);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'module_deps', $msg_ret));

$tbl = CMS_DB_PREFIX.'module_smarty_plugins';
// name field is case-sensitive, to support liberal indexing, tho' we select-for-use caselessly
// default cachable value (1) is contrary to CMSMS pre-2.99
$flds = '
id I UNSIGNED AUTO KEY,
name C(50) NOTNULL UKEY,
module C(50) NOTNULL UKEY,
type C(25) NOTNULL DEFAULT "function",
callable C(255) NOTNULL,
available I1 UNSIGNED DEFAULT 1,
cachable I1 UNSIGNED DEFAULT 1
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); //module_smarty_plugins
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'module_smarty_plugins', $msg_ret));

$tbl = CMS_DB_PREFIX.'permissions';
$flds = '
id I UNSIGNED AUTO KEY,
name C(50) NOTNULL UKEY,
description C(255) CHARACTER SET utf8mb4,
originator C(50) NOTNULL UKEY,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); // permissions
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'permissions', $msg_ret));

$tbl = CMS_DB_PREFIX.'routes';
$flds = '
id I UNSIGNED AUTO KEY,
term C(300) NOTNULL,
dest1 C(50) COLLATE ascii_general_ci NOTNULL,
page C(10),
delmatch C(100) COLLATE ascii_general_ci,
data C(600) NOTNULL,
create_date DT DEFAULT CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); // routes
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'routes', $msg_ret));
// UKEY workaround: index-fields order != their table-order
$iname = $dbdict->IndexName('dest1,page,term');
// NOTE max 1000-byte length for index-key
$sqlarray = $dbdict->CreateIndexSQL($iname, $tbl, 'dest1,page,term', ['UNIQUE']);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_index', $iname, $msg_ret));

$tbl = CMS_DB_PREFIX.'siteprefs';
$flds = '
sitepref_name C(255) NOTNULL UKEY,
sitepref_value X(65535) CHARACTER SET utf8mb4,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //siteprefs
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'siteprefs', $msg_ret));

$tbl = CMS_DB_PREFIX.'users';
$flds = '
user_id I UNSIGNED KEY,
username C(80) CHARACTER SET utf8mb4 NOTNULL UKEY,
password C(128),
first_name C(64) CHARACTER SET utf8mb4,
last_name C(64) CHARACTER SET utf8mb4,
email C(255) CHARACTER SET utf8mb4,
active I1 UNSIGNED DEFAULT 1,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
tailor B(16384)
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); // users
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'users', $msg_ret));

$tbl = CMS_DB_PREFIX.'userplugins';
// name must support case-insenstive matching, TODO might include UTF8 ?
// code field mostly/entirely ASCII TODO but might include UTF8 text for UI ?
$flds = '
id I UNSIGNED AUTO KEY,
name C(50) NOTNULL UKEY,
description C(1500) CHARACTER SET utf8mb4,
parameters C(1000) CHARACTER SET utf8mb4,
contentfile I1 UNSIGNED DEFAULT 0,
code C(15000),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //userplugins
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'userplugins', $msg_ret));

$tbl = CMS_DB_PREFIX.'userprefs';
$flds = '
user_id I UNSIGNED NOTNULL XKEY UKEY,
preference C(255) NOTNULL UKEY,
value X(65535) CHARACTER SET utf8mb4
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $taboptarray); //userprefs
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'userprefs', $msg_ret));

$tbl = CMS_DB_PREFIX.'user_groups';
$flds = '
group_id I UNSIGNED NOTNULL UKEY,
user_id I UNSIGNED NOTNULL UKEY,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP
';
$sqlarray = $dbdict->CreateTableSQL($tbl, $flds, $casedtaboptarray); // user_groups
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? $good : $bad;
verbose_msg(lang('install_created_table', 'user_groups', $msg_ret));
