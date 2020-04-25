<?php

use CMSMS\ModuleOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');

$mod = get_parameter_value($params,'mod');
if( !$mod ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}

$result = ModuleOperations::get_instance()->UpgradeModule($mod);
if( !is_array($result) || !isset($result[0]) ) $result = [FALSE,$this->Lang('error_moduleupgradefailed')];

if( $result[0] == FALSE ) {
  $this->SetError($result[1]);
  $this->RedirectToAdminTab();
}

$this->SetMessage($this->Lang('msg_module_upgraded',$mod));
$this->RedirectToAdminTab();
