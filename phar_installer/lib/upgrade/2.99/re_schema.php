<?php

if (!isset($handle)) {
    require __DIR__.DIRECTORY_SEPARATOR.'config.php';

    $handle = new mysqli(
    $config['db_hostname'],
    $config['db_username'],
    $config['db_password'],
    $config['db_name']
);

    if (!$handle) {
        die('no database connection');
    }

    $tblprefix = $config['db_prefix'] ?? 'cms_';
    $incl = true;
} else {
    $incl = false;
}

$add_defns = [];
$mod_defns = [];
$drop_defns = [];

// ~~~~~~~~~~~~~ TABLE ADDITIONS ~~~~~~~~~~~~

//data field holds a serialized class, size 1000 is probably enough
$flds = '
id I UNSIGNED AUTO NOTNULL PKEY(`id`),
name C(255) NOTNULL,
module C(50),
created I UNSIGNED NOTNULL,
start I UNSIGNED NOTNULL,
until I UNSIGNED DEFAULT 0,
recurs I2 UNSIGNED,
errors I2 UNSIGNED DEFAULT 0,
data C(2000),
';
$tabopts = '
ENGINE MyISAM,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$add_defns[$tblprefix.'asyncjobs'] = [$tabopts, $flds];

$flds = '
id I UNSIGNED AUTO NOTNULL PKEY(`id`),
originator C(50) NOTNULL,
name C(25) NOTNULL,
publicname_key C(64),
displayclass C(255) NOTNULL,
editclass C(255),
UNIQUE KEY i_originat_name (originator,name),
';
$tabopts = '
ENGINE MyISAM,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$add_defns[$tblprefix.'content_types'] = [$tabopts, $flds];

$flds = '
id I UNSIGNED AUTO PKEY(`id`),
name C(80) CHARACTER SET utf8mb4 NOTNULL,
reltoppath C(255),
data X(16383) NOTNULL,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
UNIQUE KEY i_name (name),
';
$tabopts = '
ENGINE MyISAM,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$add_defns[$tblprefix.'controlsets'] = [$tabopts, $flds];

$flds = '
token C(16) NOTNULL PKEY(`token`),
hash C(32) NOTNULL,
create_date DT DEFAULT CURRENT_TIMESTAMP,
';
$tabopts = '
ENGINE MyISAM,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$add_defns[$tblprefix.'job_records'] = [$tabopts, $flds];

$flds = '
id I UNSIGNED AUTO NOTNULL PKEY(`id`),
name C(60) CHARACTER SET utf8mb4 NOTNULL UNIQUE,
description C(1500) CHARACTER SET utf8mb4,
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
$tabopts = '
ENGINE MyISAM,
CHARACTER SET utf8mb4,
';
$add_defns[$tblprefix.'layout_css_groups'] = [$tabopts, $flds]; // aka StylesheetsGroup::TABLENAME

$flds = '
id I UNSIGNED AUTO NOTNULL PKEY(`id`),
group_id I UNSIGNED NOTNULL,
css_id I UNSIGNED NOTNULL,
item_order I1 UNSIGNED DEFAULT 0,
';
$tabopts = '
ENGINE MyISAM,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$add_defns[$tblprefix.'layout_cssgroup_members'] = [$tabopts, $flds]; // aka StylesheetsGroup::MEMBERSTABLE

$flds = '
id I UNSIGNED AUTO NOTNULL PKEY(`id`),
originator C(50),
name C(60) CHARACTER SET utf8mb4 NOTNULL,
description C(1500) CHARACTER SET utf8mb4,
lang_cb C(255),
dflt_content_cb C(255),
help_content_cb C(255),
owner_id I UNSIGNED DEFAULT 1,
has_dflt I1 UNSIGNED DEFAULT 0,
dflt_content X(65535),
create_date DT DEFAULT CURRENT_TIMESTAMP,
modified_date DT ON UPDATE CURRENT_TIMESTAMP,
UNIQUE KEY i_originat_name (originator,name),
';
$tabopts = '
ENGINE MyISAM,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$add_defns[$tblprefix.'layout_css_types'] = [$tabopts, $flds]; // future StylesheetsType::TABLENAME

$flds = '
id I UNSIGNED AUTO NOTNULL PKEY(`id`),
group_id I UNSIGNED NOTNULL,
tpl_id I UNSIGNED NOTNULL,
item_order I1 UNSIGNED DEFAULT 0,
';
$tabopts = '
ENGINE MyISAM,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$add_defns[$tblprefix.'layout_tplgroup_members'] = [$tabopts, $flds]; // aka TemplatesGroup::MEMBERSTABLE

// ~~~~~~~~~~~~~ TABLE MODIFICATIONS ~~~~~~~~~~~~~
$flds = '
MODIFY additional_users_id I UNSIGNED AUTO,
MODIFY content_id I UNSIGNED,
DROP page_id,
';
$tabopts = '
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'additional_users'] = [$tabopts, $flds];

