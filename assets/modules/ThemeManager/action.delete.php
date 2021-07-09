<?php
/*
Delete-theme action for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

use CMSMS\App;
use function CMSMS\sanitizeVal;

if (!isset($gCms) || !($gCms instanceof App)) {
    exit;
}

$pmod = $this->CheckPermission('Modify Themes');
if (!$pmod) {
    $this->SetError($this->Lang('nopermission'));
    $this->RedirectToAdminTab('themes');
}

$path = cms_join_path(CMS_THEMES_PATH, sanitizeVal($params['theme'], CMSSAN_FILE)); // OR , CMSSAN_PATH for a sub-theme?
if (is_dir($path)) {
    $current_theme = $this->GetPreference('current_theme');
    if ($current_theme) {
        $props = parse_ini_file($path . DIRECTORY_SEPARATOR . 'Theme.cfg');
        $theme = $props['name'];
        if ($current_theme == $theme) {
        // consequential changes
        }
    }
    if (!recursive_delete($path)) {
        $this->SetError($this->Lang('error'));
    }
} else {
    $this->SetError($this->Lang('err_themename'));
}

$this->RedirectToAdminTab('themes');
