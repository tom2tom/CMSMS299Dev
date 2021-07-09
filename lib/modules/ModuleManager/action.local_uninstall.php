<?php
/*
Module Manager action: uninstall a module
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

use CMSMS\AppSingle;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');

if( isset($params['cancel']) ) {
    $this->RedirectToAdminTab();
}

try {
    $modname = $params['mod'] ?? '';
    if( !$modname ) {
        $this->SetError($this->Lang('error_missingparam'));
        $this->RedirectToAdminTab();
    }

    $ops = AppSingle::ModuleOperations();
    $modinst = $ops->get_module_instance($modname,'',TRUE);
    if( !is_object($modinst) ) {
        // uh-oh
        $this->SetError($this->Lang('error_getmodule',$modname));
        $this->RedirectToAdminTab();
    }

    if( isset($params['submit']) ) {
        try {
            if( !isset($params['confirm']) || $params['confirm'] != 1 ) throw new RuntimeException($this->Lang('error_notconfirmed'));
            $postmsg = $modinst->UninstallPostMessage();
            if( $postmsg == '' ) $postmsg = $this->Lang('msg_module_uninstalled',$modname);
            $result = $ops->UninstallModule($modname);
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
     ->assign('module_name',$modinst->GetName())
     ->assign('module_version',$modinst->GetVersion());
    $msg = $modinst->UninstallPreMessage();
    if( !$msg ) $msg = $this->Lang('msg_module_uninstall');
    $tpl->assign('msg',$msg);
    $tpl->display();
}
catch( Exception $e ) {
    $this->SetError($e->GetMessage());
}
return '';
