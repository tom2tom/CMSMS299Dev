<?php
/*
This file is part of CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

if (!isset($gCms)) {
    exit;
}

$current_version = $oldversion;
switch ($current_version) {
    case '0.1':
        // no break here
    case '0.1.1':
        // no break here
    case '0.2':
        // no break here
    case '0.3':
        // no break here
    case '0.4':
        $this->CreatePermission('Manage Themes', 'Manage Themes');
        $this->CreatePermission('Manage Theme Contents', 'Manage Theme Contents');
        break;
}

// put mention into the admin log
audit($this->Lang('friendlyname'), $this->Lang('upgraded', $this->GetVersion()));
