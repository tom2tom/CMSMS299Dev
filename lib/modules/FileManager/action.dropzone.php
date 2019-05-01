<?php

use FileManager\Utils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Files')) return;

if( isset($params['template']) ) {
    $template = trim($params['template']);
    if( !endswith($template,'.tpl') )  $template .= '.tpl';
} else {
    $template = 'dropzone.tpl';
}
$tpl = $smarty->createTemplate($this->GetTemplateResource($template),null,null,$smarty);

//see DoActionBase()$tpl->assign('mod',$this)
// ->assign('actionid',$id);

if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    $tpl->assign('is_ie',1);
}
$tpl->assign('formstart',$this->CreateFormStart($id,'upload',$returnid,'post','multipart/form-data'))
 ->assign('formend',$this->CreateFormEnd());
$post_max_size = Utils::str_to_bytes(ini_get('post_max_size'));
$upload_max_filesize = Utils::str_to_bytes(ini_get('upload_max_filesize'));
$tpl->assign('max_chunksize',min($upload_max_filesize,$post_max_size-1024))
 ->assign('action_url',$this->create_url($id,'upload',$returnid))
 ->assign('prompt_dropfiles',$this->Lang('prompt_dropfiles'))

 ->assign('chdir_formstart',$this->CreateFormStart($id,'changedir',$returnid,'','',[
  'id'=>'chdir_form',
  'class'=>'cms_form',
  'path'=>$cwd,
  'ajax'=>1
]))
 ->assign('chdir_url',str_replace('&amp;','&',$this->create_url($id,'changedir',$returnid)).'&'.CMS_JOB_KEY.'=1');

$advancedmode = $this->GetPreference('advancedmode',0);
if( strlen($advancedmode) > 1 ) $advancedmode = 0;

// get a folder list...
  $cwd = Utils::get_cwd();
  $tpl->assign('cwd',$cwd);

  $startdir = $config['uploads_path'];
  if( $this->AdvancedAccessAllowed() && $advancedmode ) $startdir = CMS_ROOT_PATH;

    // now get a simple list of all of the directories we have 'write' access to.
  $basedir = dirname($startdir);
  function get_dirs($startdir,$prefix = DIRECTORY_SEPARATOR)
  {
    $res = [];
    if( !is_dir($startdir) ) return;

    global $showhiddenfiles;
    $dh = opendir($startdir);
    while( false !== ($entry = readdir($dh)) ) {
      if( $entry == '.' ) continue;
      if( $entry == '..' ) continue;
      $full = cms_join_path($startdir,$entry);
      if( !is_dir($full) ) continue;
      if( !is_readable($full) ) continue;
      if( !$showhiddenfiles && ($entry[0] == '.' || $entry[0] == '_') ) continue;

      if( $entry == '.svn' || $entry == '.git' ) continue;
      if( is_writable($full) ) $res[$prefix.$entry] = $prefix.$entry;
      $tmp = get_dirs($full,$prefix.$entry.DIRECTORY_SEPARATOR);
      if( $tmp ) $res = array_merge($res,$tmp);
    }
    closedir($dh);
    return $res;
  }

  $output = get_dirs($startdir,DIRECTORY_SEPARATOR.basename($startdir).DIRECTORY_SEPARATOR);
  $output['/'.basename($startdir)] = DIRECTORY_SEPARATOR.basename($startdir);
  if( $output ) {
    ksort($output);
    $tpl->assign('dirlist',$output);
  }

$tpl->display();

