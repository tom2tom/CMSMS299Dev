<?php

use CMSMS\Events;
use ModuleManager\operations;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');

$key = $id.'upload';
if( !isset($_FILES[$key]) ) {
    $this->SetError($this->Lang('error_nofileuploaded'));
    $this->RedirectToAdminTab();
}
$file = $_FILES[$key];
if( $file['error'] || !$file['size'] || !isset($file['tmp_name']) || $file['tmp_name'] == '' ) {
    $this->SetError($this->Lang('error_fileupload'));
    $this->RedirectToAdminTab();
}
if( $file['type'] != 'text/xml' ) {
    $this->SetError($this->Lang('error_notxmlfile'));
    $this->RedirectToAdminTab();
}

$ops = new operations($this);

try {
    Events::SendEvent( 'ModuleManager', 'BeforeModuleImport', [ 'file'=>$file['name']] );
    $ops->expand_xml_package( $file['tmp_name'], true, false );
    Events::SendEvent( 'ModuleManager', 'AfterModuleImport', [ 'file'=>$file['name']] );

    audit('',$this->GetName(),'Imported module from '.$file['name']);
    $this->Setmessage($this->Lang('msg_module_imported'));
}
catch( Exception $e ) {
    audit('',$this->GetName(),'Module import failed: '.$file['name'].', '.$e->GetMessage());
    $this->SetError($e->GetMessage());
}

$this->RedirectToAdminTab();
