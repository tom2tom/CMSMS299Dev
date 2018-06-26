<?php
# action: defaultadmin
# Copyright (C) 2008-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# This file is a component of ModuleManager, an addon module for
# CMS Made Simple to allow browsing remotely stored modules, viewing
# information about them, and downloading or upgrading
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use ModuleManager\modulerep_client;
use ModuleManager\utils;

if( !isset($gCms) ) exit;

if( isset($params['modulehelp']) ) {
    // this is done before permissions checks
    $params['mod'] = $params['modulehelp'];
    unset($params['modulehelp']);
    include(__DIR__.DIRECTORY_SEPARATOR.'action.local_help.php');
    return;
}

if( !$this->VisibleToAdminUser() ) exit;

$connection_ok = utils::is_connection_ok();
if( !$connection_ok ) $this->ShowErrors($this->Lang('error_request_problem'));

// this is a bit ugly.
utils::get_images();

$newversions = null;
if( $connection_ok ) {
    try {
        $newversions = modulerep_client::get_newmoduleversions();
    }
    catch( Exception $e ) {
        $this->ShowErrors($e->GetMessage());
    }
}

if (isset($params['active_tab'])) {
    $seetab = $params['active_tab'];
} else {
    $seetab = 'installed';
}

echo $this->StartTabHeaders();
if ($this->CheckPermission('Modify Modules')) {
    echo $this->SetTabHeader('installed',$this->Lang('installed'),$seetab=='installed');
    if ($connection_ok) {
        $num = (is_array($newversions)) ? count($newversions) : 0;
        echo $this->SetTabHeader('newversions',$num.' '.$this->Lang('tab_newversions'),$seetab=='newversions');
        echo $this->SetTabHeader('search',$this->Lang('search'),$seetab=='search');
        echo $this->SetTabHeader('modules',$this->Lang('availmodules'),$seetab=='modules');
    }
}
if ($this->CheckPermission('Modify Site Preferences')) {
    echo $this->SetTabHeader('prefs',$this->Lang('prompt_settings'),$seetab=='prefs');
}
echo $this->EndTabHeaders();

echo $this->StartTabContent();
if( $this->CheckPermission('Modify Modules') ) {
    echo $this->StartTab('installed',$params);
    include __DIR__.DIRECTORY_SEPARATOR.'function.admin_installed.php';
    echo $this->EndTab();

    if( $connection_ok ) {
        echo $this->StartTab('newversions',$params);
        include __DIR__.DIRECTORY_SEPARATOR.'function.newversionstab.php';
        echo $this->EndTab();

        echo $this->StartTab('search',$params);
        include __DIR__.DIRECTORY_SEPARATOR.'function.search.php';
        echo $this->EndTab();

        echo $this->StartTab('modules',$params);
        include __DIR__.DIRECTORY_SEPARATOR.'function.admin_modules_tab.php';
        echo $this->EndTab();
    }
}
if ($this->CheckPermission('Modify Site Preferences')) {
    echo $this->StartTab('prefs',$params);
    include __DIR__.DIRECTORY_SEPARATOR.'function.admin_prefs_tab.php';
    echo $this->EndTab();
}
echo $this->EndTabContent();
