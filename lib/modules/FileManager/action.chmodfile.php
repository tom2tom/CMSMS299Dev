<?php

if (!isset($gCms)) exit;
if (!$this->AccessAllowed() && !$this->AdvancedAccessAllowed()) exit;

if (!isset($params["filename"]) || !isset($params["path"])) {
  $this->Redirect($id, 'defaultadmin');
}

if( !FileManager\filemanager_utils::test_valid_path($params['path']) ) {
  $this->Redirect($id, 'defaultadmin', $returnid, array("fmerror" => "fileoutsideuploads"));
}

$config = & $gCms->GetConfig();
$fullname = $this->Slash($params["path"], $params["filename"]);
$fullname = $this->Slash($config["root_path"], $fullname);


if (isset($params["newmode"])) {
  //echo deleting;die();
  if (isset($params["cancel"])) {
    $this->Redirect($id, "defaultadmin", $returnid, array("path" => $params["path"], "fmmessage" => "chmodcancelled"));
  } else {
    $newmode = $this->GetModeFromTable($params);
    if (isset($params["quickmode"]) && ($params["quickmode"] != "")) {
      $newmode = $params["quickmode"];
    }
    //echo $newmode;die();
    if ($this->SetMode($newmode, $fullname)) {
      $this->Redirect($id, "defaultadmin", $returnid, array("path" => $params["path"], "fmmessage" => "chmodsuccess"));
    } else {
      $this->Redirect($id, "defaultadmin", $returnid, array("path" => $params["path"], "fmerror" => "chmodfailure"));
    }
  }
} else {
  $currentmode = $this->GetMode($params["path"], $params["filename"]);
  $smarty->assign('startform', $this->CreateFormStart($id, 'chmodfile', $returnid));

  $smarty->assign('filename', $this->CreateInputHidden($id, "filename", $params["filename"]));
  $smarty->assign('path', $this->CreateInputHidden($id, "path", $params["path"]));
  $smarty->assign('endform', $this->CreateFormEnd());
  $smarty->assign('newmodetext', $this->Lang("newpermissions"));

  $smarty->assign('newmode', $this->CreateInputHidden($id, "newmode", "newset"));

  $smarty->assign('modetable', $this->GetModeTable($id, $this->GetPermissions($params["path"], $params["filename"])));

  $smarty->assign('quickmodetext', $this->Lang("quickmode"));
  $smarty->assign('quickmodeinput', $this->CreateInputText($id, "quickmode", "", 3, 3));

//see template  $smarty->assign('submit', //$this->CreateInputSubmit($id, 'submit', $this->Lang('setpermissions')));
//  $smarty->assign('cancel', //$this->CreateInputSubmit($id, 'cancel', $this->Lang('cancel')));
  echo $this->ProcessTemplate('chmodfile.tpl');
}
?>
