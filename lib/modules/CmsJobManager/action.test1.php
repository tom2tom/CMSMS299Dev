<?php
#test action for CmsJobManager, a module for CMS Made Simple
#Copyright (C) 2016-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
#See license details at the top of file CmsJobManager.module.php

use CMSMS\App;

if (!isset($gCms) || !($gCms instanceof App)) {
    exit;
}
if (!$this->VisibleToAdminUser()) {
    return '';
}

$newjob = new Test1Job();
$newjob->save();

$this->SetMessage('Job Created');
$this->RedirectToAdminTab();