$flds = '
CHANGE action message C(512) CHARACTER SET utf8mb4,
ADD id I UNSIGNED AUTO NOTNULL FIRST APKEY(`id`),
MODIFY ip_addr C(40),
MODIFY item_id C(6) DEFAULT "",
CHANGE item_name subject C(255),
ADD severity I1 UNSIGNED NOTNULL DEFAULT 0 AFTER timestamp,
MODIFY timestamp I UNSIGNED NOTNULL,
MODIFY user_id C(6) DEFAULT "",
MODIFY username C(80) CHARACTER SET utf8mb4 DEFAULT "",
';
$tabopts = '
DROP KEY '.$tblprefix.'index_adminlog1,
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'adminlog'] = [$tabopts, $flds];

$flds = '
MODIFY bookmark_id I UNSIGNED AUTO,
MODIFY title C(40) CHARACTER SET utf8mb4 NOTNULL,
MODIFY url C(255),
MODIFY user_id I UNSIGNED,
';
$tabopts = '
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'admin_bookmarks'] = [$tabopts, $flds];

// default cachable value (1) is contrary to CMSMS pre-2.99
// NOTE tabindex empty-string values must be NULL'd to allow converting varchar to int
// migrate/defer to non-core props: tpltype_id I UNSIGNED, csstype_id I UNSIGNED,
$flds = '
MODIFY accesskey C(8),
MODIFY active I1 UNSIGNED DEFAULT 1,
MODIFY cachable I1 UNSIGNED DEFAULT 1,
DROP collapsed,
MODIFY content_alias C(255),
MODIFY content_id I UNSIGNED,
MODIFY content_name C(255) CHARACTER SET utf8mb4,
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY default_content I1 UNSIGNED DEFAULT 0,
MODIFY hierarchy C(40) COLLATE ascii_bin,
MODIFY hierarchy_path C(500),
MODIFY id_hierarchy C(50) COLLATE ascii_bin,
MODIFY item_order I1 UNSIGNED DEFAULT 0,
MODIFY last_modified_by I UNSIGNED DEFAULT 0,
MODIFY menu_text C(255) CHARACTER SET utf8mb4,
MODIFY metadata C(10000),
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
MODIFY owner_id I UNSIGNED DEFAULT 1,
Modify page_url C(255),
DROP prop_names,
MODIFY secure I1 UNSIGNED DEFAULT 0,
MODIFY show_in_menu I1 UNSIGNED DEFAULT 1,
ADD styles C(50),
MODIFY tabindex I1 UNSIGNED DEFAULT 0,
MODIFY template_id I UNSIGNED,
MODIFY titleattribute C(255) CHARACTER SET utf8mb4,
MODIFY type C(25) NOTNULL,
';
// this key is used in FORCE INDEX hints
$tabopts = '
ADD KEY i_contentid_hierarchy (content_id,hierarchy),
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'content'] = [$tabopts, $flds];

$flds = '
MODIFY content X(65535) CHARACTER SET utf8mb4,
MODIFY content_id I UNSIGNED,
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
MODIFY param1 C(255) CHARACTER SET utf8mb4,
MODIFY param2 C(255) CHARACTER SET utf8mb4,
MODIFY param3 C(255) CHARACTER SET utf8mb4,
MODIFY prop_name C(255) NOTNULL,
MODIFY type C(25) NOTNULL,
';
$tabopts = '
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'content_props'] = [$tabopts, $flds];

$flds = '
MODIFY event_id I UNSIGNED AUTO NOTNULL FIRST APKEY(`event_id`),
MODIFY event_name C(40) NOTNULL,
MODIFY originator C(50) NOTNULL,
';
$tabopts = '
DROP PRIMARY KEY,
DROP KEY '.$tblprefix.'event_id,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'events'] = [$tabopts, $flds];

$flds = '
MODIFY event_id I UNSIGNED,
MODIFY handler_id I UNSIGNED AUTO FIRST,
MODIFY handler_order I1 UNSIGNED DEFAULT 0,
CHANGE module_name class C(96),
ADD method C(64) AFTER class,
MODIFY removable I1 UNSIGNED DEFAULT 1,
DROP tag_name,
ADD type C(1) NOTNULL DEFAULT "C" AFTER method,
';
$tabopts = '
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'event_handlers'] = [$tabopts, $flds];

$flds = '
MODIFY active I1 UNSIGNED DEFAULT 1,
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY group_desc C(255) CHARACTER SET utf8mb4 AFTER group_name,
MODIFY group_id I UNSIGNED AUTO,
MODIFY group_name C(50) CHARACTER SET utf8mb4 NOTNULL,
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
$tabopts = '
ADD UNIQUE KEY i_groupname (group_name),
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'groups'] = [$tabopts, $flds];

$flds = '
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY group_id I UNSIGNED,
MODIFY group_perm_id I UNSIGNED AUTO,
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
MODIFY permission_id I UNSIGNED,
';
$tabopts = '
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'group_perms'] = [$tabopts, $flds];

