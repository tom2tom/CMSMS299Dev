<?php
use FileManager\filemanager_utils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission("Modify Files") && !$this->AdvancedAccessAllowed()) exit;
if (isset($params["cancel"])) $this->Redirect($id,"defaultadmin",$returnid,$params);

$sel = $params['sel'];
if( !is_array($sel) ) $sel = json_decode(rawurldecode($sel),true);

if (count($sel)==0) {
  $params["fmerror"]="nofilesselected";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}

// decode the sellallstuff.
foreach( $sel as &$one ) {
  $one = $this->decodefilename($one);
}

// process form
$errors = array();
if( isset($params['delete']) ) {
  $advancedmode = filemanager_utils::check_advanced_mode();
  $basedir = CMS_ROOT_PATH; //TODO or $config['uploads_path'] ?
  $cwd = filemanager_utils::get_cwd();

  foreach( $sel as $file ) {
    // build complete path
    $fn = cms_join_path($basedir,$cwd,$file);
    if( !file_exists($fn) ) continue; // no error here.

    if( !is_writable($fn) ) {
      $errors[] = $this->Lang('error_notwritable',$file);
      continue;
    }

    if( is_dir($fn) ) {
      // check to make sure it's empty
      $tmp = scandir($fn);
      if( count($tmp) > 2 ) { // account for . and ..
        $errors[] = $this->Lang('error_dirnotempty',$file);
        continue;
      }
    }

    $thumb = '';
    if( filemanager_utils::is_image_file($file) ) {
      // check for thumb, make sure it's writable.
      $thumb = cms_join_path($basedir,$cwd,'thumb_'.basename($file));
      if( file_exists($fn) && !is_writable($fn) ) $errors[] = $this->Lang('error_thumbnotwritable',$file);
    }

    // at this point, we should be good to delete.
    if( is_dir($fn) ) {
      @rmdir($fn);
    } else {
      @unlink($fn);
    }
    if( $thumb != '' ) @unlink($thumb);

    $parms = array('file'=>$fn);
    if( $thumb ) $parms['thumb'] = $thumb;
    audit('',"File Manager", "Removed file: ".$fn);
    CMSMS\Events::SendEvent( 'FileManager', 'OnFileDeleted', $parms );
  } // foreach

  if( count($errors) == 0 ) {
    $paramsnofiles["fmmessage"]="deletesuccess"; //strips the file data
    $this->Redirect($id,"defaultadmin",$returnid,$paramsnofiles);
  }
} // if submit

// give everything to smarty.
if( count($errors) ) {
  $this->ShowErrors($errors);
  $smarty->assign('errors',$errors);
}
if( is_array($params['sel']) ) $params['sel'] = rawurlencode(json_encode($params['sel']));
$smarty->assign('sel',$sel);
$smarty->assign('mod',$this);
$smarty->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid,"post","",false,"",$params));
$smarty->assign('formend', $this->CreateFormEnd());

echo $this->ProcessTemplate('delete.tpl');
