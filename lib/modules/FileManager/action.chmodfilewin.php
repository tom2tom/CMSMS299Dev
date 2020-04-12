<?php
if (!isset($gCms)) exit;
if (!$this->AccessAllowed() && !$this->AdvancedAccessAllowed()) exit;

if(!isset($params['filename']) || !isset($params['path'])) {
	$this->Redirect($id,'defaultadmin');
}

if( !FileManager\Utils::test_valid_path($params['path']) ) {
	$this->Redirect($id,'defaultadmin',$returnid,['fmerror'=>'fileoutsideuploads']);
}

$config = $gCms->GetConfig();
$fullname = cms_join_path(CMS_ROOT_PATH,$params['path'],$params['filename']);

if (isset($params['newmode'])) {
	if (isset($params['cancel'])) {
		$this->Redirect($id,'defaultadmin',$returnid,['path'=>$params['path'],'fmmessage'=>'chmodcancelled']);
	} else {
		if ($this->SetModeWin($params['newmode'],$fullname)) {
			$this->Redirect($id,'defaultadmin',$returnid,['path'=>$params['path'],'fmmessage'=>'chmodsuccess']);
		} else {
			$this->Redirect($id,'defaultadmin',$returnid,['path'=>$params['path'],'fmerror'=>'chmodfailure']);
		}
	}
} else {
	$currentmode = $this->GetModeWin($params['path'],$params['filename']);
	$tpl = $smarty->createTemplate($this->GetTemplateResource('chmodfilewin.tpl'),null,null,$smarty);
	$tpl->assign('formstart', $this->CreateFormStart($id, 'chmodfilewin', $returnid))

	 ->assign('filename', $this->CreateInputHidden($id,'filename',$params['filename']))
	 ->assign('path', $this->CreateInputHidden($id,'path',$params['path']))
	 ->assign('formend', $this->CreateFormEnd())
	 ->assign('newmodetext', $this->Lang('newpermissions'))

	 ->assign('modeswitch',
		  $this->CreateInputRadioGroup($id,'newmode',[$this->Lang('writable')=>'777',$this->Lang('writeprotected')=>'444'],$currentmode))
	 ->assign('modeswitchof', $this->GetModeTable($id,$this->GetPermissions($params['path'],$params['filename'])));

//see template	->assign('submit', //$this->CreateInputSubmit($id, 'submit', $this->Lang('setpermissions')))
//	 ->assign('cancel', //$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')));

	$tpl->display();
}
return false;