// UPDATE tbl SET *_date = DATE_FORMAT(FROM_UNIXTIME(*_stamp), '%Y-%m-%d %H:%i:%s') WHERE *_stamp > 0
//// migrate layout_designs created,modified expected to produce
////create_date DT DEFAULT CURRENT_TIMESTAMP,
////modified_date DT ON UPDATE CURRENT_TIMESTAMP,
$flds = '
MODIFY description C(1500) CHARACTER SET utf8mb4,
DROP dflt,
MODIFY id I UNSIGNED NOTNULL AUTO,
MODIFY name C(50) CHARACTER SET utf8mb4 NOTNULL,
ADD create_date DT DEFAULT CURRENT_TIMESTAMP,
ADD modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
$tabopts = '
RENAME TO `'.$tblprefix.'module_designs`,
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'layout_designs'] = [$tabopts, $flds];

//old primary key on design_id,css_id
$flds = '
MODIFY css_id I UNSIGNED NOTNULL,
MODIFY design_id I UNSIGNED NOTNULL,
ADD id I UNSIGNED NOTNULL AUTO FIRST APKEY(`id`),
CHANGE item_order css_order I1 UNSIGNED DEFAULT 0,
';
$tabopts = '
RENAME TO `'.$tblprefix.'module_designs_css`,
DROP PRIMARY KEY,
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'layout_design_cssassoc'] = [$tabopts, $flds];

// old primary key on design_id, tpl_id
// another old key on tpl_id
$flds = '
MODIFY design_id I UNSIGNED NOTNULL,
ADD id I UNSIGNED NOTNULL AUTO FIRST APKEY(`id`),
MODIFY tpl_id I UNSIGNED NOTNULL,
ADD tpl_order I1 UNSIGNED DEFAULT 0,
';
$tabopts = '
RENAME TO `'.$tblprefix.'module_designs_tpl`,
DROP PRIMARY KEY,
DROP KEY '.$tblprefix.'index_dsnassoc1,
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'layout_design_tplassoc'] = [$tabopts, $flds];

//// migrate layout_stylesheets created,modified expected to produce
////create_date DT DEFAULT CURRENT_TIMESTAMP,
////modified_date DT ON UPDATE CURRENT_TIMESTAMP,
$flds = '
MODIFY content X(65535),
MODIFY description C(1500) CHARACTER SET utf8mb4,
MODIFY id I UNSIGNED AUTO NOTNULL FIRST,
MODIFY media_query C(255),
MODIFY media_type C(255),
MODIFY name C(60) CHARACTER SET utf8mb4 NOTNULL,
ADD originator C(50) AFTER id,
ADD owner_id I UNSIGNED DEFAULT 1 AFTER media_query,
ADD type_id I UNSIGNED,
ADD type_dflt I1 UNSIGNED DEFAULT 0,
ADD listable I1 UNSIGNED DEFAULT 1,
ADD contentfile I1 UNSIGNED DEFAULT 0,
ADD create_date DT DEFAULT CURRENT_TIMESTAMP,
ADD modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
$tabopts = '
DROP KEY '.$tblprefix.'idx_layout_css_1,
ADD KEY i_name (name),
ADD KEY i_typeid_typedflt (type_id,type_dflt),
ADD UNIQUE KEY i_originat_name (originator,name),
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'layout_stylesheets'] = [$tabopts, $flds];

//// migrate layout_templates created,modified expected to produce
////create_date DT DEFAULT CURRENT_TIMESTAMP,
////modified_date DT ON UPDATE CURRENT_TIMESTAMP,
//// defer DROP category_id, until processed later
$flds = '
MODIFY content X(65535) CHARACTER SET utf8mb4,
ADD contentfile I1 UNSIGNED DEFAULT 0,
MODIFY description C(1500) CHARACTER SET utf8mb4,
ADD hierarchy C(100) COLLATE ascii_bin AFTER description,
MODIFY id I UNSIGNED NOTNULL AUTO,
MODIFY name C(60) CHARACTER SET utf8mb4 NOTNULL,
ADD originator C(50) AFTER id,
MODIFY owner_id I UNSIGNED DEFAULT 1 AFTER hierarchy,
MODIFY type_dflt I1 UNSIGNED DEFAULT 0 AFTER owner_id,
MODIFY type_id I UNSIGNED,
ADD create_date DT DEFAULT CURRENT_TIMESTAMP,
ADD modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
// DROP UNIQUE index on name add UNIQUE on (originator,name),
$tabopts = '
DROP KEY '.$tblprefix.'idx_layout_tpl_1,
ADD UNIQUE KEY i_originat_name (originator,name),
ADD KEY i_name (name),
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'layout_templates'] = [$tabopts, $flds];

$tbl = $tblprefix.'layout_tpl_addusers'; //aka TemplateOperations::ADDUSERSTABLE
$flds = '
MODIFY tpl_id I UNSIGNED,
MODIFY user_id I UNSIGNED,
';
$tabopts = '
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'layout_tpl_addusers'] = [$tabopts, $flds];

//// migrate layout_tpl_categories/groups modified ONLY expected to produce
////modified_date DT ON UPDATE CURRENT_TIMESTAMP,
$flds = '
ADD create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY description C(1500),
MODIFY id I UNSIGNED AUTO,
DROP item_order,
MODIFY name C(60) NOTNULL,
ADD modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
// relevant ? ADD UNIQUE '.$tblprefix.'i_originat_name (originator,name),
$tabopts = '
RENAME TO `'.$tblprefix.'layout_tpl_groups`,
CHARACTER SET utf8mb4,
';
$mod_defns[$tblprefix.'layout_tpl_categories'] = [$tabopts, $flds];

