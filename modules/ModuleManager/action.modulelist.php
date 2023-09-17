<?php
/*
ModuleManager module action: modulelist
Copyright (C) 2011-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

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

use ModuleManager\ModuleRepClient;
use ModuleManager\Utils;
use function CMSMS\log_error;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Modules') ) exit;

$_SESSION[$this->GetName()]['active_tab'] = 'modules';
if( !isset($params['name']) ) $this->Redirect($id,'defaultadmin');

$prefix = trim($params['name']);
$result = ModuleRepClient::get_repository_modules($prefix,FALSE,TRUE);
if( !is_array($result) || !$result[0] ) {
    $msg = $result[1] ?? $this->Lang('error_internal'); // TODO better default advice
    log_error($msg,$this->GetName().'::modulelist');
//    $this->DisplayErrorPage($msg);
//    return;
    $this->SetError($msg);
    $this->Redirect($id,'defaultadmin'); // for some reason, nothing matched
}

$repmodules = $result[1];

$result = Utils::get_installed_modules();
if( !is_array($result) || !$result[0] ) {
    $msg = $result[1] ?? $this->Lang('error_internal'); // TODO better default advice
    log_error($msg,$this->GetName().'::modulelist');
    $this->DisplayErrorPage($msg);
    return;
//    $this->SetError($msg);
//    $this->Redirect($id,'defaultadmin');
}

$instmodules = $result[1];

$tpl = $smarty->createTemplate($this->GetTemplateResource('showmodule.tpl')); //,null,null,$smarty);

$data = Utils::build_module_data($repmodules,$instmodules,FALSE);
if( $data ) {

// defined data structure to support PHP optimisation
class ModuleListData
{
    public $aboutlink;
    public $age;
    public $date;
    public $dependslink;
    public $description;
    public $downloads;
    public $helplink;
    public $name;
    public $size;
    public $status;
    public $version;
}

    $size = count($data);

    $dirlist = cms_module_places();
    $writelist = [];
    foreach( $dirlist as $i => $dir ) {
        $writelist[$i] = is_writable($dir);
    }

    // build the table
    $rowarray = [];
    $newestdisplayed = '';
    foreach( $data as $row ) {
        $onerow = new ModuleListData(); //stdClass();
        $onerow->age = Utils::get_status($row['date']);
        $onerow->date = $row['date'];
        $onerow->description = ( !empty($row['description']) ) ? $row['description'] : null;
        $onerow->downloads = $row['downloads']; // 0 i.e. not recorded ATM
        $onerow->name = $row['name'];
        $onerow->size = (int)((float) $row['size'] / 1024.0 + 0.5);
        $onerow->version = $row['version'];
        $onerow->helplink = $this->CreateLink($id, 'modulehelp', $returnid,
            $this->Lang('helptxt'),
            ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);
        $onerow->dependslink = $this->CreateLink($id, 'moduledepends', $returnid,
            $this->Lang('dependstxt'),
            ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);
        $onerow->aboutlink = $this->CreateLink($id, 'moduleabout', $returnid,
            $this->Lang('abouttxt'),
            ['name' => $row['name'], 'version' => $row['version'], 'filename' => $row['filename']]);

        switch( $row['status'] ) {
            case 'incompatible':
                $onerow->status = $this->Lang('incompatible');
                break;
            case 'uptodate':
                $onerow->status = $this->Lang('uptodate');
                break;
            case 'newerversion':
                $onerow->status = $this->Lang('newerversion');
                break;
            case 'notinstalled':
                // check for uninstalled presence
                $moddir = '';
                $cando = false;
                $writable = false;
                $modname = trim($row['name']);
                foreach( $dirlist as $i => $dir ) {
                    $fp = $dir.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
                    if( is_file($fp) ) {
                        $moddir = dirname($fp);
                        $cando = $writable = $writelist[$i];
                        break;
                    }
                }
                if( !$moddir ) {
                    // nope, default to main-place
                    $moddir = $dirlist[0].DIRECTORY_SEPARATOR.$modname;
                    $cando = $writable = $writelist[0];
                }

                if( !$writable ) {
                    $onerow->status = $this->Lang('cantinstall');
                }
                elseif( (is_dir($moddir) && is_directory_writable($moddir)) ||
                        ($cando && !file_exists($moddir)) ) {
                    $onerow->status = $this->CreateLink($id, 'installmodule', $returnid,
                        $this->Lang('download'),
                        ['name' => $row['name'], 'version' => $row['version'],
                         'filename' => $row['filename'], 'size' => $row['size']]);
                }
                else {
                    $onerow->status = $this->Lang('cantinstall');
                }
                break;
            case 'upgrade':
                $moddir = '';
                $cando = false;
                $writable = false;
                $modname = trim($row['name']);
                foreach( $dirlist as $i => $dir ) {
                    $fp = $dir.DIRECTORY_SEPARATOR.$modname.DIRECTORY_SEPARATOR.$modname.'.module.php';
                    if( is_file($fp) ) {
                        $moddir = dirname($fp);
                        $writable = $writelist[$i];
                        $cando = is_writable($moddir);
                        break;
                    }
                }
                if( !$moddir ) { // should never happen for an upgrade
                    $moddir = $dirlist[0].DIRECTORY_SEPARATOR.$modname;
                    $cando = $writable = $writelist[0];
                }

                if( !$writable ) {
                    $onerow->status = $this->Lang('cantupgrade');
                }
                elseif( (is_dir($moddir) && is_directory_writable($moddir)) ||
                        ($cando && !file_exists($moddir)) ) {
                    $onerow->status = $this->CreateLink($id, 'installmodule', $returnid,
                        $this->Lang('upgrade'),
                        ['name' => $row['name'],'version' => $row['version'],
                         'filename' => $row['filename'], 'size' => $row['size']]);
                }
                else {
                    $onerow->status = $this->Lang('cantupgrade');
                }
                break;
        }  // switch
        $rowarray[] = $onerow;
    } // foreach

    Utils::get_images($tpl);
    $tpl->assign('items', $rowarray)
     ->assign('itemcount', count($rowarray));
}

$tpl->assign('nametext',$this->Lang('nametext'))
 ->assign('vertext',$this->Lang('version'))
 ->assign('sizetext',$this->Lang('sizetext'))
 ->assign('statustext',$this->Lang('statustext'))
 ->assign('header',$this->Lang('versionsformodule',$prefix));

$tpl->display();
