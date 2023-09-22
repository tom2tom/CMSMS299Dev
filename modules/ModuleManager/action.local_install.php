<?php
/*
Module Manager action: install a module
Copyright (C) 2008-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of ModuleManager, an addon module for
CMS Made Simple to allow browsing remotely stored modules, viewing
information about them, and downloading or upgrading

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Lone;
use function CMSMS\log_notice;
use function CMSMS\sanitizeVal;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;

$this->SetCurrentTab('installed');

$modname = sanitizeVal($params['mod'], CMSSAN_FILE); //module-identifier == foldername and in file-name
if( !$modname ) {
    $this->SetError($this->Lang('error_missingparams'));
    $this->RedirectToAdminTab();
}

$ops = Lone::get('ModuleOperations');
$result = $ops->InstallModule($modname);
if( !is_array($result) || !isset($result[0]) ) $result = [false, $this->Lang('error_moduleinstallfailed')];

if( $result[0] == FALSE ) {
    $this->SetError($result[1]);
    $this->RedirectToAdminTab();
}

$mod = $ops->get_module_instance($modname, '', true);
if( !is_object($mod) ) {
    // uh-oh...
    $this->SetError($this->Lang('error_getmodule', $modname));
    $this->RedirectToAdminTab();
}

log_notice($this->GetName().'::local_install','Installed '.$modname.' '.$mod->GetVersion());
$msg = $mod->InstallPostMessage();
if( !$msg ) $msg = $this->Lang('msg_module_installed', $modname);
$this->SetMessage($msg);
$this->RedirectToAdminTab();