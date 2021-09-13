<?php
/*
CMSModuleManager module function: populate modules tab
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

use ModuleManager\ModuleRepClient;
use ModuleManager\Utils;

if (!Utils::is_connection_ok()) {
    $this->ShowErrors($this->Lang('error_request_problem'));
}

$curletter = 'A';
if (isset($params['curletter'])) {
    $curletter = $params['curletter'];
    $_SESSION['mm_curletter'] = $curletter;
}
elseif (isset($_SESSION['mm_curletter'])) {
    $curletter = $_SESSION['mm_curletter'];
}

// get the modules available in the repository
$result = ModuleRepClient::get_repository_modules($curletter);
if (!$result[0]) {
    $repmodules = '';
    $this->DisplayErrorPage($result[1]);
    return;
}
$repmodules = $result[1];

// get the modules that are installed
$result = Utils::get_installed_modules();
if (!$result[0]) {
    $instmodules = '';
    $this->DisplayErrorPage($result[1]);
    return;
}
$instmodules = $result[1];

// build a letters list
$letters = [];
$tmp = explode(',', 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z');
foreach ($tmp as $i) {
    $letters[$i] = $this->create_action_url($id, 'defaultadmin', ['curletter'=>$i,'active_tab'=>'modules']);
}

// cross reference them
$data = [];
if ($repmodules) {
    $data = Utils::build_module_data($repmodules, $instmodules);
}
if ($data) {
    $size = count($data);

    // build the table
    $rowarray = [];
    $newestdisplayed = '';
    foreach ($data as $row) {
        $onerow = (object)$row;

        $onerow->age = Utils::get_status($row['date']);
        $onerow->candownload = false; // maybe changed later
        $onerow->date = $row['date'];
        $onerow->description = (!empty($row['description'])) ? $row['description'] : null;
        $onerow->downloads = $row['downloads']??$this->Lang('unknown');
        $onerow->name = $this->CreateLink($id, 'modulelist', $returnid, $row['name'], ['name'=>$row['name']]);
        $onerow->size = (int)((float) $row['size'] / 1024.0 + 0.5);
        $onerow->version = $row['version'];

        $onerow->help_url = $this->create_action_url($id, 'modulehelp',
            ['name' => $row['name'], 'version' => $row['version'],
             'filename' => $row['filename']]);
        $onerow->helplink = $this->CreateLink($id, 'modulehelp', $returnid,
            $this->Lang('helptxt'),
            ['name' => $row['name'], 'version' => $row['version'],
             'filename' => $row['filename']]);

        $onerow->depends_url = $this->create_action_url($id, 'moduledepends',
            ['name' => $row['name'], 'version' => $row['version'],
             'filename' => $row['filename']]);
        $onerow->dependslink = $this->CreateLink($id, 'moduledepends', $returnid,
            $this->Lang('dependstxt'),
            ['name' => $row['name'], 'version' => $row['version'],
             'filename' => $row['filename']]);

        $onerow->about_url = $this->create_action_url($id, 'moduleabout',
            ['name' => $row['name'], 'version' => $row['version'],
             'filename' => $row['filename']]);
        $onerow->aboutlink = $this->CreateLink($id, 'moduleabout', $returnid,
            $this->Lang('abouttxt'),
            ['name' => $row['name'], 'version' => $row['version'],
             'filename' => $row['filename']]);

        switch ($row['status']) {
          case 'incompatible':
            $onerow->status = $this->Lang('incompatible'); //TODO remove any other rows with some other status
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
                $onerow->candownload = true;
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
                $onerow->candownload = true;
                $onerow->status = $this->CreateLink($id, 'installmodule', $returnid,
                    $this->Lang('upgrade'),
                    ['name' => $row['name'],'version' => $row['version'],
                     'filename' => $row['filename'], 'size' => $row['size']]);
            }
            else {
                $onerow->status = $this->Lang('cantupgrade');
            }
            break;
        }
        $rowarray[] = $onerow;
    } // foreach

    $tpl->assign('items', $rowarray)
     ->assign('itemcount', count($rowarray));
}
else {
    $tpl->assign('message', $this->Lang('error_connectnomodules'));
}

$tpl->assign('letter_urls', $letters)
 ->assign('curletter', $curletter)
 ->assign('nametext', $this->Lang('nametext'))
 ->assign('vertext', $this->Lang('version'))
 ->assign('sizetext', $this->Lang('sizetext'))
 ->assign('statustext', $this->Lang('statustext'));
