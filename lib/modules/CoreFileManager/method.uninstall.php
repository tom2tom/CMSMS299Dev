<?php

if (!function_exists("cmsms")) exit;

// remove the permissions
$this->RemovePermission('Use Filemanager'); //Used in some old versions
$this->RemovePermission('Use Filemanager Advanced');
$this->RemoveEvent('OnFileUploaded');
$this->RemoveEvent('OnFileDeleted');
$this->RemovePreference();
$this->RemoveSmartyPlugin();
