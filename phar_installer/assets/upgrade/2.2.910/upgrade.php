<?php

//extra permissions
foreach( [
 'Modify Simple Plugins',
 'Modify Site Code',
] as $one_perm ) {
  $permission = new CmsPermission();
  $permission->source = 'Core';
  $permission->name = $one_perm;
  $permission->text = $one_perm;
  $permission->save();
}

// redundant sequence-tables
$db->DropSequence(CMS_DB_PREFIX.'content_props_seq');
$db->DropSequence(CMS_DB_PREFIX.'userplugins_seq');

$dbdict = GetDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

// modify module-templates table
$tbl = CMS_DB_PREFIX.'module_templates';
$flds = '
id I KEY AUTO,
description X,
type_id I NOTNULL,
owner_id I NOTNULL DEFAULT -1,
type_dflt I(1) DEFAULT 0,
listable I(1) DEFAULT 1
';
$sqlarray = $dbdict->AddColumnSQL($tbl, $flds);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->AlterColumnSQL($tbl, 'content X2');
$return = $return && $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->RenameColumnSQL($tbl, 'module_name', 'module');
$return = $return && $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->RenameColumnSQL($tbl, 'template_name', 'name', 'C(100)');
$return = $return && $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->RenameColumnSQL($tbl, 'create_date', 'created', 'I');
$return = $return && $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->RenameColumnSQL($tbl, 'modified_date', 'modified', 'I');
$return = $return && $dbdict->ExecuteSQLArray($sqlarray);

$sqlarray = $dbdict->CreateIndexSQL('idx_module_templates_2', $tbl, 'name', ['UNIQUE']);
$return = $return && $dbdict->ExecuteSQLArray($sqlarray);
$sqlarray = $dbdict->CreateIndexSQL('idx_module_templates_3', $tbl, 'type_id,type_dflt');
$return = $return && $dbdict->ExecuteSQLArray($sqlarray);
//TODO $msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
//verbose_msg(ilang('install_modify_table', 'module_templates', $msg_ret));

// other tables
$sqlarray = $dbdict->DropColumnSQL(CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,'category_id');
$dbdict->ExecuteSQLArray($sqlarray);

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

$sqlarray = $dbdict->CreateIndexSQL(
 CMS_DB_PREFIX.'idx_layout_cat_tplasoc_1',
 CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE,
 'tpl_id'
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
verbose_msg(ilang('install_creating_index', 'idx_layout_cat_tplasoc_1', $msg_ret));

//if ($return == 2) {
  $query = 'INSERT INTO '.CMS_DB_PREFIX.'version VALUES (205)';
  $db->Execute($query);
//}
