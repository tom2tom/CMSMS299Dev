<?php

use CMSMS\ModuleOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
if( isset($params['state']) ) $state = (bool)$params['state'];
else $state = false;
$module = trim($params['mod'] ?? '');

if( $module) {
  $res = ModuleOperations::get_instance()->ActivateModule($module, $state);
}
else {
  $res = false;
}
if( !$res ) {
    $this->SetError($this->Lang('error_active_failed'));
    $this->RedirectToAdminTab();
}

if( $state ) {
  $this->SetMessage($this->Lang('msg_module_activated',$module));
}
else {
  $this->SetMessage($this->Lang('msg_module_deactivated',$module));
}
$this->RedirectToAdminTab();