//// migrate layout_tpl_types created,modified stamps to DT's
$flds = '
MODIFY description C(1500) CHARACTER SET utf8mb4,
MODIFY dflt_content_cb C(255),
CHANGE dflt_contents dflt_content X(65535) CHARACTER SET utf8mb4 AFTER owner_id,
MODIFY help_content_cb C(255) AFTER dflt_content_cb,
MODIFY has_dflt I1 UNSIGNED DEFAULT 0 AFTER help_content_cb,
MODIFY id I UNSIGNED AUTO NOTNULL,
MODIFY lang_cb C(255),
MODIFY name C(60) CHARACTER SET utf8mb4 NOTNULL,
MODIFY one_only I1 UNSIGNED DEFAULT 0 AFTER requires_contentblocks,
MODIFY originator C(50),
CHANGE owner owner_id I UNSIGNED DEFAULT 1,
MODIFY requires_contentblocks I1 UNSIGNED DEFAULT 0,
ADD create_date DT DEFAULT CURRENT_TIMESTAMP,
ADD modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
//ADD UNIQUE '.$tblprefix.'i_originat_name (originator,name),
//DROP KEY '.$tblprefix.'idx_tpltype_orgname,
$tabopts = '
RENAME TO `'.$tblprefix.'layout_tpl_types`,
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'layout_tpl_type'] = [$tabopts, $flds];

//// migrate locks created,modified stamps to DT's
$flds = '
MODIFY expires I UNSIGNED DEFAULT 0,
MODIFY id I UNSIGNED AUTO,
MODIFY lifetime I2 UNSIGNED,
MODIFY oid I UNSIGNED NOTNULL,
MODIFY type C(25) NOTNULL,
MODIFY uid I UNSIGNED NOTNULL,
ADD create_date DT DEFAULT CURRENT_TIMESTAMP,
ADD modified_date DT ON UPDATE CURRENT_TIMESTAMP,
';
$tabopts = '
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'locks'] = [$tabopts, $flds];

$flds = '
MODIFY active I1 UNSIGNED DEFAULT 1,
MODIFY admin_only I1 UNSIGNED DEFAULT 0,
DROP allow_admin_lazyload,
DROP allow_fe_lazyload,
MODIFY module_name C(50) NOTNULL,
DROP status,
MODIFY version C(16),
';
$tabopts = '
DROP KEY '.$tblprefix.'index_modules_by_module_name,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'modules'] = [$tabopts, $flds];

$flds = '
MODIFY child_module C(50),
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY minimum_version C(16),
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
MODIFY parent_module C(50),
';
$tabopts = '
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'module_deps'] = [$tabopts, $flds];

// default cachable value (1) is contrary to CMSMS pre-2.99
$flds = '
MODIFY available I1 UNSIGNED DEFAULT 1,
MODIFY cachable I1 UNSIGNED DEFAULT 1,
CHANGE callback callable C(255) NOTNULL,
ADD id I UNSIGNED NOTNULL AUTO FIRST APKEY(`id`),
MODIFY module C(50) NOTNULL,
MODIFY name C(50) NOTNULL,
DROP sig,
MODIFY type C(25) NOTNULL DEFAULT "function",
';
$tabopts = '
DROP KEY '.$tblprefix.'idx_smp_module,
ADD UNIQUE KEY i_name_modul (name,module),
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'module_smarty_plugins'] = [$tabopts, $flds];

$flds = '
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
CHANGE permission_id id I UNSIGNED AUTO NOTNULL,
CHANGE permission_name name C(50) NOTNULL,
CHANGE permission_text description C(255) CHARACTER SET utf8mb4,
CHANGE permission_source originator C(50) NOTNULL AFTER description,
';
$tabopts = '
ADD UNIQUE KEY i_originat_name (originator,name),
ADD KEY i_name (name),
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'permissions'] = [$tabopts, $flds];

$flds = '
CHANGE created create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY data C(600) NOTNULL,
ADD id I UNSIGNED AUTO NOTNULL FIRST APKEY(`id`),
CHANGE key1 dest1 C(50) COLLATE ascii_general_ci NOTNULL,
CHANGE key2 page C(10),
CHANGE key3 delmatch C(100) COLLATE ascii_general_ci,
MODIFY term C(300) NOTNULL,
';
// prob. invalid index before field-sizes are changed (max total 1000) ?
// must add index later c.f. ADD UNIQUE KEY i_dest_page_term (key1,key2,term)
$tabopts = '
DROP PRIMARY KEY,
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'routes'] = [$tabopts, $flds];

$flds = '
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
MODIFY sitepref_name C(255) NOTNULL UNIQUE,
MODIFY sitepref_value X(65535) CHARACTER SET utf8mb4,
';
$tabopts = '
DROP PRIMARY KEY,
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'siteprefs'] = [$tabopts, $flds];

