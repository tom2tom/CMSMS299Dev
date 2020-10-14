<?php
# installation-process for CMS Made Simple module: CmsJobManager
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# See license details at the top of file CmsJobManager.module.php

use CMSMS\AppParams;
use CMSMS\Database\DataDictionary;

if( !isset($gCms) ) exit;

//table is essentially a cache, written as much as read, use InnoDB table
$taboptarray = ['mysqli' => 'CHARACTER SET utf8 COLLATE utf8_general_ci'];
$dict = new DataDictionary($db);

//data field holds a serialized class, size 1024 is probably enough
//TODO consider datetime fields instead of some of the current timestamps
$flds = '
id I UNSIGNED KEY AUTO NOT NULL,
name C(255) NOT NULL,
module C(128),
created I NOT NULL,
start I UNSIGNED NOT NULL,
until I UNSIGNED,
recurs I(4) UNSIGNED,
errors I(4) UNSIGNED DEFAULT 0 NOT NULL,
data X(16383)
';
$sqlarray = $dict->CreateTableSQL(CmsJobManager::TABLE_NAME, $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

AppParams::set('jobinterval',180); //seconds, min-gap between job-processing's 1 .. 600
AppParams::set('jobtimeout',30); //seconds, max jobs execution-time 30 .. 1800

$this->SetPreference('enabled',1); //whether async job-processing by this module is currently enabled
$this->SetPreference('joburl',''); //custom url for job processing
$this->SetPreference('last_check',0); //timestamp for internal use only
$this->SetPreference('last_processing',0); //ditto

$this->CreatePermission(CmsJobManager::MANAGE_JOBS, $this->Lang('perm_Manage_Jobs'));

$this->refresh_jobs(); //init jobs-data

$this->CreateEvent(CmsJobManager::EVT_ONFAILEDJOB);
$this->AddEventHandler('Core','ModuleInstalled',false);
$this->AddEventHandler('Core','ModuleUninstalled',false);
$this->AddEventHandler('Core','ModuleUpgraded',false);
