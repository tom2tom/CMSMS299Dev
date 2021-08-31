<?php
use FileManager\Utils;

//if( some worthy test fails ) exit;
if( !$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed() ) exit;
if( isset($params['cancel']) ) {
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}
$sel = $params['sel'];
if( !is_array($sel) ) {
  $sel = json_decode(rawurldecode($sel), true);
}
if( !$sel ) {
  $params['fmerror']='nofilesselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}
if( count($sel)>1 ) {
  $params['fmerror']='morethanonefiledirselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$config=cmsms()->GetConfig();
$filename=$this->decodefilename($sel[0]);
$src = cms_join_path(CMS_ROOT_PATH,Utils::get_cwd(),$filename);
if( !file_exists($src) ) {
  $params['fmerror']='filenotfound';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

include_once __DIR__.'/easyarchives/EasyArchive.class.php';
$archive = new EasyArchive();
$destdir = cms_join_path(CMS_ROOT_PATH,Utils::get_cwd());
if( !endswith($destdir,'/') ) $destdir .= '/';
$res = $archive->extract($src,$destdir);

$paramsnofiles['fmmessage']='unpacksuccess'; //strips the file data
$this->Audit('','File Manager', 'Unpacked file: '.$src);
$this->Redirect($id,'defaultadmin',$returnid,$paramsnofiles);
