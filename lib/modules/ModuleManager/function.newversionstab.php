<?php
/*
ModuleManager module function: populate new-versions tab
Copyright (C) 2008-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

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

use CMSMS\Utils;
use ModuleManager\modulerep_client;
use ModuleManager\Utils as ManagerUtils;

//TODO what's expected to go into the alternate dir ?
$dir = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'modules';
$caninstall = (is_dir($dir) && is_writable($dir));
//this is a core module, so it goes here ...
$moduledir = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'modules';
$writable = is_dir($moduledir) && is_writable( $moduledir );

$results = [];
$newversions = null;

if( $connection_ok ) {
    try {
        $newversions = modulerep_client::get_newmoduleversions();
    }
    catch( Exception $e ) {
        $this->ShowErrors($e->GetMessage());
    }
}

if( $newversions ) {
    foreach( $newversions as $row ) {
        $txt = '';
        $onerow = new stdClass();
        $onerow->txt = $onerow->error = $onerow->age = $onerow->depends_url = $onerow->about_url = $onerow->help_url = $onerow->helplink = $onerow->aboutlink = $onerow->dependslink = null;
        foreach( $row as $key => $val ) {
            $onerow->$key = $val;
        }

        $mod = $this->GetModuleInstance($row['name']);
        if( !is_object($mod) ) {
            $onerow->error = $this->Lang('error_module_object',$row['name']);
        }
        else {
            $mver = $mod->GetVersion();
            if( version_compare($row['version'],$mver) > 0 ) {
                $modinst = Utils::get_module($row['name']);
                if( is_object($modinst) ) $onerow->haveversion = $modinst->GetVersion();

                $onerow->age = ManagerUtils::get_status($row['date']);
                $onerow->downloads = $row['downloads'];
                $onerow->date = $row['date'];
                $onerow->age = ManagerUtils::get_status($row['date']);

                $onerow->name = $this->CreateLink( $id, 'modulelist', $returnid, $row['name'], ['name'=>$row['name']]);
                $onerow->version = $row['version'];

                $onerow->help_url = $this->create_url($id,'modulehelp',$returnid,
                                                      ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);
                $onerow->helplink = $this->CreateLink( $id, 'modulehelp', $returnid, $this->Lang('helptxt'),
                                                       ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);

                $onerow->depends_url = $this->create_url( $id, 'moduledepends', $returnid,
                                                          ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);

                $onerow->dependslink = $this->CreateLink( $id, 'moduledepends', $returnid,
                                                          $this->Lang('dependstxt'),
                                                          ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);

                $onerow->about_url = $this->create_url( $id, 'moduleabout', $returnid,
                                                        ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);

                $onerow->aboutlink = $this->CreateLink( $id, 'moduleabout', $returnid,
                                                        $this->Lang('abouttxt'),
                                                        ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]);

                $onerow->size = (int)((float) $row['size'] / 1024.0 + 0.5);
                if( isset( $row['description'] ) ) $onerow->description=$row['description'];
                $onerow->txt= $this->Lang('upgrade_available',$row['version'],$mver);
                $moddir = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
                if( (($writable && is_dir($moddir) && is_directory_writable( $moddir )) ||
                     ($writable && !file_exists( $moddir ) )) && $caninstall ) {
                    if( (!empty($row['maxcmsversion']) && version_compare(CMS_VERSION,$row['maxcmsversion']) > 0) ||
                        (!empty($row['mincmsversion']) && version_compare(CMS_VERSION,$row['mincmsversion']) < 0) ) {
                        $onerow->status = 'incompatible';
                    } else {
                        $onerow->status = $this->CreateLink( $id, 'installmodule', $returnid,
                                                             $this->Lang('upgrade'),
                                                            ['name' => $row['name'],'version' => $row['version'],
                                                            'filename' => $row['filename'],'size' => $row['size'],
                                                            'active_tab'=>'newversions','reset_prefs' => 1]);
                    }
                }
                else {
                    $onerow->status = $this->Lang('cantdownload');
                }
            }
        }

        $results[] = $onerow;
    }
}

$num = ( is_array($newversions) ) ? count($newversions) : 0;
$tpl->assign('newtext',$this->Lang('tab_newversions', $num));

if( $results)
    $tpl->assign('updatestxt',$this->Lang('available_updates'))
     ->assign('updates',$results)
     ->assign('upcount',count($results));
else {
    $tpl->assign('nvmessage',$this->Lang('all_modules_up_to_date'));
}