// name must support case-insenstive matching, TODO might include UTF8 ?
// code might include UTF8 for UI ?
$flds = '
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY description C(1500) CHARACTER SET utf8mb4,
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
ADD parameters C(1000) CHARACTER SET utf8mb4 AFTER description,
ADD contentfile I1 UNSIGNED DEFAULT 0 AFTER parameters,
MODIFY code C(15000) AFTER contentfile,
CHANGE userplugin_id id I UNSIGNED AUTO,
CHANGE userplugin_name name C(50) NOTNULL UNIQUE,
';
$tabopts = '
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'userplugins'] = [$tabopts, $flds];

$flds = '
MODIFY preference C(255) NOTNULL,
DROP type,
MODIFY user_id I UNSIGNED NOTNULL,
MODIFY value X(65535) CHARACTER SET utf8mb4,
';
$tabopts = '
DROP PRIMARY KEY,
DROP KEY '.$tblprefix.'index_userprefs_by_user_id,
ADD KEY i_userid (user_id),
ADD UNIQUE KEY i_userid_preferen (user_id,preference),
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'userprefs'] = [$tabopts, $flds];

$flds = '
MODIFY active I1 UNSIGNED DEFAULT 1,
DROP admin_access,
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY email C(255) CHARACTER SET utf8mb4,
MODIFY first_name C(64) CHARACTER SET utf8mb4,
MODIFY last_name C(64) CHARACTER SET utf8mb4,
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
MODIFY password C(128),
ADD tailor B(16384),
MODIFY user_id I UNSIGNED,
MODIFY username C(80) CHARACTER SET utf8mb4 NOTNULL UNIQUE,
';
$tabopts = '
CHARACTER SET ascii,
';
$mod_defns[$tblprefix.'users'] = [$tabopts, $flds];

$flds = '
MODIFY create_date DT DEFAULT CURRENT_TIMESTAMP,
MODIFY group_id I UNSIGNED NOTNULL,
MODIFY modified_date DT ON UPDATE CURRENT_TIMESTAMP,
MODIFY user_id I UNSIGNED NOTNULL,
';
$tabopts = '
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'user_groups'] = [$tabopts, $flds];

$flds = '
MODIFY id I UNSIGNED NOTNULL,
';
$tabopts = '
CHARACTER SET ascii,
COLLATE ascii_bin,
';
$mod_defns[$tblprefix.'content_seq'] = [$tabopts, $flds];
$mod_defns[$tblprefix.'users_seq'] = [$tabopts, $flds];
$mod_defns[$tblprefix.'module_news_seq'] = [$tabopts, $flds];
$mod_defns[$tblprefix.'module_search_items_seq'] = [$tabopts, $flds];

// ~~~~~~~~~~~~~ TABLE DELETIONS ~~~~~~~~~~~~
// others need to wait until after content adjustments, elsewhere

foreach ([
    'additional_users_seq',
    'admin_bookmarks_seq',
    'admin_recent_pages_seq',
    'content_props_seq',
    'event_handler_seq',
    'events_seq',
    'groups_seq',
    'group_perms_seq',
    'permissions_seq',
    'userplugins_seq',
    'admin_recent_pages',
    'crossref',
    'mod_cmsjobmgr',
    'version',
] as $suffx) {
    $drop_defns[] = $tblprefix.$suffx;
}

$realnames = function($defn) {
    $matches = [];
    $exp = preg_replace_callback('~(B|X)(\(\d+\))?~', function($matches) {
        if ($matches[2]) {
            $s = trim($matches[2], ' ()');
            $d = max((int)$s, 256);
            if ($d <= 256) {
                $s = 'tiny';
            } elseif ($d <= 65536) {
                $s = '';
            } elseif ($d <= 1 << 24) {
                $s = 'medium';
            } else {
                $s = 'long';
            }
        } else {
            $s = '';
        }
        $s .= ($matches[1] === 'X') ? 'text' : 'blob';
        return $s;
    }, $defn);
    // this reflects what's needed here ATM. Complicate the conversion as appropriate. NB stet 'DROP PRIMARY KEY', 'DROP KEY name', 'ADD KEY name'
    $exp = str_replace(
     [' I(',   ' I1',      ' I2',       ' I',   ' C(',       ' DT',       ' AUTO',           ' APKEY(',             ' PKEY',         'NOTNULL'],
     [' int(', ' tinyint', ' smallint', ' int', ' varchar(', ' datetime', ' AUTO_INCREMENT', ', ADD PRIMARY KEY (', ', PRIMARY KEY', 'NOT NULL'],
    $exp);
    return $exp;
};

foreach ($add_defns as $tbl => $props) {
    $tblopts = trim($props[0], " ,\r\n");
    $s = trim($props[1], " ,\r\n");
    $fldopts = $realnames($s);
    $sql = <<<EOS
CREATE TABLE `$tbl` (
$fldopts
)
$tblopts
EOS;
    $res = $handle->query($sql);
    if (!$res) {
        if ($incl) {
            echo "TABLE $tbl ADDITION ERROR ".$handle->error.'<br/>';
        } else {
            error_msg("Table $tbl addition error: ".$handle->error);
        }
    }
}

