<?php
if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;

$this->SetCurrentTab('installed');
if( empty($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

$result = false;
$module = $params['mod'];
$dir = cms_module_path($module);
if( $dir ) {
    $result = recursive_chmod($dir); //default perms for dirs & others
}

if( $result ) {
    audit('',$this->GetName(),'Changed permissions on '.$module.' directory');
    $this->SetMessage($this->Lang('msg_module_chmod'));
} else {
    $this->SetError($this->Lang('error_chmodfailed'));
}

$this->RedirectToAdminTab();
