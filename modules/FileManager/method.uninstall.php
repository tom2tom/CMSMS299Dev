<?php

//use CMSMS\Database\DataDictionary;

if (empty($this) || !($this instanceof FileManager)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

// remove the database table
/*$dict = new DataDictionary($db);
 $sqlarray = $dict->DropTableSQL(CMS_DB_PREFIX."module_filemanager_thumbs");
 $dict->ExecuteSQLArray($sqlarray);
 */
// remove the sequence
//$db->DropSequence( CMS_DB_PREFIX."module_skeleton_seq" );

// remove the permissions
$this->RemovePermission('Use Filemanager'); //Used in some old versions
$this->RemovePermission('Use Filemanager Advanced');
$this->RemoveEvent('OnFileUploaded');
$this->RemoveEvent('OnFileDeleted');
$this->RemovePreference();
$this->RemoveSmartyPlugin();
