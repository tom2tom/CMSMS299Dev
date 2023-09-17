<?php
/*
Module Manager action: delete all the contents of a module
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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use function CMSMS\log_notice;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
$module = $params['mod'];
if( $module ) {
    $dir = cms_module_path($module, true);
}
else {
    $dir = '';
}
if( $dir ) {
    $result = recursive_delete($dir);
}
else {
    $result = FALSE;
}

if( $result ) {
    log_notice($this->GetName().'::local_remove','Module '.$module.' removed');
    $this->SetMessage($this->Lang('msg_module_removed'));
}
else {
    $this->SetError($this->Lang('error_moduleremovefailed'));
}

$this->RedirectToAdminTab();
