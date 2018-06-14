<?php
# ModuleManager action:
# Copyright (C) 2008-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use ModuleManager\cached_request;

if (!isset($gCms)) exit;

$this->SetCurrentTab('modules');

$name = get_parameter_value($params,'name');
if( !$name ) {
  $this->SetError($this->Lang('error_insufficientparams'));
  $this->RedirectToAdminTab();
  return;
}

$version = get_parameter_value($params,'version');
if( !$version ) {
  $this->SetError($this->Lang('error_insufficientparams'));
  $this->RedirectToAdminTab();
  return;
}

$url = $this->GetPreference('module_repository');
if( !$url ) {
  $this->SetError($this->Lang('error_norepositoryurl'));
  $this->RedirectToAdminTab();
  return;
}
$url .= '/modulehelp';

$xmlfile = get_parameter_value($params,'filename');
if( !$xmlfile ) {
  $this->SetError($this->Lang('error_nofilename'));
  $this->RedirectToAdminTab();
  return;
}


$req = new cached_request();
$req->execute($url,array('name'=>$xmlfile));
$status = $req->getStatus();
$result = $req->getResult();
if( $status != 200 || $result == '' ) {
  $this->SetError($this->Lang('error_request_problem'));
  $this->RedirectToAdminTab();
  return;
}
$help = json_decode($result,true);
if( !$help ) {
  $this->SetError($this->Lang('error_nodata'));
  $this->RedirectToAdminTab();
  return;
}

$smarty->assign('title',$this->Lang('helptxt'));
$smarty->assign('moduletext',$this->Lang('nametext'));
$smarty->assign('vertext',$this->Lang('vertext'));
$smarty->assign('xmltext',$this->Lang('xmltext'));
$smarty->assign('modulename',$name);
$smarty->assign('moduleversion',$version);
$smarty->assign('xmlfile',$xmlfile);
$smarty->assign('content',$help);
$smarty->assign('back_url',$this->create_url($id,'defaultadmin',$returnid));
$smarty->assign('link_back',$this->CreateLink($id,'defaultadmin',$returnid, $this->Lang('back_to_module_manager')));

echo $this->ProcessTemplate('remotecontent.tpl');

#
# EOF
#
?>