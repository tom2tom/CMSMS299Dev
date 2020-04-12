<?php
use FileManager\Utils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) exit;
if (isset($params['cancel'])) $this->Redirect($id, 'defaultadmin', $returnid, $params);

$path = Utils::get_cwd();

$newdirname = '';
if (isset($params['newdirname'])) {
  $newdirname = trim($params['newdirname']);

  if (!Utils::is_valid_filename($params['newdirname'])) {
    // $this->Redirect($id, 'defaultadmin',$returnid,array("fmerror"=>"invalidnewdir"));
    $this->ShowErrors($this->Lang('invalidnewdir'));
    //fallthrough
  } else {

    $newdir = cms_join_path(CMS_ROOT_PATH, $params['path'], $params['newdirname']);

    if (is_dir($newdir)) {
      $this->ShowErrors($this->Lang('direxists'));
      //fallthrough
    } else {
      if (mkdir($newdir)) {
        $params['fmmessage'] = 'newdirsuccess'; //strips the file data
        $this->Audit(0, 'File Manager', 'Created new directory: ' . $params['newdirname']);
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
      } else {
        $params['fmerror'] = 'newdirfail';
        $this->Redirect($id, 'defaultadmin', $returnid, $params);
      }
    }
  }
}
$tpl = $smarty->createTemplate($this->GetTemplateResource('newdir.tpl'),null,null,$smarty);

$tpl->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', $params))
 ->assign('newdirtext', $this->lang('newdir'))
 ->assign('newdirname',$newdirname)
 ->assign('formend', $this->CreateFormEnd());
// see template ->assign('submit', //$this->CreateInputSubmit($id, 'submit', $this->Lang('create')))
// ->assign('cancel', //$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')));

$tpl->display();
return false;
