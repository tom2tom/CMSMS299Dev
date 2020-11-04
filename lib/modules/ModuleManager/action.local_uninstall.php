<?php

use CMSMS\ModuleOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');

if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab();
}

try {
    $mod = get_parameter_value($params,'mod');
    if( !$mod ) {
        $this->SetError($this->Lang('error_missingparam'));
        $this->RedirectToAdminTab();
    }

    $ops = ModuleOperations::get_instance();
    $modinstance = $ops->get_module_instance($mod,'',TRUE);
    if( !is_object($modinstance) ) {
        // uh-oh
        $this->SetError($this->Lang('error_getmodule',$mod));
        $this->RedirectToAdminTab();
    }

    if( isset($params['submit']) ) {
        try {
            if( !isset($params['confirm']) || $params['confirm'] != 1 ) throw new RuntimeException($this->Lang('error_notconfirmed'));
            $postmsg = $modinstance->UninstallPostMessage();
            if( $postmsg == '' ) $postmsg = $this->Lang('msg_module_uninstalled',$mod);
            $result = $ops->UninstallModule($mod);
            if( $result[0] == FALSE ) throw new RuntimeException($result[1]);
            $this->SetMessage($postmsg);
            $this->RedirectToAdminTab();
        }
        catch( Exception $e ) {
            $this->ShowErrors($e->GetMessage());
        }
    }

    $tpl = $smarty->createTemplate($this->GetTemplateResource('local_uninstall.tpl')); //,null,null,$smarty);
    $tpl //see DoActionBase()->assign('mod',$this)
//     ->assign('actionid',$id)
     ->assign('module_name',$modinstance->GetName())
     ->assign('module_version',$modinstance->GetVersion());
    $msg = $modinstance->UninstallPreMessage();
    if( !$msg ) $msg = $this->Lang('msg_module_uninstall');
    $tpl->assign('msg',$msg);
    $tpl->display();
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage());
}
return '';
