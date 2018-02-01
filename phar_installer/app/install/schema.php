<?php

if (isset($CMS_INSTALL_DROP_TABLES)) {
    status_msg(ilang('install_dropping_tables'));
    $db->DropSequence(CMS_DB_PREFIX.'additional_users_seq');
    $db->DropSequence(CMS_DB_PREFIX.'admin_bookmarks_seq');
    $db->DropSequence(CMS_DB_PREFIX.'additional_users_seq');
    $db->DropSequence(CMS_DB_PREFIX.'content_seq');
    $db->DropSequence(CMS_DB_PREFIX.'content_props_seq');
    $db->DropSequence(CMS_DB_PREFIX.'events_seq');
    $db->DropSequence(CMS_DB_PREFIX.'event_handler_seq');
    $db->DropSequence(CMS_DB_PREFIX.'group_perms_seq');
    $db->DropSequence(CMS_DB_PREFIX.'groups_seq');
    $db->DropSequence(CMS_DB_PREFIX.'module_deps_seq');
    $db->DropSequence(CMS_DB_PREFIX.'module_templates_seq');
    $db->DropSequence(CMS_DB_PREFIX.'permissions_seq');
    $db->DropSequence(CMS_DB_PREFIX.'users_seq');

    $dbdict = NewDataDictionary($db);

    $sqlarray = $dbdict->DropIndexSQL('idx_template_id_modified_date');
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropIndexSQL(CMS_DB_PREFIX.'idx_template_id_modified_date');
    $dbdict->ExecuteSQLArray($sqlarray);

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
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'module_templates');
    $dbdict->ExecuteSQLArray($sqlarray);
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
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME);
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME);
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME);
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutTemplate::ADDUSERSTABLE);
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutStylesheet::TABLENAME);
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME);
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE);
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE);
    $dbdict->ExecuteSQLArray($sqlarray);
    $sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.CmsLock::LOCK_TABLE);
    $dbdict->ExecuteSQLArray($sqlarray);
}

if (isset($CMS_INSTALL_CREATE_TABLES)) {
    status_msg(ilang('install_createtablesindexes'));
    if ($db->dbtype == 'mysqli') {
        @$db->Execute('ALTER DATABASE `' . $db->database . '` DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci');
    }

    $dbdict = NewDataDictionary($db);
    $taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];
    //  $innotaboptarray = array('mysqli' => 'CHARACTER SET utf8 COLLATE utf8_general_ci');

    $flds = '
additional_users_id I KEY,
user_id I,
page_id I,
content_id I
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'additional_users', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'additional_users', $msg_ret));

    $flds = '
bookmark_id I KEY,
user_id I,
title C(255),
url C(255)
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'admin_bookmarks', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'admin_bookmarks', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'index_admin_bookmarks_by_user_id',
        CMS_DB_PREFIX.'admin_bookmarks',
        'user_id'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'admin_bookmarks', $msg_ret));

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
item_order I,
hierarchy C(255),
menu_text C(255),
content_alias C(255),
id_hierarchy C(255),
hierarchy_path X,
prop_names X,
metadata X,
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
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'content', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_content_by_alias_active',
        CMS_DB_PREFIX.'content',
        'content_alias, active'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_content_by_alias_active', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_content_default_content',
        CMS_DB_PREFIX.'content',
        'default_content'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_content_default_content', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_content_by_parent_id',
        CMS_DB_PREFIX.'content',
        'parent_id'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_content_by_parent_id', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_content_by_hier',
        CMS_DB_PREFIX.'content',
        'hierarchy'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_content_by_hier', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'index_content_by_idhier',
        CMS_DB_PREFIX.'content',
        'content_id, hierarchy'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_content_by_idhier', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_content_by_modified',
        CMS_DB_PREFIX.'content',
        'modified_date'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_content_by_modified', $msg_ret));

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
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'content_props', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_content_props_by_content',
        CMS_DB_PREFIX.'content_props',
        'content_id'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_content_props_by_content', $msg_ret));

    $flds = '
handler_id I KEY,
event_id I,
tag_name C(255),
module_name C(160),
removable I,
handler_order I
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'event_handlers', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'event_handlers', $msg_ret));

    $flds = '
