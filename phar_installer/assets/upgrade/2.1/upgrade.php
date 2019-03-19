<?php

use function cms_installer\lang;

status_msg('Upgrading schema for CMSMS 2.1');

//$gCms = cmsms();
$dbdict = GetDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.LayoutTemplateOperations::TABLENAME,'listable I1 DEFAULT 1');
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg(lang('upgrading_schema',201));
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 201';
$db->Execute($query);
