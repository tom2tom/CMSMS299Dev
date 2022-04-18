<?php

//if (some worthy test fails) exit;
if (!$this->AccessAllowed() && !$this->AdvancedAccessAllowed()) exit;

if (!isset($params['filename']) || !isset($params['path'])) {
  $this->Redirect($id, 'defaultadmin');
}

if( !FileManager\Utils::test_valid_path($params['path']) ) {
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
   ->assign('formend', $this->CreateFormEnd())
   ->assign('newmodetext', $this->Lang('newpermissions'))

   ->assign('newmode', $this->CreateInputHidden($id, 'newmode', 'newset'))

   ->assign('modetable', $this->GetModeTable($id, $this->GetPermissions($params['path'], $params['filename'])))

   ->assign('quickmodetext', $this->Lang('quickmode'))
   ->assign('quickmodeinput', $this->CreateInputText($id, 'quickmode', '', 3, 3));
//see template  ->assign('submit', //$this->CreateInputSubmit($id, 'submit', $this->Lang('setpermissions')));
//  ->assign('cancel', //$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')));
  $tpl->display();
}
