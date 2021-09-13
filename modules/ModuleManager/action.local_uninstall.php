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

use CMSMS\SingleItem;

//if( some worthy test fails ) exit;
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

    $ops = SingleItem::ModuleOperations();
    $mod = $ops->get_module_instance($modname, '', true);
    if( !is_object($mod) ) {
        // uh-oh
        $this->SetError($this->Lang('error_getmodule', $modname));
        $this->RedirectToAdminTab();
    }

    if( isset($params['submit']) ) {
        try {
            if( !isset($params['confirm']) || $params['confirm'] != 1 ) throw new RuntimeException($this->Lang('error_notconfirmed'));
            $postmsg = $mod->UninstallPostMessage();
            if( $postmsg == '' ) $postmsg = $this->Lang('msg_module_uninstalled', $modname);
            $result = $ops->UninstallModule($modname);
            if( $result[0] == FALSE ) throw new RuntimeException($result[1]);
            $this->SetMessage($postmsg);
            $this->RedirectToAdminTab();
        }
        catch( Throwable $t ) {
            $this->ShowErrors($t->GetMessage());
        }
    }

    $tpl = $smarty->createTemplate($this->GetTemplateResource('uninstall.tpl')); //, null, null, $smarty);
    $tpl //see DoActionBase()->assign('mod', $this)
//     ->assign('actionid', $id)
     ->assign('module_name', $mod->GetName())
     ->assign('module_version', $mod->GetVersion());
    $msg = $mod->UninstallPreMessage();
    if( !$msg ) $msg = $this->Lang('msg_module_uninstall');
    $tpl->assign('msg', $msg);
    $tpl->display();
}
catch( Throwable $t ) {
    $this->SetError($t->GetMessage());
    $this->RedirectToAdminTab();
}
