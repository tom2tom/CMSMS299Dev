<?php
/*
FileManager module action: chmodfile
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

//if (some worthy test fails) exit;
if (!$this->AccessAllowed() && !$this->AdvancedAccessAllowed()) {
    exit;
}

if (!isset($params['filename']) || !isset($params['path'])) {
    $this->Redirect($id, 'defaultadmin');
}

if (!FileManager\Utils::test_valid_path($params['path'])) {
    $this->Redirect($id, 'defaultadmin', $returnid, ['fmerror' => 'fileoutsideuploads']);
}

$config = $gCms->GetConfig();
$fullname = cms_join_path(CMS_ROOT_PATH, $params['path'], $params['filename']);

if (isset($params['newmode'])) {
    //echo 'deleting';exit;
    if (isset($params['cancel'])) {
        $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmmessage' => 'chmodcancelled']);
    } else {
        $newmode = $this->GetModeFromTable($params);
        if (isset($params['quickmode']) && ($params['quickmode'] != '')) {
            $newmode = $params['quickmode'];
        }
        //echo $newmode;die();
        if ($this->SetMode($newmode, $fullname)) {
            $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmmessage' => 'chmodsuccess']);
        } else {
            $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmerror' => 'chmodfailure']);
        }
    }
} else {
    $currentmode = $this->GetMode($params['path'], $params['filename']);

    $tpl = $smarty->createTemplate($this->GetTemplateResource('chmodfile.tpl')); //,null,null,$smarty);
    $tpl->assign('formstart', $this->CreateFormStart($id, 'chmodfile', $returnid))
        ->assign('filename', $this->CreateInputHidden($id, 'filename', $params['filename']))
        ->assign('path', $this->CreateInputHidden($id, 'path', $params['path']))
        ->assign('newmodetext', $this->Lang('newpermissions'))
        ->assign('newmode', $this->CreateInputHidden($id, 'newmode', 'newset'))
        ->assign('modetable', $this->GetModeTable($id, $this->GetPermissions($params['path'], $params['filename'])))
        ->assign('quickmodetext', $this->Lang('quickmode'))
        ->assign('quickmodeinput', $this->CreateInputText($id, 'quickmode', '', 3, 3));
//see template    ->assign('submit', //$this->CreateInputSubmit($id, 'submit', $this->Lang('setpermissions')));
//    ->assign('cancel', //$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')));
    $tpl->display();
}
