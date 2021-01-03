<?php

use CMSMS\ModuleOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
$module = $params['mod'] ?? '';

if( $module ) {
    $modinstance = ModuleOperations::get_instance()->get_module_instance($module, '', TRUE);
}
else {
    $module = 'Not Specified';
    $modinstance = null;
}
if( !is_object($modinstance) ) {
    $this->SetError($this->Lang('error_getmodule', $module));
    $this->RedirectToAdminTab();
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('local_about.tpl')); //,null,null,$smarty);

$tpl->assign([
    'module_name' => $module,
    'back_url' => $this->create_url($id, 'defaultadmin', $returnid),
    'about_page' => $modinstance->GetAbout(),
    'about_title' => $this->Lang('about_title', $modinstance->GetName()),
]);

$tpl->display();
return '';
