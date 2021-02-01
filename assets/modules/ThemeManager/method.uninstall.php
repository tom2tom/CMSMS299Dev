<?php
/*
This file is part of CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

if (!isset($gCms)) {
    exit;
}
/*
// remove database tables
$dict = NewDataDictionary($db);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_themes');
$dict->ExecuteSQLArray($sqlarray);
$sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX.'module_themes_comp');
$dict->ExecuteSQLArray($sqlarray);
*/
// remove permissions
$this->RemovePermission();

// put mention into the admin log
audit($this->Lang('friendlyname'), $this->Lang('uninstalled'));
