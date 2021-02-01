<?php
/*
This file is part of CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

if (!isset($gCms)) {
    exit;
}

/*
$taboptarray = ['mysqli' => 'ENGINE=MYISAM CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci'];
$dict = NewDataDictionary($db);

$flds = '
id I AUTO KEY,
name C(160) NOTNULL,
version C(32),
author C(96),
notes X(16383)
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX . 'module_themes', $flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);

$flds = '
id I AUTO KEY,
theme_id I NOTNULL,
name C(160) NOTNULL,
type C(64) NOTNULL,
module C(64),
location X(511)
';
$sqlarray = $dict->CreateTableSQL(CMS_DB_PREFIX.'module_themes_comp',
$flds, $taboptarray);
$dict->ExecuteSQLArray($sqlarray);
*/
// permissions
$this->CreatePermission('Manage Themes', 'Manage Themes');
$this->CreatePermission('Manage Theme Contents', 'Manage Theme Contents');

// put mention into the admin log
audit($this->Lang('friendlyname'), $this->Lang('installed', $this->GetVersion()));
