<?php
/*
Module Manager action: import a module
Copyright (C) 2008-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of ModuleManager, an addon module for
CMS Made Simple to allow browsing remotely stored modules, viewing
information about them, and downloading or upgrading

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Events;
use ModuleManager\Operations;

//if( some worthy test fails ) exit;
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

$ops = new Operations($this);

try {
    Events::SendEvent( 'ModuleManager', 'BeforeModuleImport', [ 'file'=>$file['name']] );
    $ops->expand_xml_package( $file['tmp_name'], true, false );
    Events::SendEvent( 'ModuleManager', 'AfterModuleImport', [ 'file'=>$file['name']] );

    audit('',$this->GetName().'::local_import','Imported module from '.$file['name']);
    $this->Setmessage($this->Lang('msg_module_imported'));
}
catch( Throwable $t ) {
    cms_error('',$this->GetName().'::local_import','Module import failed: '.$file['name'].', '.$e->GetMessage());
    $this->SetError($t->GetMessage());
}

$this->RedirectToAdminTab();
