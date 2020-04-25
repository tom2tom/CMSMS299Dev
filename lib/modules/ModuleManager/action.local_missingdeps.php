<?php

use ModuleManager\ModuleInfo;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
$module = get_parameter_value($params,'mod');

$info = ModuleInfo::get_module_info($module);

$tpl = $smarty->createTemplate($this->GetTemplateResource('local_missingdeps.tpl'),null,null,$smarty);

$tpl->assign('back_url',$this->create_url($id,'defaultadmin',$returnid))
 ->assign('info',$info);

$tpl->display();
return '';
