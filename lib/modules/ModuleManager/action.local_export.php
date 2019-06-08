<?php

use CMSMS\Events;
use CMSMS\ModuleOperations;
use CMSMS\NlsOperations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) return;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
$module = get_parameter_value($params,'mod');

try {
    $modinstance = ModuleOperations::get_instance()->get_module_instance($module,'',TRUE);
    if( !is_object($modinstance) ) {
        $this->SetError($this->Lang('error_getmodule',$module));
        $this->RedirectToAdminTab();
    }

    $old_display_errors = ini_set('display_errors',0);
    $orig_lang = NlsOperations::get_current_language();
    NlsOperations::set_language('en_US');
    $files = 0;
    $message = '';

    Events::SendEvent( 'ModuleManager', 'BeforeModuleExport', [ 'module_name' => $module, 'version' => $modinstance->GetVersion() ] );
    $xmlfile = $this->get_operations()->create_xml_package($modinstance,$message,$files);
    Events::SendEvent( 'ModuleManager', 'AfterModuleExport', [ 'module_name' => $module, 'version' => $modinstance->GetVersion() ] );
    NlsOperations::set_language($orig_lang);
    if( $old_display_errors !== FALSE ) ini_set('display_errors',$old_display_errors);

    if( !$files ) {
        $this->SetError('error_moduleexport');
        $this->RedirectToAdminTab();
    }
    else {
        $xmlname = $modinstance->GetName().'-'.$modinstance->GetVersion().'.xml';
        audit('',$this->GetName(),'Exported '.$modinstance->GetName().' to '.$xmlname);

        // send the file.
        $handlers = ob_list_handlers();
        for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }
        header('Content-Description: File Transfer');
        header('Content-Type: application/force-download');
        header('Content-Disposition: attachment; filename='.$xmlname);
        echo file_get_contents($xmlfile);
		unlink($xmlfile);
        exit;
    }
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage());
    $this->RedirectToAdminTab();
}
