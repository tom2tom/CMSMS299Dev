<?php
# installation-process  for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# See license details at the top of file CmsJobManager.module.php

if( !isset($gCms) ) exit;

$this->CreatePermission(\CmsJobManager::MANAGE_JOBS,\CmsJobManager::MANAGE_JOBS);
$this->CreateEvent(\CmsJobManager::EVT_ONFAILEDJOB);
$this->AddEventHandler('Core','ModuleUninstalled',FALSE);

$taboptarray = array('mysqli' => 'ENGINE=MyISAM CHARACTER SET utf8 COLLATE utf8_general_ci'); //TODO InnoDB relevant?
$dict = NewDataDictionary($db);

$flds = "
id I KEY AUTO NOTNULL,
name C(255) NOTNULL,
created I NOTNULL,
module C(255) NOTNULL,
errors I NOTNULL DEFAULT 0,
start I NOTNULL,
recurs C(255),
until I,
data X2
";
$sqlarray = $dict->CreateTableSQL( CmsJobManager::table_name(), $flds, $taboptarray );
$dict->ExecuteSQLArray($sqlarray);
