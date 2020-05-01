<?php
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

foreach( $sel as &$one ) {
    $one = $this->decodefilename($one);
}

$config=cmsms()->GetConfig();
$cwd = Utils::get_cwd();
$dirlist = Utils::get_dirlist();
if( !count($dirlist) ) {
    $params['fmerror']='nodestinationdirs';
    $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$errors = [];
$destloc = '';
if( isset($params['move']) ) {
    $destdir = trim($params['destdir']);
    if( $destdir == $cwd ) $errors[] = $this->Lang('movedestdirsame');

    $advancedmode = Utils::check_advanced_mode();
    $basedir = ( $advancedmode ) ?  CMS_ROOT_PATH : $config['uploads_path'];

    if( count($errors) == 0 ) {
        $destloc = cms_join_path($basedir,$destdir);
        if( !is_dir($destloc) || ! is_writable($destloc) ) $errors[] = $this->Lang('invalidmovedir');
    }

    if( count($errors) == 0 ) {
        foreach( $sel as $file ) {
            $src = cms_join_path(CMS_ROOT_PATH,$cwd,$file);
            $dest = cms_join_path($basedir,$destdir,$file);

            if( !file_exists($src) ) {
                $errors[] = $this->Lang('filenotfound')." $file";
                continue;
            }
            if( !is_readable($src) ) {
                $errors[] = $this->Lang('insufficientpermission',$file);
                continue;
            }
            if( file_exists($dest) ) {
                $errors[] = $this->Lang('fileexistsdest',$file);
                continue;
            }
            if( is_dir($src) && startswith($dest,$src) ) {
                $errors[] = $this->Lang('filemovesame',$file);
                continue;
            }

            $thumb = '';
            $src_thumb = '';
            $dest_thumb = '';
            if( Utils::is_image_file($file) ) {
                $tmp = 'thumb_'.$file;
                $src_thumb = cms_join_path(CMS_ROOT_PATH,$cwd,$tmp);
                $dest_thumb = cms_join_path($basedir,$destdir,$tmp);

                if( file_exists($src_thumb) ) {
                    // have a thumbnail
                    $thumb = $tmp;
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
            $res = rename($src,$dest);
            if( !$res ) {
                $errors[] = $this->Lang('movefailed',$file);
                continue;
            }
            if( $thumb ) {
                $res = rename($src_thumb,$dest_thumb);
                if( !$res ) {
                    $errors[] = $this->Lang('movefailed',$thumb);
                    continue;
                }
            }
        } // foreach
    } // no errors

    if( count($errors) == 0 ) {
        $paramsnofiles['fmmessage']='movesuccess'; //strips the file data
        $this->Redirect($id,'defaultadmin',$returnid,$paramsnofiles);
    }
} // submit

if( $errors ) $this->ShowErrors($errors);
if( is_array($params['sel']) ) $params['sel'] = rawurlencode(json_encode($params['sel']));

$tpl = $smarty->createTemplate($this->GetTemplateResource('move.tpl')); //,null,null,$smarty);
$tpl->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', $params))
 ->assign('formend', $this->CreateFormEnd())
 ->assign('cwd','/'.$cwd)
 ->assign('dirlist',$dirlist)
 ->assign('sel',$sel);
//see DoActionBase() ->assign('mod',$this);

$tpl->display();
return '';
