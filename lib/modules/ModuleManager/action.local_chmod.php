<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
$module = get_parameter_value($params,'mod');

$dir = cms_module_path($module);
if( $dir ) {
  $result = chmod_r($dir,0777); //TODO BAD PERM FOR NON-DIRS
} else {
  $result = false;
}

if( $result ) {
  audit('',$this->GetName(),'Changed permissions on '.$module.' directory');
  $this->SetMessage($this->Lang('msg_module_chmod'));
} else {
  $this->SetError($this->Lang('error_chmodfailed'));
}

$this->RedirectToAdminTab();
