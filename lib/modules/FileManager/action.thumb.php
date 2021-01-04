<?php
/*
FileManager module action: thumb
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use FileManager\Utils;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) exit;

if (isset($params['cancel'])) $this->Redirect($id,'defaultadmin',$returnid,$params);

$sel = $params['sel'];
if( !is_array($sel) ) {
  $sel = json_decode(rawurldecode($sel),true);
}
unset($params['sel']);

if (count($sel)==0) {
  $params['fmerror']='nofilesselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}
if (count($sel)>1) {
  $params['fmerror']='morethanonefiledirselected';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$advancedmode = Utils::check_advanced_mode();

$basedir=CMS_ROOT_PATH;
$filename=$this->decodefilename($sel[0]);
$src=cms_join_path($basedir,Utils::get_cwd(),$filename);
if( !file_exists($src) ) {
  $params['fmerror']='filenotfound';
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

if( isset($params['thumb']) ) {
  $thumb = Utils::create_thumbnail($src);

  if( !$thumb ) {
    $params['fmerror']='thumberror';
  }
  else {
    $params['fmmessage']='thumbsuccess';
  }
  $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$thumb = cms_join_path($basedir,Utils::get_cwd(),'thumb_'.$filename);

//
// build the form
//
$tpl = $smarty->createTemplate($this->GetTemplateResource('filethumbnail.tpl')); //,null,null,$smarty);
$tpl->assign('filename',$filename)
 ->assign('filespec',$src)
 ->assign('thumb',$thumb)
 ->assign('thumbexists',file_exists($thumb));
if( is_array($sel) ) $params['sel'] = rawurlencode(json_encode($sel));
$tpl->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid,'post','',false,'',$params))
//see DoActionBase() ->assign('mod',$this)
 ->assign('formend', $this->CreateFormEnd());

$tpl->display();
return '';