// prepare for cleanups
$sql = 'UPDATE '.$tblprefix.'users SET active = 0 WHERE active = 1 AND admin_access = 0';
$res = $handle->query($sql);
// content::tabindex '' values must be NULL'd to allow change varchar to int
$sql = 'UPDATE '.$tblprefix.'content SET tabindex = NULL WHERE tabindex = \'0\' OR tabindex = ""';
$res = $handle->query($sql);
// permissions::permission_source NULL values must be populated (permission::CORE) to allow NOT NULL
$sql = 'UPDATE '.$tblprefix.'permissions SET permission_source = \'__CORE__\' WHERE permission_source IS NULL OR permission_source = \'Core\'';
$res = $handle->query($sql);

foreach ($mod_defns as $tbl => $props) {
    $tblopts = trim($props[0], " \r\n");
    $s = trim($props[1], " ,\r\n");
    $fldopts = $realnames($s);
    $sql = <<<EOS
ALTER TABLE `$tbl`
$tblopts
$fldopts
EOS;
    $res = $handle->query($sql);
    if (!$res) {
        if ($incl) {
            echo "TABLE $tbl CHANGE ERROR ".$handle->error.'<br/>';
        } else {
            error_msg("Table $tbl change error: ".$handle->error);
        }
    }
}

// migrate timestamp fields to datetime

// pretend we're in UTC timezone (like the recorded stamps)
$rst = $handle->query('SHOW VARIABLES LIKE \'time_zone\'');
if ($rst) {
    $offsave = $rst->fetch_array(MYSQLI_NUM);
    $rst->close();
} else {
    $offsave = [0 => 'time_zone', 1 => 'SYSTEM'];
}
$handle->query("SET time_zone = '+00:00'");

foreach ([
    ['layout_stylesheets', 1],
    ['layout_templates', 1],
    ['layout_tpl_types', 1],
    ['layout_tpl_groups', 3], //modified only
    ['layout_stylesheets', 1],
    ['locks', 1],
    ['module_designs', 1],
] as $row) {
    $tbl = $tblprefix.$row[0];
    switch ($row[1]) {
        case 1:
            //NOTE these convert stamps to server-timezone datetimes, not UTC/GMT
        $sqlary = ['UPDATE '.$tbl.' SET create_date = FROM_UNIXTIME(created, \'%Y-%m-%d %H:%i:%s\') WHERE created > 0',
                   'UPDATE '.$tbl.' SET modified_date = FROM_UNIXTIME(modified, \'%Y-%m-%d %H:%i:%s\') WHERE modified > 0',
                   'UPDATE '.$tbl.' SET modified_date = NULL WHERE modified_date IS NOT NULL AND modified_date <= create_date',
                   'ALTER TABLE `'.$tbl.'` DROP COLUMN created, DROP COLUMN modified',
            ];
        break;
        case 2:
        $sqlary = ['UPDATE '.$tbl.' SET create_date = FROM_UNIXTIME(created, \'%Y-%m-%d %H:%i:%s\') WHERE created > 0',
                   'ALTER TABLE `'.$tbl.'` DROP COLUMN created',
            ];
        break;
        case 3:
        $sqlary = ['UPDATE '.$tbl.' SET modified_date = FROM_UNIXTIME(modified, \'%Y-%m-%d %H:%i:%s\') WHERE modified > 0',
                   'ALTER TABLE `'.$tbl.'` DROP COLUMN modified',
            ];
        break;
    }
    $res2 = true;
    foreach ($sqlary as $sql) {
        $res = $handle->query($sql);
        $res2 = $res2 && $res;
    }
}
// revert to prior timezone
$handle->query("SET time_zone = '".$offsave[1]."'");

$namequote = function(string $str) : string {
    $str = strtr(trim($str, ' \'"'), '"', "'");
    return '"'.$str.'"';
};

