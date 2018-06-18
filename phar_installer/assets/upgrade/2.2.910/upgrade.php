<?php

$dbdict = GetDataDictionary($db);
$taboptarray = array('mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci');

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
verbose_msg(ilang('install_created_table', CmsLayoutTemplateCategory::TPLTABLE, $msg_ret));

$sqlarray = $dbdict->CreateIndexSQL(
 CMS_DB_PREFIX.'idx_layout_cat_tplasoc_1',
 CMS_DB_PREFIX.CmsLayoutTemplateCategory::TPLTABLE,
 'tpl_id'
);
$return = $dbdict->ExecuteSQLArray($sqlarray);
$msg_ret = ($return == 2) ? ilang('done') : ilang('failed');
verbose_msg(ilang('install_creating_index', 'idx_layout_cat_tplasoc_1', $msg_ret));

if ($return == 2) {
    $query = 'INSERT INTO '.CMS_DB_PREFIX.'version VALUES (205)';
    $db->Execute($query);
}
