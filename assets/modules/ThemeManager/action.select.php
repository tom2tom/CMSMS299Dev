<?php
/*
Select/apply theme action for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

use CMSMS\App;

if (!isset($gCms) || !($gCms instanceof App)) {
    exit;
}

$pmod = $this->CheckPermission('Modify Themes');
$pset = $this->CheckPermission('Modify Site Settings');
if (!($pmod || $pset)) {
    $this->SetError($this->Lang('nopermission'));
    $this->RedirectToAdminTab('themes');
}

$theme = $this->GetPreference('current_theme');
$this->SetPreference('current_theme', $params['theme']);

if ($theme != $params['theme']) {
    // consequential changes
    require __DIR__.DIRECTORY_SEPARATOR.'function.select.php';
}

$this->RedirectToAdminTab('themes');
