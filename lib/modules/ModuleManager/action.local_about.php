<?php
/*
Module Manager action: display module changelog
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
use function CMSMS\sanitizeVal;

if( !isset($gCms) ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
$modname = sanitizeVal($params['mod'], CMSSAN_FILE);

if( $modname ) {
    $modinst = AppSingle::ModuleOperations()->get_module_instance($modname, '', TRUE);
}
else {
    $modname = lang('notspecified');
    $modinst = null;
}
if( !is_object($modinst) ) {
    $this->SetError($this->Lang('error_getmodule', $modname));
    $this->RedirectToAdminTab();
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('local_about.tpl')); //,null,null,$smarty);

$tpl->assign([
    'module_name' => $modname,
    'back_url' => $this->create_url($id, 'defaultadmin', $returnid),
    'about_page' => $modinst->GetAbout(),
    'about_title' => $this->Lang('about_title', $modinst->GetName()),
]);

$tpl->display();
return '';
