<?php
/*
FileManager module action: admin_settings
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Site Preferences')) {
    exit;
}

$advancedmode = $this->GetPreference('advancedmode', 0);
$showhiddenfiles = $this->GetPreference('showhiddenfiles', 0);
$showthumbnails = $this->GetPreference('showthumbnails', 1);
$iconsize = $this->GetPreference('iconsize', 0);
$permissionstyle = $this->GetPreference('permissionstyle', 'xxx');

$tpl = $smarty->createTemplate($this->GetTemplateResource('settings.tpl')); //,null,null,$smarty);

//$tpl->assign('path',$this->CreateInputHidden($id,"path",$path)); //why?
$tpl->assign('advancedmode', $advancedmode)
    ->assign('showhiddenfiles', $showhiddenfiles)
    ->assign('showthumbnails', $showthumbnails)
    ->assign('create_thumbnails', $this->GetPreference('create_thumbnails', 1));
$iconsizes = [];
$iconsizes['32px'] = $this->Lang('largeicons').' (32px)';
$iconsizes['16px'] = $this->Lang('smallicons').' (16px)';
$tpl->assign('iconsizes', $iconsizes)
    ->assign('iconsize', $this->GetPreference('iconsize', '16px'));

$permstyles = [$this->Lang('rwxstyle') => 'xxxxxxxxx', $this->Lang('755style') => 'xxx'];
$tpl->assign('permstyles', array_flip($permstyles))
    ->assign('permissionstyle', $permissionstyle);

$tpl->display();
