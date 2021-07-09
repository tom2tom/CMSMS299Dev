<?php
/*
ModuleManager action: show module help
Copyright (C) 2008-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.    See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use ModuleManager\cached_request;

if (!isset($gCms)) exit;

$this->SetCurrentTab('modules');

$name = $params['name'] ?? '';
if( !$name ) {
    $this->SetError($this->Lang('error_insufficientparams'));
    $this->RedirectToAdminTab();
}

$version = $params['version'] ?? '';
if( !$version ) {
    $this->SetError($this->Lang('error_insufficientparams'));
    $this->RedirectToAdminTab();
}

$url = $this->GetPreference('module_repository');
if( !$url ) {
    $this->SetError($this->Lang('error_norepositoryurl'));
    $this->RedirectToAdminTab();
}
$url .= '/modulehelp';

$xmlfile = $params['filename'] ?? '';
if( !$xmlfile ) {
    $this->SetError($this->Lang('error_nofilename'));
    $this->RedirectToAdminTab();
}

$req = new cached_request();
$req->execute($url,['name'=>$xmlfile]);
$status = $req->getStatus();
$result = $req->getResult();
if( $status != 200 || $result == '' ) {
    $this->SetError($this->Lang('error_request_problem'));
    $this->RedirectToAdminTab();
}
$help = json_decode($result,true);
if( !$help ) {
    $this->SetError($this->Lang('error_nodata'));
    $this->RedirectToAdminTab();
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('remotecontent.tpl')); //,null,null,$smarty);

$tpl->assign('title',$this->Lang('helptxt'))
 ->assign('moduletext',$this->Lang('nametext'))
 ->assign('vertext',$this->Lang('vertext'))
 ->assign('xmltext',$this->Lang('xmltext'))
 ->assign('modulename',$name)
 ->assign('moduleversion',$version)
 ->assign('xmlfile',$xmlfile)
 ->assign('content',$help)
 ->assign('back_url',$this->create_url($id,'defaultadmin',$returnid))
 ->assign('link_back',$this->CreateLink($id,'defaultadmin',$returnid, $this->Lang('back_to_module')));

$tpl->display();
return '';
