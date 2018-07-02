<?php
/*
CoreTextEditing module action: defaultadmin
Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) exit;

if (isset($params['apply'])) {
	//SAVE STUFF
/*
    $this->SetPreference('ace_cdn', CoreTextEditing::ACE_CDN);
    $this->SetPreference('ace_theme', CoreTextEditing::ACE_THEME);
    $this->SetPreference('codemirror_cdn', CoreTextEditing::CM_CDN);
    $this->SetPreference('codemirror_theme', CoreTextEditing::CM_THEME);
*/	
}

//GET STUFF
$this->GetPreference('ace_cdn', CoreTextEditing::ACE_CDN);
$this->GetPreference('ace_theme', CoreTextEditing::ACE_THEME);
$this->GetPreference('codemirror_cdn', CoreTextEditing::CM_CDN);
$this->GetPreference('codemirror_theme', CoreTextEditing::CM_THEME);

$smarty->assign('header', $header);
//if () $smarty->assign('warning', $warning); //optional
$smarty->assign('form_start', $this->Create('TODO'));

//other options

$items = [];
foreach ($X as $editor) {
	$one = new stdClass();
	$one->label = '';
	$one->name = '';
	$one->active = '';
	$one->help = '';
	$items[] = $one;
}
if ($items) {
	$smarty->assign('items', $items);
}

echo $this->processTemplate('adminpanel.tpl');
