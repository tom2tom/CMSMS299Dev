<?php
/*
Un-installation-process for CMS Made Simple module: CmsJobManager
Copyright (C) 2016-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file CmsJobManager.module.php
*/
use CMSMS\Database\DataDictionary;

if (empty($gCms)) {
    exit;
}

$dict = new DataDictionary($db);
$sqlarray = $dict->DropTableSQL(CmsJobManager::TABLE_NAME);
$dict->ExecuteSQLArray($sqlarray);

$this->RemovePreference();
$this->RemovePermission(CmsJobManager::MANAGE_JOBS);
$this->RemoveEvent(CmsJobManager::EVT_ONFAILEDJOB);
$this->RemoveEventHandler('Core', 'ModuleUninstalled');
