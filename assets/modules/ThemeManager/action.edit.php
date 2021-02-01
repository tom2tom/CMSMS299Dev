<?php
/*
Edit-theme action for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

use CMSMS\App;
use CMSMS\FormUtils;

if (!isset($gCms) || !($gCms instanceof App)) {
	exit;
}

if (!$this->CheckPermission('Modify Themes')) { //OR WHATEVER
	$this->SetError($this->Lang('nopermission'));
	$this->RedirectToAdminTab('themes');
}

if (isset($params['cancel'])) {
	$this->RedirectToAdminTab('themes');
}

if (isset($params['submit'])) {
	//TODO save stuff
	$this->RedirectToAdminTab('themes');
}

//$themename = $params['TODO'];

/*
show:

metadata
   name

list contents

templates
stylesheets
media
fonts
tags?
   regular
   user
   module

add / edit / delete selected item(s), clicked item in each category 

add
   new
   clone + edit
   DnD from ?
   upload

edit
   syntax
   visual designer (DnD) GUI for templates
       

image viewer
*/
//$menus = [];
//$templates = [];
//$stylers = [];
//$fonts = [];
//$medias = [];
//$scripts = [];

$help_menu = $this->Lang('help_menu');
$title_created = $this->Lang('created');
$title_custom = $this->Lang('custom');
$title_desc = $this->Lang('description');
$title_modified = $this->Lang('modified');
$title_name = $this->Lang('name');
$title_pages = $this->Lang('usage');

$seetab = 'main';
//$starter = $this->CreateFormStart($id, 'defaultadmin', $returnid);
$starter = FormUtils::create_form_start($this, ['id' => $id, 'action' => 'edit']);

$tpl = $smarty->createTemplate($this->GetTemplateResource('edit.tpl')); //,null,null,$smarty);

$tpl->assign([
 'codeitems' => $scripts ?? null,
 'fontitems' => $fonts ?? null,
 'mediaitems' => $medias ?? null,
 'styleitems' => $stylers ?? null,
 'tplitems' => $templates ?? null,
 'contextmenus' => $menus ?? null,
 'form_start' => $starter,
 'tab' => $seetab,
 'help_menu' => $help_menu,
 'title_created' => $title_created,
 'title_custom' => $title_custom,
 'title_desc' => $title_desc,
 'title_modified' => $title_modified,
 'title_name' => $title_name,
 'title_pages' => $title_pages,
]);

$tpl->Display();
return '';