// migrate module templates to layout-templates table
$sql = 'SELECT * FROM '.$tblprefix.'module_templates ORDER BY module_name,template_name';
$rst = $handle->query($sql);
if ($rst) {
    $data = $rst->fetch_all(MYSQLI_ASSOC);
    $rst->close();
    $longnow = date('Y-m-d H:i:s', time()); // UTC prob. wrong zone for server
    $types = [];
    $gid = 0;
    // aka TemplateType::TABLENAME
    $stmt = $handle->prepare("INSERT INTO {$tblprefix}layout_tpl_types (originator,name,description,owner_id) VALUES (?,'moduleactions',?,0)");
    $stmt2 = $handle->prepare("INSERT INTO {$tblprefix}layout_tpl_groups (name,description,create_date) VALUES (?,?,?)");
    // category_id used only for transition
    $stmt3 = $handle->prepare("INSERT INTO {$tblprefix}layout_templates (originator,name,content,type_id,category_id,create_date,modified_date)
VALUES (?,?,?,?,?,?,?)");
    foreach ($data as $row) {
        $name = $row['module_name'];
        if (!isset($types[$name])) {
            $s = $namequote($name); // length limit applies
            $q = "Action templates for $s module";
            $res = $stmt->bind_param('ss', $name, $q);
            $res2 = $stmt->execute();
            if ($res2) {
                $types[$name] = $handle->insert_id;
            }
            $s = "$name templates"; // length limit applies
            $q = 'Templates for displaying module-action output';
            $res = $stmt2->bind_param('sss', $s, $q, $longnow);
            $res2 = $stmt2->execute();
            $gid = $handle->insert_id;
        }
        // binding variable-references N/A
        $s = $row['template_name'];
        $tmp = $row['content'];
        $p = (int)$types[$name];
        $d1 = $row['create_date'];
        $d2 = $row['modified_date'];
        $res = $stmt3->bind_param('sssiiss', $name, $s, $tmp, $p, $gid, $d1, $d2);
        $res2 = $stmt3->execute();
        if (!$res || !$res2) {
            if ($incl) {
                echo "MODULE-TEMPLATE '$s' TRANSER ERROR ".$stmt3->error.'<br/>';
            } else {
                error_msg("Module-template '$s' transer error: ".$stmt3->error);
            }
        }
    }
    $stmt->close();
    $stmt2->close();
    $stmt3->close();
    $res = $handle->query("UPDATE {$tblprefix}layout_templates SET modified_date = NULL WHERE modified_date IS NOT NULL AND modified_date <= create_date");
}

// if (SUCCESS)
$drop_defns[] = $tblprefix.'module_templates';

// migrate existing template category_id values to new table
$sql = 'SELECT id,category_id FROM '.$tblprefix.'layout_templates WHERE category_id IS NOT NULL ORDER BY category_id, name'; // aka TemplateOperations::TABLENAME
$rst = $handle->query($sql);
if ($rst) {
    $data = $rst->fetch_all(MYSQLI_ASSOC);
    $rst->close();
    $thiscid = -1;
    // aka TemplatesGroup::MEMBERSTABLE
    $tmpl = "INSERT INTO {$tblprefix}layout_tplgroup_members (group_id,tpl_id,item_order) VALUES (%d,%d,%d)";
    foreach ($data as $row) {
        $cid = (int)$row['category_id'];
        if ($cid !== $thiscid) {
            $thiscid = $cid;
            $o = 1;
        }
        $sql = sprintf($tmpl, $cid, (int)$row['id'], $o++);
        $res = $handle->query($sql);
        $res2 = $handle->insert_id;
        $here = 1;
    }
}
// now we're done transitioning
$handle->query("ALTER TABLE `{$tblprefix}layout_templates` DROP COLUMN category_id");

//migrate filepicker_profiles to core controlsets
$tbl = $tblprefix.'mod_filepicker_profiles';
$rst = $handle->query('SELECT * FROM '.$tbl);
if ($rst) {
    $data = $rst->fetch_all(MYSQLI_ASSOC);
    $rst->close();
    $nullfy = false;
    $stmt = $handle->prepare("INSERT INTO {$tblprefix}controlsets (name,reltoppath,data,create_date,modified_date) VALUES (?,?,?,?,?)");
    foreach ($data as $row) {
        $arr = unserialize($row['data']); // no class in there
        $reltop = (!empty($arr['top'])) ? $arr['top'] : 'uploads'; //CHECKME should be site-root-relative TODO later: replace by actual config['uploads_path']
        unset($arr['top']);
        $raw = json_encode($arr, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
        $cst = $row['create_date'];
        if ($cst < 1) {
            $cst = time() - 2592000; // default to 30-days prior
        }
        $cd = date('Y-m-d H:i:s', $cst); // convert stamp to db datetime
        $mst = $row['modified_date'];
        if ($mst >= $cst) {
            $md = date('Y-m-d H:i:s', $mst);
        } else {
            $md = '';
            $nullfy = true;
        }
        $res = $stmt->bind_param('sssss', $row['name'], $reltop, $raw, $cd, $md);
        $res2 = $stmt->execute();
    }
    $stmt->close();
    if ($nullfy) {
        $handle->query("UPDATE {$tblprefix}controlsets SET modified_date=NULL WHERE modified_date=''");
    }
}
$handle->query('DROP TABLE '.$tbl); // don't care if this fails

// migrate design stylesheets and/or templates to layout tables and pages as respective groups
$rst = $handle->query('SELECT id,name,create_date,modified_date FROM '.$tblprefix.'module_designs');
if ($rst) {
    $data = $rst->fetch_all(MYSQLI_ASSOC);
    $rst->close();
    if ($data) {
        $designs = [];
        foreach ($data as $row) {
            $id = array_shift($row);
            $designs[$id] = $row;
        }
        $rst = $handle->query('SELECT A.* FROM '.
            $tblprefix.'module_designs_tpl A LEFT JOIN '.
            $tblprefix.'layout_templates T ON A.tpl_id=T.id ORDER BY A.design_id,A.tpl_order,T.name');
        if ($rst) {
            $templates = $rst->fetch_all(MYSQLI_ASSOC);
            $rst->close();

            $bank = [];
            foreach ($templates as &$row) {
                $id = (int)$row['design_id'];
                if (!isset($bank[$id])) {
                    $bank[$id] = [];
                }
                $bank[$id][] = (int)$row['tpl_id'];
            }
            $stmt = $handle->prepare("INSERT INTO {$tblprefix}layout_tpl_groups
(name,description,create_date,modified_date) VALUES (?,?,?,?)");
            $tmpl = "INSERT INTO {$tblprefix}layout_tplgroup_members
(group_id,tpl_id,item_order) VALUES (%d,%d,%d)";
            foreach ($bank as $id => $row) {
                // binding variable-references N/A
                $s = $designs[$id]['name'];
                $tmp = $namequote($s);
                $q = "Templates migrated from $tmp design";
                $d1 = $designs[$id]['create_date'];
                $d2 = $designs[$id]['modified_date'];
                $res = $stmt->bind_param('ssss', $s, $q, $d1, $d2);
                $res2 = $stmt->execute();
                if ($res && $res2) {
                    $gid = $handle->insert_id;
                    $o = 0;
                    foreach ($row as $tplid) {
                        $sql = sprintf($tmpl, $gid, $tplid, ++$o);
                        $res = $handle->query($sql);
                    }
                } else {
                    if ($incl) {
                        echo "DESIGN $tmp TEMPLATES-MIGRATION ERROR ".$stmt->error.'<br/>';
                    } else {
                        error_msg("Design $tmp templates-migration error: ".$stmt->error);
                    }
                }
            }
            $stmt->close();
        } // design-template(s) exist

        $rst = $handle->query('SELECT design_id,css_id FROM '.$tblprefix.'module_designs_css ORDER BY design_id,css_order');
        if ($rst) {
            $sheets = $rst->fetch_all();
            $rst->close();
            $bank = [];
            foreach ($sheets as $row) { // [0] = design_id, [1] = css_id
                $id = (int)$row[0];
                if (!isset($bank[$id])) {
                    $bank[$id] = [];
                }
                $bank[$id][] = (int)$row[1];
            }
            $trans = []; // design-id cache
            $sizes = []; // item-count cache
            $stmt = $handle->prepare("INSERT INTO {$tblprefix}layout_css_groups
(name,description,create_date,modified_date) VALUES(?,?,?,?)");
            $tmpl = "INSERT INTO {$tblprefix}layout_cssgroup_members
(group_id,css_id,item_order) VALUES (%d,%d,%d)";
            foreach ($bank as $id => $row) {
                $s = $designs[$id]['name'];
                $tmp = $namequote($s);
                $q = "Stylesheets migrated from $tmp design";
                $d1 = $designs[$id]['create_date'];
                $d2 = $designs[$id]['modified_date'];
                $res = $stmt->bind_param('ssss', $s, $q, $d1, $d2);
                $res2 = $stmt->execute();
                if ($res && $res2) {
                    $trans[$id] = $handle->insert_id;
                    $sizes[$id] = 0;
                    $o = 0;
                    foreach ($row as $cssid) {
                        $sql = sprintf($tmpl, $trans[$id], $cssid, ++$o);
                        $res = $handle->query($sql);
                        ++$sizes[$id];
                    }
                } else {
                    if ($incl) {
                        echo "DESIGN $tmp STYLESHEETS-MIGRATION ERROR ".$stmt->error.'<br/>';
                    } else {
                        error_msg("Design $tmp stylesheets-migration error: ".$stmt->error);
                    }
                }
            }
            $stmt->close();

            // set page-stylesheets
            $rst = $handle->query('SELECT C.content_id,P.content AS design_id FROM '.
                $tblprefix.'content C JOIN '.
                $tblprefix.'content_props P ON C.content_id=P.content_id WHERE P.prop_name=\'design_id\'');
            if ($rst) {
                $pages = $rst->fetch_all();
                $rst->close();
                $stmt = $handle->prepare('UPDATE '.$tblprefix.'content SET styles=? WHERE content_id=?');
                foreach ($pages as $row) { // [0] = content_id, [1] = design_id
                    $did = $row[1];
                    if (!empty($trans[$did])) {
                        if ($sizes[$did] == 1) {
                            $num = (string)$sheets[$did][1]; // the id of that one
                        } else {
                            $num = (string)(-$trans[$did]); // group id's recorded < 0
                        }
                        // binding variable-references N/A
                        $id = (int)$row[0];
                        $res = $stmt->bind_param('si', $num, $id);
                        $res2 = $stmt->execute();
                        if (!$res || !$res2) {
                            if ($incl) {
                                echo "PAGE $id STYLESHEET(S)-ASSIGNMENT ERROR ".$stmt->error.'<br/>';
                            } else {
                                error_msg("Page $id stylesheet(s)-assignment error: ".$stmt->error);
                            }
                        }
                    }
                }
                $stmt->close();
            }
        } // design-sheet(s) exist
    } // design(s) exist
//NOT YET $handle->query('DELETE FROM '.$tblprefix.'content_props WHERE prop_name=\'design_id\'');
}

// drop redundant tables
foreach ($drop_defns as $tbl) {
    $res = $handle->query('DROP TABLE '.$tbl);
    if (!$res) {
        if ($incl) {
            echo 'DROP TABLE ERROR '.$handle->error.'<br/>';
        } else {
            error_msg('Drop table error: '.$handle->error);
        }
    }
}

if ($incl) {
    $handle->close();
}
