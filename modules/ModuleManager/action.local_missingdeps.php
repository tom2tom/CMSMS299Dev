<?php
/*
Module Manager action: display missing dependencies
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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use ModuleManager\ModuleInfo;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;

$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
$modname = $params['mod'];
if( $modname ) {
    $info = ModuleInfo::get_module_info($modname);
}
else {
    $info = null;
}
$url = $this->create_action_url($id,'defaultadmin');

$tpl = $smarty->createTemplate($this->GetTemplateResource('missingdeps.tpl')); //,null,null,$smarty);

$tpl->assign('back_url',$url)
 ->assign('info',$info);

$tpl->display();
