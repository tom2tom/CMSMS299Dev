<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
$module = $params['mod'];
if( $module ) {
  $dir = cms_module_path($module);
}
else {
  $dir = '';
}
if( $dir ) {
  $result = recursive_delete($dir);
}
else {
  $result = FALSE;
}

if( $result ) {
  audit('',$this->GetName(),'Module '.$module.' removed');
  $this->SetMessage($this->Lang('msg_module_removed'));
}
else {
  $this->SetError($this->Lang('error_moduleremovefailed'));
}

$this->RedirectToAdminTab();
