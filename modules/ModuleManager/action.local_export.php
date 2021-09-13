<?php
/*
Module Manager action: export module
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
use CMSMS\NlsOperations;
use CMSMS\SingleItem;
use function CMSMS\sanitizeVal;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;

$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}

$modname = sanitizeVal($params['mod'], CMSSAN_FILE);
try {
    if( $modname ) {
        $mod = SingleItem::ModuleOperations()->get_module_instance($modname, '', true);
    }
    else {
        $modname = 'Not Specified'; // not translated - export could go to anywhere
        $mod = null;
    }
    if( !is_object($mod) ) {
        $this->SetError($this->Lang('error_getmodule', $modname));
        $this->RedirectToAdminTab();
    }

    $old_display_errors = ini_set('display_errors',0);
    $orig_lang = NlsOperations::get_current_language();
    NlsOperations::set_language('en_US');
    $files = 0;
    $message = '';

    Events::SendEvent( 'ModuleManager', 'BeforeModuleExport', [ 'module_name' => $modname, 'version' => $mod->GetVersion() ] );
    $xmlfile = $this->get_operations()->create_xml_package($mod,$message,$files);
    Events::SendEvent( 'ModuleManager', 'AfterModuleExport', [ 'module_name' => $modname, 'version' => $mod->GetVersion() ] );
    NlsOperations::set_language($orig_lang);
    if( $old_display_errors !== FALSE ) ini_set('display_errors',$old_display_errors);

    if( !$files ) {
        $this->SetError('error_moduleexport');
        $this->RedirectToAdminTab();
    }
    else {
        $xmlname = $mod->GetName().'-'.$mod->GetVersion().'.xml';
        audit('',$this->GetName().'::local_export','Exported '.$mod->GetName().' to '.$xmlname);

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
catch( Throwable $t ) {
    $this->SetError($t->GetMessage());
    $this->RedirectToAdminTab();
}
