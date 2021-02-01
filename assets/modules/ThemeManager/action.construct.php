<?php
/*
Construct-theme action for CMS Made Simple module: ThemeManager.
Copyright (C) 2005-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Refer to licence and other details at the top of file ThemeManager.module.php
*/

use CMSMS\App;

if (!isset($gCms) || !($gCms instanceof App)) {
	exit;
}

if (!$this->CheckPermission('Modify Themes')) { //OR WHATEVER
	$this->SetError($this->Lang('nopermission'));
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

$tpl = $smarty->createTemplate($this->GetTemplateResource('collect.tpl')); //,null,null,$smarty);
/*
$tpl->assign([
 'activateurl' => $activateurl ?? null,
 'addurl' => $addurl ?? null,
 'contextmenus' => $menus ?? null,
 'extraparms' => $extras,
 'iconadd' => $icon_add ?? null,
 'iconfalse' => $icon_false ?? null,
 'icontrue' => $icon_true ?? null,
 'importurl' => $importurl ?? null,
 'pdev' => $pdev,
 'pmod' => $pmod,
 'psee' => $psee,
 'pset' => $pset,
 'tab' => $seetab,
 'themes' => $items ?? null,
 'themeoptions' => $sel ?? null,
 'current_theme' => $current ?? null,
]);
*/

$tpl->Display();
return '';
