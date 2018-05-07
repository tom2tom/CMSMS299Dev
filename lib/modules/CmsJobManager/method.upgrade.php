<?php
# upgrade-process  for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# See license details at the top of file CmsJobManager.module.php

if( !isset($gCms) ) exit;

if( version_compare($oldversion,'0.3') < 0 ) {
    $this->SetPreference('enabled',1);
    $this->SetPreference('jobinterval',$config['cmsjobmgr_asyncfreq'] ?? 5);
    $this->SetPreference('jobtimeout',$config['cmsjobmanager_timelimit'] ?? 30);
    $this->SetPreference('joburl','');
}
