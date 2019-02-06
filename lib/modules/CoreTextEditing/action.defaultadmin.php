<?php
/*
CoreTextEditing module action: defaultadmin
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
    $url = filter_var($params['ace_cdn'], FILTER_SANITIZE_URL); //TODO handle error
    $this->SetPreference('ace_cdn', $url);
    $this->SetPreference('ace_theme', $params['ace_theme']);
    $url = filter_var($params['codemirror_cdn'], FILTER_SANITIZE_URL); //TODO handle error
    $this->SetPreference('codemirror_cdn', $url);
    $this->SetPreference('codemirror_theme', $params['codemirror_theme']);
    $this->ShowMessage($this->Lang('settings_success'));
}

$ace_cdn = $this->GetPreference('ace_cdn', CoreTextEditing::ACE_CDN);
$ace_theme = $this->GetPreference('ace_theme', CoreTextEditing::ACE_THEME);
$codemirror_cdn = $this->GetPreference('codemirror_cdn', CoreTextEditing::CM_CDN);
$codemirror_theme = $this->GetPreference('codemirror_theme', CoreTextEditing::CM_THEME);

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl'),null,null,$smarty);

$tpl->assign('info', $this->Lang('info_settings'))
 ->assign('form_start', $this->CreateFormStart($id, 'defaultadmin'));
if (!empty($warning)) {
    $tpl->assign('warning', $warning); //optional
}
$tpl->assign([
    'ace_cdn' => $ace_cdn,
    'ace_theme' => $ace_theme,
    'codemirror_cdn' => $codemirror_cdn,
    'codemirror_theme' => $codemirror_theme,
]);

$tpl->display();
