<?php
/*
FileManager module action: chmodfilewin
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
    if (isset($params['cancel'])) {
        $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmmessage' => 'chmodcancelled']);
    } else {
        if ($this->SetModeWin($params['newmode'], $fullname)) {
            $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmmessage' => 'chmodsuccess']);
        } else {
            $this->Redirect($id, 'defaultadmin', $returnid, ['path' => $params['path'], 'fmerror' => 'chmodfailure']);
        }
    }
} else {
    $currentmode = $this->GetModeWin($params['path'], $params['filename']);
    //  $out = $this->CreateInputRadioGroup($id,'newmode',[$this->Lang('writable')=>'777',$this->Lang('writeprotected')=>'444'],$currentmode);
    $out = FormUtils::create_select([ // DEBUG
        'type' => 'radio',
        'name' => 'newmode',
        'htmlid' => 'newmode',
        'getid' => $id,
        'options' => [$this->Lang('writable') => '777', $this->Lang('writeprotected') => '444'], //OR server_mode()[whatever]
        'selectedvalue' => $currentmode,
//      'delimiter' => '',
    ]);

    $tpl = $smarty->createTemplate($this->GetTemplateResource('chmodfilewin.tpl')); //,null,null,$smarty);
    $tpl->assign('formstart', $this->CreateFormStart($id, 'chmodfilewin', $returnid))
        ->assign('filename', $this->CreateInputHidden($id, 'filename', $params['filename']))
        ->assign('path', $this->CreateInputHidden($id, 'path', $params['path']))
        ->assign('newmodetext', $this->Lang('newpermissions'))
        ->assign('modeswitch', $out)
        ->assign('modeswitchof', $this->GetModeTable($id, $this->GetPermissions($params['path'], $params['filename'])));

//see template  ->assign('submit', //$this->CreateInputSubmit($id, 'submit', $this->Lang('setpermissions')))
//      ->assign('cancel', //$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')));

    $tpl->display();
}
