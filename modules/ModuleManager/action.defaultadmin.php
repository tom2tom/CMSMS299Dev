<?php
/*
action: defaultadmin
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

use ModuleManager\Utils;

//if( some worthy test fails ) exit;

if( isset($params['modulehelp']) ) {
    // this is done before permissions checks
    $params['mod'] = $params['modulehelp'];
    unset($params['modulehelp']);
    return require __DIR__.DIRECTORY_SEPARATOR.'action.local_help.php';
}

if( !$this->VisibleToAdminUser() ) exit;

$pmod = $this->CheckPermission('Modify Modules');
$pset = $this->CheckPermission('Modify Site Preferences');
if( !($pmod || $pset) ) exit;

$connection_ok = Utils::is_connection_ok();
if( !$connection_ok ) {
    $this->ShowErrors($this->Lang('error_request_problem'));
}

$seetab = $params['active_tab'] ?? 'installed';

$tpl = $smarty->createTemplate($this->GetTemplateResource('defaultadmin.tpl')); //,null,null,$smarty);

$tpl->assign('tab',$seetab)
 ->assign('pmod',$pmod)
 ->assign('pset',$pset)
 ->assign('connected',$connection_ok);

if( $pmod ) {
    Utils::get_images($tpl);
    require __DIR__.DIRECTORY_SEPARATOR.'function.installedtab.php';
    if( $connection_ok ) {
        // data for inclusions
        $dirlist = cms_module_places();
        $writelist = [];
        foreach( $dirlist as $i => $dir ) {
            $writelist[$i] = is_writable($dir);
        }
        require __DIR__.DIRECTORY_SEPARATOR.'function.newversionstab.php';
        require __DIR__.DIRECTORY_SEPARATOR.'function.searchtab.php';
        require __DIR__.DIRECTORY_SEPARATOR.'function.forgetab.php';
    }
}
if( $pset ) {
    require __DIR__.DIRECTORY_SEPARATOR.'function.prefstab.php';
}

$tpl->display();
