<?php
if (!function_exists('cmsms')) exit;
if (!$this->CheckPermission('Modify Site Preferences')) exit;

$advancedmode = $this->GetPreference('advancedmode',0);
$showhiddenfiles = $this->GetPreference('showhiddenfiles',0);
$showthumbnails = $this->GetPreference('showthumbnails',1);
$iconsize=$this->GetPreference('iconsize',0);
$permissionstyle = $this->GetPreference('permissionstyle','xxx');

$tpl = $smarty->createTemplate($this->GetTemplateResource('settings.tpl')); //,null,null,$smarty);

//$tpl->assign('path',$this->CreateInputHidden($id,"path",$path)); //why?
$tpl->assign('advancedmode',$advancedmode)
 ->assign('showhiddenfiles',$showhiddenfiles)
 ->assign('showthumbnails',$showthumbnails)
 ->assign('create_thumbnails',$this->GetPreference('create_thumbnails',1));
$iconsizes = [];
$iconsizes['32px'] = $this->Lang('largeicons').' (32px)';
$iconsizes['16px'] = $this->Lang('smallicons').' (16px)';
$tpl->assign('iconsizes',$iconsizes)
 ->assign('iconsize',$this->GetPreference('iconsize','16px'));

$permstyles=[$this->Lang('rwxstyle')=>'xxxxxxxxx',$this->Lang('755style')=>'xxx'];
$tpl->assign('permstyles',array_flip($permstyles))
 ->assign('permissionstyle',$permissionstyle);

$tpl->display();
return '';
