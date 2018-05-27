<?php
# FileManager. A plugin for CMS - CMS Made Simple
# Copyright (c) 2006-08 by Morten Poulsen <morten@poulsen.org>
#
#CMS - CMS Made Simple
#(c)2004 by Ted Kulp (wishy@users.sf.net)
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use FileManager\filemanager_utils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission("Modify Files") && !$this->AdvancedAccessAllowed()) exit;

if (isset($params["cancel"])) $this->Redirect($id,"defaultadmin",$returnid,$params);

$sel = $params['sel'];
if( !is_array($sel) ) {
  $sel = json_decode(rawurldecode($sel),true);
}
unset($params['sel']);

if (count($sel)==0) {
  $params["fmerror"]="nofilesselected";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}
if (count($sel)>1) {
  $params["fmerror"]="morethanonefiledirselected";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}

$advancedmode = filemanager_utils::check_advanced_mode();

$basedir=CMS_ROOT_PATH;
$filename=$this->decodefilename($sel[0]);
$src=cms_join_path($basedir,filemanager_utils::get_cwd(),$filename);
if( !file_exists($src) ) {
  $params["fmerror"]="filenotfound";
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}

if( isset($params['thumb']) ) {
  $thumb = filemanager_utils::create_thumbnail($src);

  if( !$thumb ) {
    $params["fmerror"]="thumberror";
  }
  else {
    $params["fmmessage"]="thumbsuccess";
  }
  $this->Redirect($id,"defaultadmin",$returnid,$params);
}

$thumb = cms_join_path($basedir,filemanager_utils::get_cwd(),'thumb_'.$filename);

//
// build the form
//
$smarty->assign('filename',$filename);
$smarty->assign('filespec',$src);
$smarty->assign('thumb',$thumb);
$smarty->assign('thumbexists',file_exists($thumb));
if( is_array($sel) ) $params['sel'] = rawurlencode(json_encode($sel));
$smarty->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid,"post","",false,"",$params));
$smarty->assign('mod',$this);
$smarty->assign('formend', $this->CreateFormEnd());
echo $this->ProcessTemplate('filethumbnail.tpl');
