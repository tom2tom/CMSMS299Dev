<?php
# CMSModuleManager module function: populate modules tab
# Copyright (C) 2008-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
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

if (!$this->CheckPermission('Modify Modules')) {
    exit;
}

if (!utils::is_connection_ok()) {
    $this->ShowErrors($this->Lang('error_request_problem'));
}

$dir = CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'modules'; //WHAT ??
$caninstall = (!is_dir($dir) || is_writable($dir));

$curletter = 'A';
if (isset($params['curletter'])) {
    $curletter = $params['curletter'];
    $_SESSION['mm_curletter'] = $curletter;
} elseif (isset($_SESSION['mm_curletter'])) {
    $curletter = $_SESSION['mm_curletter'];
}

// get the modules available in the repository
$repmodules = '';
{
    $result = modulerep_client::get_repository_modules($curletter);
    if (! $result[0]) {
        $this->_DisplayErrorPage($id, $params, $returnid, $result[1]);
        return;
    }
    $repmodules = $result[1];
}

// get the modules that are already installed
$instmodules = '';

$result = utils::get_installed_modules();
if (! $result[0]) {
    $this->_DisplayErrorPage($id, $params, $returnid, $result[1]);
    return;
}

$instmodules = $result[1];

// build a letters list
$letters = [];
$tmp = explode(',', 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z');
foreach ($tmp as $i) {
    $letters[$i] = $this->create_url($id, 'defaultadmin', $returnid, ['curletter'=>$i,'active_tab'=>'modules']);
}

// cross reference them
$data = [];
if ($repmodules) {
    $data = utils::build_module_data($repmodules, $instmodules);
}
if ($data) {
    $size = count($data);

    // check for permissions
    $moduledir = CMS_ASSETS_PATH.'/modules';
    $writable = is_writable($moduledir);

    // build the table
    $rowarray = [];
    $newestdisplayed = '';
    foreach ($data as $row) {
        $onerow = new stdClass();
        foreach ($row as $key => $value) {
            $onerow->$key = $value;
        }
        $onerow->name = $this->CreateLink($id, 'modulelist', $returnid, $row['name'], ['name'=>$row['name']]);
        $onerow->version = $row['version'];
        $onerow->help_url = $this->create_url(
            $id,
            'modulehelp',
            $returnid,
            ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]
        );

        $onerow->helplink = $this->CreateLink(
            $id,
            'modulehelp',
            $returnid,
            $this->Lang('helptxt'),
            ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]
        );

        $onerow->depends_url = $this->create_url(
            $id,
            'moduledepends',
            $returnid,
            ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]
        );

        $onerow->dependslink = $this->CreateLink(
            $id,
            'moduledepends',
            $returnid,
            $this->Lang('dependstxt'),
            ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]
        );

        $onerow->about_url = $this->create_url(
            $id,
            'moduleabout',
            $returnid,
            ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]
        );

        $onerow->aboutlink = $this->CreateLink(
            $id,
            'moduleabout',
            $returnid,
            $this->Lang('abouttxt'),
            ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename']]
        );
        $onerow->age = utils::get_status($row['date']);
        $onerow->date = $row['date'];
        $onerow->downloads = $row['downloads']??$this->Lang('unknown');
        $onerow->candownload = false;

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
            $mod = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
            if ((($writable && is_dir($mod) && is_directory_writable($mod)) ||
                 ($writable && !file_exists($mod))) && $caninstall) {
                $onerow->candownload = true;
                $onerow->status = $this->CreateLink(
                    $id,
                    'installmodule',
                    $returnid,
                    $this->Lang('download'),
                    ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'],
                   'size' => $row['size']]
                );
            } else {
                $onerow->status = $this->Lang('cantdownload');
            }
            break;
        case 'upgrade':
            $mod = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
            if ((($writable && is_dir($mod) && is_directory_writable($mod)) ||
                 ($writable && !file_exists($mod))) && $caninstall) {
                $onerow->candownload = true;
                $onerow->status = $this->CreateLink(
                    $id,
                    'installmodule',
                    $returnid,
                    $this->Lang('upgrade'),
                    ['name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'],
                           'size' => $row['size']]
                );
            } else {
                $onerow->status = $this->Lang('cantdownload');
            }
            break;
        }

        $onerow->size = (int)((float) $row['size'] / 1024.0 + 0.5);
        if (isset($row['description'])) {
            $onerow->description=$row['description'];
        }
        $rowarray[] = $onerow;
    } // for

    $tpl->assign('items', $rowarray)
     ->assign('itemcount', count($rowarray));
} else {
    $tpl->assign('message', $this->Lang('error_connectnomodules'));
}

// Setup search form
$searchstart = $this->CreateFormStart($id, 'searchmod', $returnid);
$searchend = $this->CreateFormEnd();
$searchfield = $this->CreateInputText($id, 'search_input', "Doesn't Work", 30, 100); //TODO lang
$searchsubmit =  '<button type="submit" name="'.$id.'submit" id="'.$id.'submit" class="adminsubmit icon search">'.$this->Lang('search').'</button>';
$tpl->assign('search', $searchstart.$searchfield.$searchsubmit.$searchend)

 ->assign('letter_urls', $letters)
 ->assign('curletter', $curletter)
 ->assign('nametext', $this->Lang('nametext'))
 ->assign('vertext', $this->Lang('vertext'))
 ->assign('sizetext', $this->Lang('sizetext'))
 ->assign('statustext', $this->Lang('statustext'));
