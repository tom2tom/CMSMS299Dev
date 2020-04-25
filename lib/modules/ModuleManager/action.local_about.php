<?php

use CMSMS\ModuleOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
  $this->SetError($this->Lang('error_missingparam'));
  $this->RedirectToAdminTab();
}
$module = get_parameter_value($params,'mod');

$modinstance = ModuleOperations::get_instance()->get_module_instance($module,'',TRUE);
if( !is_object($modinstance) ) {
  $this->SetError($this->Lang('error_getmodule',$module));
  $this->RedirectToAdminTab();
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('local_about.tpl'),null,null,$smarty);

$tpl->assign('module_name',$module)
 ->assign('back_url',$this->create_url($id,'defaultadmin',$returnid))
 ->assign('about_page',$modinstance->GetAbout())
 ->assign('about_title',$this->Lang('about_title',$modinstance->GetName()));

$tpl->display();
return '';
