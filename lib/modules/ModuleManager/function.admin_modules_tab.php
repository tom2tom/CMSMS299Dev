<?php
# populate a tab for the defaultadmin action
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

use \ModuleManager\utils as modmgr_utils;

if (!isset($gCms)) {
    exit;
}
if (!$this->CheckPermission('Modify Modules')) {
    exit;
}

if (!modmgr_utils::is_connection_ok()) {
    $this->SetError($this->Lang('error_request_problem'));
    return;
}

$dir = CMS_ASSETS_PATH.'/modules';
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
{
    $result = modmgr_utils::get_installed_modules();
    if (! $result[0]) {
        $this->_DisplayErrorPage($id, $params, $returnid, $result[1]);
        return;
    }

    $instmodules = $result[1];
}

// build a letters list
$letters = array();
$tmp = explode(',', 'A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z');
foreach ($tmp as $i) {
    $letters[$i] = $this->create_url($id, 'defaultadmin', $returnid, array('curletter'=>$i,'active_tab'=>'modules'));
}

// cross reference them
$data = array();
if (count($repmodules)) {
    $data = modmgr_utils::build_module_data($repmodules, $instmodules);
}
if (count($data)) {
    $size = count($data);

    // check for permissions
    $moduledir = CMS_ASSETS_PATH.'/modules';
    $writable = is_writable($moduledir);

    // build the table
    $rowarray = array();
    $newestdisplayed='';
    foreach ($data as $row) {
        $onerow = new stdClass();
        foreach ($row as $key => $value) {
            $onerow->$key = $value;
        }
        $onerow->name = $this->CreateLink($id, 'modulelist', $returnid, $row['name'], array('name'=>$row['name']));
        $onerow->version = $row['version'];
        $onerow->help_url = $this->create_url(
            $id,
            'modulehelp',
            $returnid,
            array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'])
        );

        $onerow->helplink = $this->CreateLink(
            $id,
            'modulehelp',
            $returnid,
            $this->Lang('helptxt'),
            array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'])
        );

        $onerow->depends_url = $this->create_url(
            $id,
            'moduledepends',
            $returnid,
            array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'])
        );

        $onerow->dependslink = $this->CreateLink(
            $id,
            'moduledepends',
            $returnid,
            $this->Lang('dependstxt'),
            array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'])
        );

        $onerow->about_url = $this->create_url(
            $id,
            'moduleabout',
            $returnid,
            array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'])
        );

        $onerow->aboutlink = $this->CreateLink(
            $id,
            'moduleabout',
            $returnid,
            $this->Lang('abouttxt'),
            array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'])
        );
        $onerow->age = modmgr_utils::get_status($row['date']);
        $onerow->date = $row['date'];
        $onerow->downloads = $row['downloads']??$this->Lang('unknown');
        $onerow->candownload = false;

        switch ($row['status']) {
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
            $mod = $moduledir.DIRECTORY_SEPARATOR.$row['name'];
            if ((($writable && is_dir($mod) && is_directory_writable($mod)) ||
                 ($writable && !file_exists($mod))) && $caninstall) {
                $onerow->candownload = true;
                $onerow->status = $this->CreateLink(
                    $id,
                    'installmodule',
                    $returnid,
                    $this->Lang('download'),
                    array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'],
                   'size' => $row['size'])
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
                    array('name' => $row['name'],'version' => $row['version'],'filename' => $row['filename'],
                           'size' => $row['size'])
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

    $smarty->assign('items', $rowarray);
    $smarty->assign('itemcount', count($rowarray));
} else {
    $smarty->assign('message', $this->Lang('error_connectnomodules'));
}

// Setup search form
$searchstart = $this->CreateFormStart($id, 'searchmod', $returnid);
$searchend = $this->CreateFormEnd();
$searchfield = $this->CreateInputText($id, 'search_input', "Doesn't Work", 30, 100); //TODO lang
$searchsubmit =  '<button type="submit" name="'.$id.'submit" id="'.$id.'submit" class="adminsubmit iconsearch">'.$this->Lang('search').'</button>';
$smarty->assign('search', $searchstart.$searchfield.$searchsubmit.$searchend);

// and display our page
$smarty->assign('letter_urls', $letters);
$smarty->assign('curletter', $curletter);
$smarty->assign('nametext', $this->Lang('nametext'));
$smarty->assign('vertext', $this->Lang('vertext'));
$smarty->assign('sizetext', $this->Lang('sizetext'));
$smarty->assign('statustext', $this->Lang('statustext'));
echo $this->processTemplate('adminpanel.tpl');

#
# EOF
#
