<?php
use FileManager\Utils;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) exit;

if (isset($params['cancel'])) {
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$sel = $params['sel'];
if( !is_array($sel) ) $sel = json_decode(rawurldecode($sel),true);
if (!$sel) {
  $params['fmerror']='nofilesselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

foreach( $sel as &$one ) {
  $one = $this->decodefilename($one);
}

$config=cmsms()->GetConfig();
$cwd = Utils::get_cwd();
$dirlist = Utils::get_dirlist();
if( !$dirlist ) {
  $params['fmerror']='nodestinationdirs';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$errors = [];
$destloc = '';
if( isset($params['copy']) ) {
  $advancedmode = Utils::check_advanced_mode();
  $basedir = ( $advancedmode ) ? CMS_ROOT_PATH : $config['uploads_path'];

  $destname = '';
  $destdir = trim($params['destdir']);
  if( $destdir == $cwd && count($sel) > 1 ) $errors[] = $this->Lang('movedestdirsame');

  if( !$errors ) {
    $destloc = cms_join_path($basedir,$destdir);
    if( !is_dir($destloc) || ! is_writable($destloc) ) $errors[] = $this->Lang('invalidmovedir');
  }

  if( !$errors ) {
    if( isset($params['destname']) && count($sel) == 1 ) {
      $destname = trim($params['destname']);
      if( $destname == '' ) $errors[] = $this->Lang('invaliddestname');
    }

    if( !$errors ) {
      foreach( $sel as $file ) {
        $src = cms_join_path(Utils::get_full_cwd(),$file);
        $dest = cms_join_path($basedir,$destdir,$file);
        if( $destname ) $dest = cms_join_path($basedir,$destdir,$destname);

        if( !file_exists($src) ) {
          $errors[] = $this->Lang('filenotfound')." $file";
          continue;
        }
        if( !is_readable($src) ) {
          $errors[] = $this->Lang('insufficientpermission',$file);
          continue;
        }
        if( file_exists($dest) ) {
          $errors[] = $this->Lang('fileexistsdest',basename($dest));
          continue;
        }

        $thumb = '';
        $src_thumb = '';
        $dest_thumb = '';
        if( Utils::is_image_file($file) ) {
          $tmp = 'thumb_'.$file;
          $src_thumb = cms_join_path($basedir,$cwd,$tmp);
          $dest_thumb = cms_join_path($basedir,$destdir,$tmp);
          if( $destname ) $dest_thumb = cms_join_path($basedir,$destdir,'thumb_'.$destname);

          if( file_exists($src_thumb) ) {
            $thumb = $tmp;
            // have a thumbnail
            if( !is_readable($src_thumb) ) {
              $errors[] = $this->Lang('insufficientpermission',$thumb);
              continue;
            }
            if( file_exists($dest_thumb) ) {
              $errors[] = $this->Lang('fileexistsdest',$thumb);
              continue;
            }
          }
        }

        // here we can move the file/dir
        $res = copy($src,$dest);
        if( !$res ) {
          $errors[] = $this->Lang('copyfailed',$file);
          continue;
        }
        if( $thumb ) {
          $res = copy($src_thumb,$dest_thumb);
          if( !$res ) {
            $errors[] = $this->Lang('copyfailed',$thumb);
            continue;
          }
        }
      } // foreach
    } // no errors
  } // no errors

  if( !$errors ) {
    $paramsnofiles['fmmessage']='copysuccess'; //strips the file data
    $this->Redirect($id,'defaultadmin',$returnid,$paramsnofiles);
  }
} // submit

if( $errors ) $this->ShowErrors($errors);
if( is_array($params['sel']) ) $params['sel'] = rawurlencode(json_encode($params['sel']));

$tpl = $smarty->createTemplate($this->GetTemplateResource('copy.tpl')); //,null,null,$smarty);

$tpl->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid,'post','',false,'',$params))
 ->assign('formend', $this->CreateFormEnd())
 ->assign('cwd','/'.$cwd)
 ->assign('dirlist',$dirlist)
 ->assign('sel',$sel);
//see DoActionBase() ->assign('mod',$this);

$tpl->display();