event_id I KEY,
originator C(200) NOTNULL,
event_name C(200) NOTNULL
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'events', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'events', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'originator',
        CMS_DB_PREFIX.'events',
        'originator'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'originator', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'event_name',
        CMS_DB_PREFIX.'events',
        'event_name'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'event_name', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'event_id',
        CMS_DB_PREFIX.'events',
        'event_id'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'event_id', $msg_ret));

    $flds = '
group_perm_id I KEY,
group_id I,
permission_id I,
create_date DT,
modified_date DT
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'group_perms', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'group_perms', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_grp_perms_by_grp_id_perm_id',
        CMS_DB_PREFIX.'group_perms',
        'group_id, permission_id'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_grp_perms_by_grp_id_perm_id', $msg_ret));

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
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'groups', $msg_ret));

    $flds = '
module_name C(160) KEY,
status C(255),
version C(255),
admin_only I(1) DEFAULT 0,
active I(1) DEFAULT 1,
allow_fe_lazyload I(1) DEFAULT 1,
allow_admin_lazyload I(1) DEFAULT 0
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'modules', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'modules', $msg_ret));
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
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'module_deps', $msg_ret));

    // deprecated
    $flds = '
module_name C(160),
template_name C(160),
content X,
create_date DT,
modified_date DT
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'module_templates', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'module_templates', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_module_templates_by_module_and_tpl_name',
        CMS_DB_PREFIX.'module_templates',
        'module_name, template_name'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_module_templates_by_module_and_tpl_name', $msg_ret));

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
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'permissions', $msg_ret));

    $flds = '
sitepref_name C(255) KEY,
sitepref_value text,
create_date DT,
modified_date DT
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'siteprefs', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'siteprefs', $msg_ret));

    $flds = '
group_id I KEY,
user_id I KEY,
create_date DT,
modified_date DT
';
    //CHECKME separate index on user_id field ?
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'user_groups', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'user_groups', $msg_ret));

    $flds = '
user_id I KEY,
preference C(50) KEY,
value X,
type C(25)
';
    //CHECKME separate index on preference field ?
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'userprefs', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'userprefs', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_userprefs_by_user_id',
        CMS_DB_PREFIX.'userprefs',
        'user_id'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_userprefs_by_user_id', $msg_ret));

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
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'users', $msg_ret));

    $flds = '
version I
';
    $sqlarray = $dbdict->CreateTableSQL(
        CMS_DB_PREFIX.'version',
        $flds,
        ['mysqli' => 'ENGINE=MYISAM COLLATE ascii_general_ci']
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'version', $msg_ret));

    $flds = '
sig C(80) KEY NOTNULL,
name C(80) NOTNULL,
module C(160) NOTNULL,
type C(40) NOTNULL,
callback C(255) NOTNULL,
available I,
cachable I(1) DEFAULT 0
';
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'module_smarty_plugins', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'module_smarty_plugins', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_smp_module',
        CMS_DB_PREFIX.'module_smarty_plugins',
        'module'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_smp_module', $msg_ret));

    $flds = '
term C(255) KEY NOTNULL,
key1 C(50) KEY NOTNULL,
key2 C(50),
key3 C(50),
data X,
created DT
';
    //CHECKME separate index on key1 field ?
    $sqlarray = $dbdict->CreateTableSQL(CMS_DB_PREFIX.'routes', $flds, $taboptarray);
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', 'routes', $msg_ret));

    $flds = '
id I KEY AUTO,
originator C(50) NOTNULL,
name C(100) NOTNULL,
dflt_contents X2,
description X,
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
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', CmsLayoutTemplateType::TABLENAME, $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_layout_tpl_type_1',
        CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME,
        'originator,name',
        ['UNIQUE']
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_layout_tpl_type_1', $msg_ret));

    $flds = '
