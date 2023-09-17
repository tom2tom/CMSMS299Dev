<?php

use function cms_installer\status_msg;
use function cms_installer\verbose_msg;

$gCms = cmsms();
$dbdict = $db->NewDataDictionary();
/*
$str = $db->server_info;
if (stripos($str, 'Maria') === false) {
    $tblengn = 'MyISAM';
} else {
    $tblengn = 'Aria';
}
$taboptarray = ['mysqli' => "ENGINE=$tblengn"];
*/
status_msg('performing database changes for CMSMS 2.1.2');
verbose_msg('database schema has not changed');

$sqlarray = $dbdict->AlterColumnSQL(CMS_DB_PREFIX.'content_props', 'content X2');
$return = $dbdict->ExecuteSQLArray($sqlarray);

verbose_msg('ensuring that database schema is set to 201');
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 201';
$db->execute($query);
