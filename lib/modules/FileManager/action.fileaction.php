<?php
if (!isset($gCms)) exit;
if (!($this->CheckPermission('Modify Files') || $this->AdvancedAccessAllowed())) exit;

if (!isset($params['path'])) {
  $this->Redirect($id,'defaultadmin');
}
if (!FileManager\filemanager_utils::test_valid_path($params['path'])) {
  $this->Redirect($id,'defaultadmin',$returnid,['fmerror'=>'fileoutsideuploads']);
}
$path = $params['path'];

$fileaction = $params['fileaction'] ?? '';

$selfiles = [];
$seldirs = [];
$paramsnofiles = [];
//$somethingselected = false;
foreach ($params as $key=>$value) {
  if (substr($key,0,5) == 'file_') {
    $selfiles[] = $this->decodefilename(substr($key,5));
  } elseif (substr($key,0,4) == 'dir_') {
    $seldirs[] = $this->decodefilename(substr($key,4));
  } else {
    $paramsnofiles[$key] = $value;
  }
}

$selall = array_merge($seldirs,$selfiles);

// get the dirs from uploadspath
$dirlist = [];
$filerec = get_recursive_file_list($config['uploads_path'], [], -1, 'DIRS');
//$dirlist[$this->Lang('selecttargetdir')] = '-';
foreach ($filerec as $key => $value) {
  $value1 = str_replace($config['root_path'], '', $value);
  //prevent current dir from showing up
  if ($value1 == ($path . DIRECTORY_SEPARATOR)) continue;
  //Check for hidden items (assumes unix-y hiding)
  $dirs = explode(DIRECTORY_SEPARATOR, $value1);
  foreach ($dirs as $dir) {
    if ($dir !== '' && $dir[0] == '.') {
      continue 2;
    }
  }
  //not hidden, add to list
  $dirlist[$this->Slashes($value1)] = $this->Slashes($value1);
}

if (isset($params['fileactionnewdir']) || $fileaction == 'newdir') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.newdir.php');
  return;
}

if (isset($params['fileactionview']) || $fileaction == 'view') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.view.php');
  return;
}

if (isset($params['fileactionrename']) || $fileaction == 'rename') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.rename.php');
  return;
}

if (isset($params['fileactiondelete']) || $fileaction == 'delete') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.delete.php');
  return;
}

if (isset($params['fileactioncopy']) || $fileaction == 'copy') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.copy.php');
  return;
}

if (isset($params['fileactionmove']) || $fileaction == 'move') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.move.php');
  return;
}

if (isset($params['fileactionunpack']) || $fileaction == 'unpack') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.unpack.php');
  return;
}

if (isset($params['fileactionthumb']) || $fileaction == 'thumb') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.thumb.php');
  return;
}

if (isset($params['fileactionresizecrop']) || $fileaction == 'resizecrop') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.resizecrop.php');
  return;
}

if (isset($params['fileactionrotate']) || $fileaction == 'rotate') {
  include_once(__DIR__.DIRECTORY_SEPARATOR.'action.rotate.php');
  return;
}

$this->Redirect($id,'defaultadmin',$returnid,['path'=>$params['path'],'fmerror'=>'unknownfileaction']);
