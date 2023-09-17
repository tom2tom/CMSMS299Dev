<?php
/*
Module Manager action: toggle module active-state
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

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;
$this->SetCurrentTab('installed');
if( !isset($params['mod']) ) {
    $this->SetError($this->Lang('error_missingparam'));
    $this->RedirectToAdminTab();
}
if( isset($params['state']) ) $state = (bool)$params['state'];
else $state = false;
$module = trim($params['mod'] ?? '');

if( $module) {
    $res = Lone::get('ModuleOperations')->ActivateModule($module, $state);
}
else {
    $res = false;
}
if( !$res ) {
    $this->SetError($this->Lang('error_active_failed'));
    $this->RedirectToAdminTab();
}

if( $state ) {
    $this->SetMessage($this->Lang('msg_module_activated',$module));
}
else {
    $this->SetMessage($this->Lang('msg_module_deactivated',$module));
}
$this->RedirectToAdminTab();
