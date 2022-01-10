<?php
if (empty($this) || !($this instanceof FileManager)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$current_version = $oldversion;
$this->SetPreference('uploadboxes','5');
switch($current_version) {
    case '0.1.0':
    case '0.1.1':
    case '0.1.2':
    case '0.1.3':
    case '0.1.4': $this->Install(true);
}

if( version_compare($oldversion,'1.3.1') < 0 ) {
    $this->CreateEvent('OnFileUploaded');
}
if( version_compare($oldversion,'1.6.2') < 0 ) {
    $this->CreateEvent('OnFileDeleted');
}

// do this stuff for all upgrades
$this->SetPreference('advancedmode',0);
$this->RemovePermission('Use Filemanager');
$this->RegisterModulePlugin(true);
$this->RemovePreference('uploadboxes');
