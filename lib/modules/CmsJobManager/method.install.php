<?php
# installation-process for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# See license details at the top of file CmsJobManager.module.php

use CMSMS\Database\DataDictionary;

if( !isset($gCms) ) exit;

//table is essentially a cache, written as much as read, use InnoDB table
$taboptarray = ['mysqli' => 'CHARACTER SET utf8 COLLATE utf8_general_ci'];
$dict = new DataDictionary($db);

//data field holds a serialized class, size 1024 is probably enough
$flds = '
id I KEY AUTO NOTNULL,
name C(255) NOTNULL,
module C(128),
created I NOTNULL,
start I NOTNULL,
until I,
recurs I(4),
errors I(4) NOTNULL DEFAULT 0,
data X(16384)
';
$sqlarray = $dict->CreateTableSQL( CmsJobManager::TABLE_NAME, $flds, $taboptarray );
$dict->ExecuteSQLArray($sqlarray);

$this->SetPreference('enabled',1); //whether async job-processing by this module is currently enabled
$this->SetPreference('jobinterval',5); //minutes between updates 1 .. 10
$this->SetPreference('jobtimeout',30); //seconds, max jobs execution-time 30 .. 1800
$this->SetPreference('joburl',''); //custom url for job processing
$this->SetPreference('last_check',0); //timestamp for internal use only
$this->SetPreference('last_processing',0); //ditto

$this->CreatePermission(CmsJobManager::MANAGE_JOBS, $this->Lang('perm_Manage_Jobs'));

$this->refresh_jobs(); //init jobs-data

$this->CreateEvent(CmsJobManager::EVT_ONFAILEDJOB);
$this->AddEventHandler('Core','ModuleInstalled',false);
$this->AddEventHandler('Core','ModuleUninstalled',false);
$this->AddEventHandler('Core','ModuleUpgraded',false);
