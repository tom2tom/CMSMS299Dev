<?php

// access to CMSMS 3.0+ API is needed
use CMSMS\Template;

status_msg('Upgrading schema for CMSMS 2.1');

//$gCms = cmsms();
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
$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.Template::TABLENAME, 'listable I1 DEFAULT 1');
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg(ilang('upgrading_schema', 201));
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 201';
$db->execute($query);