id I KEY AUTO,
name C(100) NOTNULL,
description X,
item_order X,
modified I
';
    $sqlarray = $dbdict->CreateTableSQL(
        CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME,
        $flds,
        $taboptarray
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    verbose_msg(ilang('install_created_table', CmsLayoutTemplateCategory::TABLENAME, $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_layout_tpl_cat_1',
        CMS_DB_PREFIX.CmsLayoutTemplateCategory::TABLENAME,
        'name',
        ['UNIQUE']
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_layout_tpl_type_1', $msg_ret));

    $flds = '
id I KEY AUTO,
name C(100) NOTNULL,
content X2,
description X,
type_id I NOTNULL,
category_id I,
owner_id I NOTNULL,
type_dflt I(1) DEFAULT 0,
listable I(1) DEFAULT 1,
created I,
modified I
';
    $sqlarray = $dbdict->CreateTableSQL(
        CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,
        $flds,
        $taboptarray
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', CmsLayoutTemplate::TABLENAME, $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_layout_tpl_1',
        CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,
        'name',
        ['UNIQUE']
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_layout_tpl_1', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_layout_tpl_2',
        CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,
        'type_id,type_dflt'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_creating_index', 'idx_layout_tpl_2', $msg_ret));

    $flds = '
id I KEY AUTO,
name C(100) NOTNULL,
content X2,
description X,
media_type C(255),
media_query X,
created I,
modified I
';
    $sqlarray = $dbdict->CreateTableSQL(
        CMS_DB_PREFIX.CmsLayoutStylesheet::TABLENAME,
        $flds,
        $taboptarray
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', CmsLayoutStylesheet::TABLENAME, $msg_ret));
    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_layout_css_1',
        CMS_DB_PREFIX.CmsLayoutStylesheet::TABLENAME,
        'name',
        ['UNIQUE']
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_index', 'idx_layout_css_1', $msg_ret));

    $flds = '
tpl_id I KEY,
user_id I KEY
';
    //CHECKME separate index on user_id field ?
    $sqlarray = $dbdict->CreateTableSQL(
        CMS_DB_PREFIX.CmsLayoutTemplate::ADDUSERSTABLE,
        $flds,
        $taboptarray
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    verbose_msg(ilang('install_created_table', CmsLayoutTemplate::ADDUSERSTABLE, $msg_ret));

    $flds = '
id I KEY AUTO,
name C(100) NOTNULL,
description X,
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
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', CmsLayoutCollection::TABLENAME, $msg_ret));
    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'idx_layout_dsn_1',
        CMS_DB_PREFIX.CmsLayoutCollection::TABLENAME,
        'name',
        ['UNIQUE']
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_index', 'idx_layout_dsn_1', $msg_ret));

    $flds = '
design_id I KEY NOTNULL,
tpl_id I KEY NOTNULL
';
    //CHECKME separate index on tpl_id field ?
    $sqlarray = $dbdict->CreateTableSQL(
        CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE,
        $flds,
        $taboptarray
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', CmsLayoutCollection::TPLTABLE, $msg_ret));
    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'index_dsnassoc1',
        CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE,
        'tpl_id'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_index', 'index_dsnassoc1', $msg_ret));

    $flds = '
design_id I KEY NOTNULL,
css_id I KEY NOTNULL,
item_order I NOTNULL
';
    //CHECKME separate index on css_id field ?
    $sqlarray = $dbdict->CreateTableSQL(
        CMS_DB_PREFIX.CmsLayoutCollection::CSSTABLE,
        $flds,
        $taboptarray
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', CmsLayoutCollection::CSSTABLE, $msg_ret));

    $flds = '
id I AUTO KEY NOTNULL,
type C(20) NOTNULL,
oid I NOTNULL,
uid I NOTNULL,
created I NOTNULL,
modified I NOTNULL,
lifetime I NOTNULL,
expires I NOTNULL
';
    $sqlarray = $dbdict->CreateTableSQL(
        CMS_DB_PREFIX.CmsLock::LOCK_TABLE,
        $flds,
        $taboptarray
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
    verbose_msg(ilang('install_created_table', CmsLock::LOCK_TABLE, $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'index_locks1',
        CMS_DB_PREFIX.'locks',
        'type,oid',
        ['UNIQUE']
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    verbose_msg(ilang('install_created_index', 'index_locks1', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'index_locks2',
        CMS_DB_PREFIX.'locks',
        'expires'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    verbose_msg(ilang('install_created_index', 'index_locks2', $msg_ret));

    $sqlarray = $dbdict->CreateIndexSQL(
        CMS_DB_PREFIX.'index_locks3',
        CMS_DB_PREFIX.'locks',
        'uid'
    );
    $return = $dbdict->ExecuteSQLArray($sqlarray);
    verbose_msg(ilang('install_created_index', 'index_locks3', $msg_ret));
}
