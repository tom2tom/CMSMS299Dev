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

use CMSMS\Utils as AppUtils;
use ModuleManager\ModuleRepClient;
use ModuleManager\Utils;

$results = [];
$newversions = [];

if( $connection_ok ) {
    try {
        $newversions = ModuleRepClient::get_newmoduleversions();
    }
    catch( Throwable $t ) {
        $this->ShowErrors($t->GetMessage());
    }
}

if( $newversions ) {
    foreach( $newversions as $row ) {
        $onerow = new stdClass();

        foreach( $row as $key => $val ) {
            $onerow->$key = $val;
        }

        $mod = $this->GetModuleInstance($row['name']);
        if( !is_object($mod) ) {
            $onerow->error = $this->Lang('error_module_object',$row['name']);
            $onerow->txt = $onerow->age = $onerow->depends_url = $onerow->about_url = $onerow->help_url = $onerow->helplink = $onerow->aboutlink = $onerow->dependslink = null;
        }
        else {
            $onerow->error = null;
            $mver = $mod->GetVersion();
            if( version_compare($row['version'],$mver) > 0 ) {
                $onerow->age = Utils::get_status($row['date']);
                $onerow->date = $row['date'];
                if( !empty($row['description']) ) {
                    $onerow->description = $row['description'];
                } else {
                    $onerow->description = null;
                }
                $onerow->downloads = $row['downloads'];
                $installed_mod = AppUtils::get_module($row['name']);
                if( is_object($installed_mod) ) {
                    $onerow->haveversion = $installed_mod->GetVersion();
                } else {
                    $onerow->haveversion = null;
                }
                $onerow->name = $this->CreateLink($id, 'modulelist', $returnid, $row['name'], ['name'=>$row['name']]);
                $onerow->size = (int)((float) $row['size'] / 1024.0 + 0.5);
                $onerow->txt = $this->Lang('upgrade_available', $row['version'],$mver);
                $onerow->version = $row['version'];

                $onerow->help_url = $this->create_action_url($id,'modulehelp',
                    ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);
                $onerow->helplink = $this->CreateLink($id, 'modulehelp', $returnid, $this->Lang('helptxt'),
                    ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);

                $onerow->depends_url = $this->create_action_url($id, 'moduledepends',
                    ['name' => $row['name'], 'version' => $row['version'],'filename' => $row['filename']]);
                $onerow->dependslink = $this->CreateLink($id, 'moduledepends', $returnid,
                    $this->Lang('dependstxt'),
                    ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);

                $onerow->about_url = $this->create_action_url($id, 'moduleabout',
                    ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);
                $onerow->aboutlink = $this->CreateLink($id, 'moduleabout', $returnid,
                    $this->Lang('abouttxt'),
                    ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);

                $moddir = $dirlist[0];
                $cando = false;
                $writable = $writelist[0];
                $modname = trim($row['name']);
                foreach( $dirlist as $i => $dir ) {
                    $fp = $dir.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
                    if( is_file($fp) ) {
                        $moddir = dirname($fp);
                        $cando = $writable = $writelist[$i];
                        break;
                    }
                }

                if( !$writable ) {
                    $onerow->status = $this->Lang('cantupgrade');
                }
                elseif( (is_dir($moddir) && is_directory_writable($moddir)) ||
                        ($cando && !file_exists( $moddir)) ) {
                    if( (!empty($row['maxcmsversion']) && version_compare(CMS_VERSION, $row['maxcmsversion']) > 0) ||
                        (!empty($row['mincmsversion']) && version_compare(CMS_VERSION, $row['mincmsversion']) < 0) ) {
                        $onerow->status = 'incompatible';
                    } else {
                        $onerow->status = $this->CreateLink($id, 'installmodule', $returnid,
                            $this->Lang('upgrade'),
                            ['name' => $row['name'], 'version' => $row['version'],
                             'filename' => $row['filename'], 'size' => $row['size'],
                             'active_tab'=>'newversions', 'reset_prefs' => 1]);
                    }
                }
                else {
                    $onerow->status = $this->Lang('cantupgrade');
                }
            }
        }
        $results[] = $onerow;
    }
}

$num = ($newversions) ? count($newversions) : 0;
$tpl->assign('newtext',$this->Lang('tab_newversions',$num));

if( $results)
    $tpl->assign('updatestxt',$this->Lang('available_updates'))
     ->assign('updates',$results)
     ->assign('upcount',count($results));
else {
    $tpl->assign('nvmessage',$this->Lang('all_modules_up_to_date'));
}
