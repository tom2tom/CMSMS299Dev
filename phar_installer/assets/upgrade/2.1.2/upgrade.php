<?php
$gCms = cmsms();
$dbdict = GetDataDictionary($db);
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci'];

status_msg('performing database changes for CMSMS 2.1.2');
verbose_msg('database schema has not changed');

$sqlarray = $dbdict->AlterColumnSQL(CMS_DB_PREFIX.'content_props','content X2');
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('ensuring that database schema is set to 201');
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 201';
$db->Execute($query);
