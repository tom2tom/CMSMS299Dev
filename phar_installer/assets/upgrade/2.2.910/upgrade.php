<?php

use CMSMS\Group;
use function __installer\CMSMS\endswith;
use function __installer\CMSMS\joinpath;

//extra permissions
foreach( [
 'Modify Simple Plugins',
 'Modify Site Code',
// 'Modify Site Assets',
 'Remote Administration',  //for app management sans admin console
] as $one_perm ) {
  $permission = new CmsPermission();
  $permission->source = 'Core';
  $permission->name = $one_perm;
  $permission->text = $one_perm;
  $permission->save();
}

$group = new Group();
$group->name = 'CodeManager';
$group->description = ilang('grp_coder_desc');
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Site Code');
//$group->GrantPermission('Modify Site Assets');
$group->GrantPermission('Modify Simple Plugins');
/*
$group = new Group();
$group->name = 'AssetManager';
$group->description = 'Members of this group can add/edit/delete website asset-files';
$group->active = 1;
$group->Save();
$group->GrantPermission('Modify Site Assets');
*/

// remove reference from plugins-arugument where necessary
foreach ([
 ['lib', 'plugins'],
 ['admin', 'plugins'],
 ['assets', 'plugins'],
 ['plugins'], //deprecated
] as $segs) {
    $path = joinpath($destdir, ...$segs);
    if (is_dir($path)) {
        $files = scandir($path, SCANDIR_SORT_NONE);
        if ($files) {
            foreach ($files as $one) {
                if (endswith($one, '.php')) {
                    $fp = $path.DIRECTORY_SEPARATOR.$one;
                    $content = file_get_contents($fp);
                    if ($content) {
                        $parts = explode('.',$one);
                        $patn = '/function\\s+smarty(_cms)?_'.$parts[0].'_'.$parts[1].'\\s?\\([^,]+,[^,]*(&\\s?)(\\$\\S+)\\s?\\)\\s?[\r\n]/';
                        if (preg_match($patn, $content, $matches)) {
                            $content = str_replace($matches[2].$matches[3], $matches[3], $content);
                            file_put_contents($fp, $content);
                        }
                    }
               }
            }
        }
    }
}

// redundant sequence-tables
$db->DropSequence(CMS_DB_PREFIX.'content_props_seq');
$db->DropSequence(CMS_DB_PREFIX.'userplugins_seq');

$dbdict = GetDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

// tables
$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.CmsLayoutCollection::TPLTABLE,'tpl_order I(4) DEFAULT 0');
$dbdict->ExecuteSQLArray($sqlarray);

$flds = '
category_id I NOTNULL,
tpl_id I NOTNULL,
tpl_order I(4) DEFAULT 0
';
$sqlarray = $dbdict->CreateTableSQL(
    CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE,
    $flds,
    $taboptarray
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
verbose_msg(ilang('install_created_table', CmsLayoutTemplateCategory::TPLTABLE, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL('idx_layout_cat_tplasoc_1',
 CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE, 'tpl_id');
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
verbose_msg(ilang('install_creating_index', 'idx_layout_cat_tplasoc_1', $msg_ret));

// migrate existing category_id values to new table
$sql = 'SELECT id,category_id FROM '.CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME.' WHERE category_id IS NOT NULL';
$data = $db->GetArray($sql);
if ($data) {
    $sql = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE.' (category_id,tpl_id,tpl_order) VALUES (?,?,-1)';
    foreach ($data as $row) {
        $db->Execute($sql, [$row['category_id'], $row['id']]);
    }
}

$sqlarray = $dbdict->DropColumnSQL(CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,'category_id');
$dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,'originator C(32)');
$dbdict->ExecuteSQLArray($sqlarray);

// layout-templates table indices
// replace this 'unique' by non- (_3 below becomes the validator)
$sqlarray = $dbdict->DropIndexSQL(CMS_DB_PREFIX.'idx_layout_tpl_1',
    CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, 'name');
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_1',
    CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, 'name');
$dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL('idx_layout_tpl_3',
    CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME, 'originator,name', ['UNIQUE']);
$dbdict->ExecuteSQLArray($sqlarray);

// migrate module templates to layout-templates table
$sql = 'SELECT * FROM '.CMS_DB_PREFIX.'module_templates ORDER BY module_name,template_name';
$data = $db->GetArray($sql);
if ($data) {
    $sql = 'INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME.
        ' (originator,name,content,type_id,created,modified) VALUES (?,?,?,?,?,?)';
    $dt = new DateTime(null, new DateTimeZone('UTC'));
    $now = time();
    $types = [];
    foreach ($data as $row) {
        $name = $row['module_name'];
        if (!isset($types[$name])) {
            $db->Execute('INSERT INTO '.CMS_DB_PREFIX.CmsLayoutTemplateType::TABLENAME.
            ' (originator,name,description,owner,created,modified) VALUES (?,?,?,-1,?,?)',
            [
                $name,
                'Moduleaction',
                'Action templates for module: '.$name,
                $now,
                $now,
            ]);
            $types[$name] = $db->insert_id();
        }
        $dt->modify($row['create_date']);
        $created = $dt->getTimestamp();
        if (!$created) { $created = $now; }
        $dt->modify($row['modified_date']);
        $modified = $dt->getTimestamp();
        if (!$modified) { $modified = min($now, $created); }
        $db->Execute($sql, [
            $name,
            $row['template_name'],
            $row['content'],
            $types[$name],
            $created,
            $modified
        ]);
    }
    verbose_msg(ilang('upgrade_modifytable', 'module_templates'));
}

$sqlarray = $dbdict->DropTableSQL(CMS_DB_PREFIX.'module_templates');
$dbdict->ExecuteSQLArray($sqlarray);
verbose_msg(ilang('upgrade_deletetable', 'module_templates'));

// migrate everyone to new default theme
$query = 'UPDATE '.CMS_DB_PREFIX.'userprefs SET value=\'Altbier\' WHERE preference=\'admintheme\'';
$db->Execute($query);

//if ($return == 2) {
  $query = 'INSERT INTO '.CMS_DB_PREFIX.'version VALUES (205)';
  $db->Execute($query);
//}
