<?php
# upgrade-process  for CmsJobManager, a core module for CMS Made Simple
# to manage asynchronous jobs and cron jobs.
# Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# See license details at the top of file CmsJobManager.module.php

if( !isset($gCms) ) exit;

if( version_compare($oldversion,'0.3') < 0 ) {
    $this->SetPreference('enabled',1);
    $this->SetPreference('jobinterval',$config['cmsjobmgr_asyncfreq'] ?? 5);
    $this->SetPreference('jobtimeout',$config['cmsjobmanager_timelimit'] ?? 30);
    $this->SetPreference('joburl','');
}
