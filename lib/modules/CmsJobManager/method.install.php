<?php
# installation-process for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# See license details at the top of file CmsJobManager.module.php

if( !isset($gCms) ) exit;

$this->SetPreference('enabled',1); //whether async job-processing by this module is currently enabled
$this->SetPreference('jobinterval',5); //minutes between updates 1 .. 10
$this->SetPreference('jobtimeout',30); //seconds, max jobs execution-time 30 .. 1800
$this->SetPreference('joburl',''); //custom url for job processing
$this->SetPreference('last_check',0); //timestamp for internal use only
$this->SetPreference('last_processing',0); //ditto

$this->CreatePermission(\CmsJobManager::MANAGE_JOBS,\CmsJobManager::MANAGE_JOBS);

$this->refresh_jobs(); //init jobs-data

$this->CreateEvent(\CmsJobManager::EVT_ONFAILEDJOB);
$this->AddEventHandler('Core','ModuleInstalled',false);
$this->AddEventHandler('Core','ModuleUninstalled',false);

//TODO check InnoDB relevant here?
$taboptarray = array('mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8 COLLATE utf8_general_ci');
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
