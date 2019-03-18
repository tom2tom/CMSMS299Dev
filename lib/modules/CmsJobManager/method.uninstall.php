<?php
# uninstallation-process  for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# See license details at the top of file CmsJobManager.module.php

use CMSMS\Database\DataDictionary;

if( !isset($gCms) ) exit;

$dict = new DataDictionary($db);
$sqlarray = $dict->DropTableSQL(CmsJobManager::TABLE_NAME);
$dict->ExecuteSQLArray($sqlarray);

$this->RemovePreference();
$this->RemovePermission(CmsJobManager::MANAGE_JOBS);
$this->RemoveEvent(CmsJobManager::EVT_ONFAILEDJOB);
$this->RemoveEventHandler('Core','ModuleUninstalled');
