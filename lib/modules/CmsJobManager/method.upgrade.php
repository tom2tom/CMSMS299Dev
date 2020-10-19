<?php
/*
Upgrade-process for CMS Made Simple module: CmsJobManager
Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
See license details at the top of file CmsJobManager.module.php
*/
use CMSMS\AppParams;

if (empty($gCms)) exit;

if (version_compare($oldversion,'0.3') < 0) {
    $val = $config['cmsjobmgr_asyncfreq'];
    if ($val) { $val = min(max(1,(int)$val),10); }
    else { $val = 3; }
    AppParams::set('jobinterval',$val * 60); // now use seconds for all async timing
    $val = $config['cmsjobmanager_timelimit'];
    if ($val) { $val = min(max(20,(int)$val),120); }
    else { $val = 30; }
    AppParams::set('jobtimeout',$val);
    $this->SetPreference('enabled',1);
    $this->SetPreference('joburl','');
}
elseif (version_compare($oldversion,'0.4') < 0) {
    AppParams::set('jobinterval',$this->GetPreference('jobinterval',3) * 60);
    $this->RemovePreference('jobinterval');
    AppParams::set('jobtimeout',$this->GetPreference('jobtimeout',30));
    $this->RemovePreference('jobtimeout');

    $this->refresh_jobs(true); //re-init jobs-data
}
