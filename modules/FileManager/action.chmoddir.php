<?php
/*
FileManager module action: chmoddir
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use FileManager\Utils;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) {
    exit;
}

if (!isset($params['dirname']) || !isset($params['path'])) {
    $this->Redirect($id, 'defaultadmin');
}

if (!Utils::test_valid_path($params['path'])) {
    $this->Redirect($id, 'defaultadmin', $returnid, ['fmerror' => 'fileoutsideuploads']);
}

$config = $gCms->GetConfig();
$fullname = cms_join_path(CMS_ROOT_PATH, $params['path'], $params['dirname']);

function chmodRecursive(string $path, $newmode, FileManager &$module)
{
    $dir = opendir($path);
    while ($entry = readdir($dir)) {
        if ($entry == '.' || $entry == '..') {
            continue;
        }

        if (is_file("$path/$entry")) {
            $module->SetMode($newmode, $path, $entry);
        //echo "hi";exit;
        } elseif (is_dir("$path/$entry") && $entry != '.' && $entry != '..') {
            chmodRecursive("$path/$entry", $newmode, $module);
        }
    }
    closedir($dir);
    return $module->SetMode($newmode, $path);
}

function isEmpty(string $path): bool
{
    $empty = true;
    $dir = opendir($path);
    while ($entry = readdir($dir)) {
        if ($entry != '.' && $entry != '..' && $entry != '\\' && $entry != '/') {
            return false;
        }
    }
    return true;
}

$emptydir = isEmpty($fullname);

if (isset($params['newmode'])) {
    if (isset($params['cancel'])) {
        $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'module_message' => $this->Lang('chmodcancelled')]);
    } else {
        $newmode = $this->GetModeFromTable($params);
        if (isset($params['quickmode']) && $params['quickmode'] != '') {
            $newmode = $params['quickmode'];
        }
        if (isset($params['recurse']) && $params['recurse'] == '1' && !$emptydir) {
            if (chmodRecursive($fullname, $newmode, $this)) {
                $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmmessage' => 'dirchmodsuccessmulti']);
            } else {
                $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmerror' => 'dirchmodfailmulti']);
            }
        } else {
            //No recursion
            if ($this->SetMode($newmode, $fullname)) {
                $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmmessage' => 'dirchmodsuccess']);
            } else {
                $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmerror' => 'dirchmodfailure']);
            }
        }
    }
} else {
    $currentmode = $this->GetMode($params['path'], $params['dirname']);
    $tpl = $smarty->createTemplate($this->GetTemplateResource('chmoddir.tpl')); //,null,null,$smarty);
    $tpl->assign('formstart', $this->CreateFormStart($id, 'chmoddir', $returnid))
        ->assign('filename', $this->CreateInputHidden($id, 'dirname', $params['dirname']))
        ->assign('path', $this->CreateInputHidden($id, 'path', $params['path']))
        ->assign('newmodetext', $this->Lang('newpermissions'))
        ->assign('recurseinputtext', $this->Lang('recursetext'))
        ->assign('recurseinput', $this->CreateInputCheckbox($id, 'recurse', '1'))
        ->assign('newmode', $this->CreateInputHidden($id, 'newmode', 'newset'))
        ->assign('quickmodetext', $this->Lang('quickmode'))
        ->assign('quickmodeinput', $this->CreateInputText($id, 'quickmode', '', 3, 3))
        ->assign('modetable', $this->GetModeTable($id, $this->GetPermissions($params['path'], $params['dirname'])));

    // see template ->assign('submit', //$this->CreateInputSubmit($id, 'submit', $this->Lang('setpermissions')));
    //  ->assign('cancel', //$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')));

    $tpl->display();
}
