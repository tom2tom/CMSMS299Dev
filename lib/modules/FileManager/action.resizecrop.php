<?php
use FileManager\Utils;
use FileManager\imageEditor;

if (!isset($gCms)) exit;
if (!$this->CheckPermission("Modify Files") && !$this->AdvancedAccessAllowed()) exit;

if (isset($params["cancel"])) $this->Redirect($id,"defaultadmin",$returnid,$params);

$sel = $params['sel'];
if( !is_array($sel) ) $sel = json_decode(rawurldecode($sel),true);
unset($params['sel']);

if (count($sel)==0) {
  $params["fmerror"]="nofilesselected";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}
if (count($sel)>1) {
  $params["fmerror"]="morethanonefiledirselected";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}

$config = cmsms()->getConfig();
$basedir = CMS_ROOT_PATH;
$filename=$this->decodefilename($sel[0]);
$src = cms_join_path($basedir,Utils::get_cwd(),$filename);
if( !file_exists($src) ) {
  $params["fmerror"]="filenotfound";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}
$imageinfo = getimagesize($src);
if( !$imageinfo || !isset($imageinfo['mime']) || !startswith($imageinfo['mime'],'image') ) {
    $this->SetError($this->Lang('filenotimage'));
    $this->Redirect($id,"defaultadmin",$returnid);
}
if( !is_writable($src) ) {
    $this->SetError($this->Lang('filenotimage'));
    $this->Redirect($id,"defaultadmin",$returnid);
}

//
// handle submit action(s).
//

if(empty($params['reset'])
   && !empty($params['cx']) && !empty($params['cy'])
   && !empty($params['cw']) && !empty($params['ch'])
   && !empty($params['iw']) && !empty($params['ih'])) {

  //Get the mimeType
  $mimeType = imageEditor::getMime($src);

  //Open new Instance
  $instance = imageEditor::open($src);

  //Resize it if necessary
  if( !empty($params['iw']) && !empty($params['ih']) ) {
      $instance = imageEditor::resize($instance, $mimeType, $params['iw'], $params['ih']);
  }

  //Crop it if necessary
  if( !empty($params['cx']) && !empty($params['cy']) && !empty($params['cw']) && !empty($params['ch']) ) {
      $instance = imageEditor::crop($instance, $mimeType, $params['cx'], $params['cy'], $params['cw'], $params['ch']);
  }

  //Save it
  $res = imageEditor::save($instance, $src, $mimeType);
  if( $this->GetPreference('create_thumbnails') ) Utils::create_thumbnail($src);

  $this->Redirect($id,"defaultadmin",$returnid);
}

if( is_array($sel) ) $params['sel'] = rawurlencode(json_encode($sel));
//
// build the form
//
$tpl = $smarty->createTemplate($this->GetTemplateResource('pie.tpl'),null,null,$smarty);

$tpl->assign('formstart',$this->CreateFormStart($id,'resizecrop',$returnid,'post','',false,'',$params))
 ->assign('formend',$this->CreateFormEnd())
 ->assign('filename',$filename);
$url = Utils::get_cwd_url()."/$filename";
$tpl->assign('image',$url)
 ->assign('image_width',$imageinfo[0]);

$tpl->display();

