<?php

use CMSMS\Events;
use FileManager\Utils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) exit;
if (isset($params['cancel'])) $this->Redirect($id,'defaultadmin',$returnid,$params);

$sel = $params['sel'];
if( !is_array($sel) ) $sel = json_decode(rawurldecode($sel),true);

if (count($sel)==0) {
  $params['fmerror']='nofilesselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

// decode the sellallstuff.
foreach( $sel as &$one ) {
  $one = $this->decodefilename($one);
}

// process form
$errors = [];
if( isset($params['delete']) ) {
  $advancedmode = Utils::check_advanced_mode();
  $basedir = CMS_ROOT_PATH; //TODO or $config['uploads_path'] ?
  $cwd = Utils::get_cwd();

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
    if( Utils::is_image_file($file) ) {
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

    $parms = ['file'=>$fn];
    if( $thumb ) $parms['thumb'] = $thumb;
    audit('','File Manager', 'Removed file: '.$fn);
    Events::SendEvent( 'FileManager', 'OnFileDeleted', $parms );
  } // foreach

  if( count($errors) == 0 ) {
    $paramsnofiles['fmmessage']='deletesuccess'; //strips the file data
    $this->Redirect($id,'defaultadmin',$returnid,$paramsnofiles);
  }
} // if submit

// give everything to smarty
$tpl = $smarty->createTemplate($this->GetTemplateResource('delete.tpl'),null,null,$smarty);

if( $errors ) {
  $this->ShowErrors($errors);
  $tpl->assign('errors',$errors);
}
if( is_array($params['sel']) ) $params['sel'] = rawurlencode(json_encode($params['sel']));

$tpl->assign('sel',$sel)
//see DoActionBase() ->assign('mod',$this)
 ->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid,'post','',false,'',$params))
 ->assign('formend', $this->CreateFormEnd());

$tpl->display();
return '';
