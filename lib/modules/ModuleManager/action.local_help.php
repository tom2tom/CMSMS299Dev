<?php

use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;

if( !isset($gCms) ) exit;
//if( !$this->CheckPermission('Modify Modules') ) return;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
$module = strip_tags(get_parameter_value($params,'mod'));
$lang = strip_tags(get_parameter_value($params,'lang'));

// get the module instance... force it to load if necessary.
$ops = ModuleOperations::get_instance();
$modinstance = $ops->get_module_instance($module,'',TRUE);
if( !is_object($modinstance) ) {
    $this->SetError($this->Lang('error_getmodule',$module));
    $this->RedirectToAdminTab();
}
$theme = cms_utils::get_theme_object();
$theme->SetTitle('module_help');

$our_lang = NlsOperations::get_current_language();

$tpl = $smarty->createTemplate($this->GetTemplateResource('local_help.tpl'),null,null,$smarty);
$tpl->assign('our_lang',$our_lang);

if( $our_lang != 'en_US' ) {
    if( $lang != '' ) {
        $tpl->assign('mylang_text',$this->Lang('display_in_mylanguage'))
         ->assign('mylang_url',$this->create_url($id,'local_help',$returnid,['mod'=>$module]));
        NlsOperations::set_language('en_US');
    }
    else {
        $yourlang_url = $this->create_url($id,'local_help',$returnid,['mod'=>$module,'lang'=>'en_US']);
        $tpl->assign('our_lang',$our_lang)
         ->assign('englang_url',$yourlang_url)
         ->assign('englang_text',$this->Lang('display_in_english'));
    }
}

$tpl->assign('module_name',$modinstance->GetName())
 ->assign('friendly_name',$modinstance->GetFriendlyName())
 ->assign('back_url',$this->create_url($id,'defaultadmin',$returnid))

 ->assign('help_page',$modinstance->GetHelpPage());
if( $our_lang != 'en_US' && $lang != '' ) {
    NlsOperations::set_language($our_lang);
}

$tpl->display();
