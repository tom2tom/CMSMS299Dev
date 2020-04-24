<?php
# installation-process for CmsJobManager module.
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\Database\DataDictionary;

if( !isset($gCms) ) exit;

//table is essentially a cache, written as much as read, use InnoDB table
$taboptarray = ['mysqli' => 'CHARACTER SET utf8 COLLATE utf8_general_ci'];
$dict = new DataDictionary($db);

//data field holds a serialized class, size 1024 is probably enough
//TODO consider datetime fields instead of some of the current timestamps
$flds = '
id I KEY AUTO NOT NULL,
name C(255) NOT NULL,
module C(128),
created I NOT NULL,
start I NOT NULL,
until I,
recurs I(4) UNSIGNED,
errors I(4) UNSIGNED DEFAULT 0 NOT NULL,
data X(16383)
';
$sqlarray = $dict->CreateTableSQL(CmsJobManager::TABLE_NAME, $flds, $taboptarray);
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
