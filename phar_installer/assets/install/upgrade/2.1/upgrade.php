<?php
status_msg('Upgrading schema for CMSMS 2.1');

//$gCms = cmsms();
$dbdict = GetDataDictionary($db);
$taboptarray = array('mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci');

$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.CmsLayoutTemplate::TABLENAME,'listable I1 DEFAULT 1');
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg(ilang('upgrading_schema',201));
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 201';
$db->Execute($query);
