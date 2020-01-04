<?php
/*
CoreTextEditing module action: defaultadmin
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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
use const CoreTextEditing\Ace\ACE_CDN;
use const CoreTextEditing\Ace\ACE_THEME;
use const CoreTextEditing\CodeMirror\CM_CDN;
use const CoreTextEditing\CodeMirror\CM_THEME;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) exit;

if (isset($params['apply'])) {
    $url = filter_var($params['ace_url'], FILTER_SANITIZE_URL); //TODO handle error
    $this->SetPreference('ace_source_url', $url);
    $this->SetPreference('ace_theme', $params['ace_theme']);
    $url = filter_var($params['codemirror_url'], FILTER_SANITIZE_URL); //TODO handle error
    $this->SetPreference('codemirror_source_url', $url);
    $this->SetPreference('codemirror_theme', $params['codemirror_theme']);
    $this->ShowMessage($this->Lang('settings_success'));
}

require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.Ace.php';
$ace_url = $this->GetPreference('ace_source_url', ACE_CDN);
$ace_theme = $this->GetPreference('ace_theme', ACE_THEME);
require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.CodeMirror.php';
$codemirror_url = $this->GetPreference('codemirror_source_url', CM_CDN);
$codemirror_theme = $this->GetPreference('codemirror_theme', CM_THEME);

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl'),null,null,$smarty);

$tpl->assign('info', $this->Lang('info_settings'))
 ->assign('form_start', $this->CreateFormStart($id, 'defaultadmin'));
if (!empty($warning)) {
    $tpl->assign('warning', $warning); //optional
}
$tpl->assign([
    'ace_url' => $ace_url,
    'ace_theme' => $ace_theme,
    'codemirror_url' => $codemirror_url,
    'codemirror_theme' => $codemirror_theme,
]);

$tpl->display();
