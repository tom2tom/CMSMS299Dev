<?php
/*
RichEditing module action: defaultadmin
Copyright (C) 2019-2020 Tom Phane <tomph@cmsmadesimple.org>
This file is a component of the RichEditing module for CMS Made Simple
 <http://dev.cmsmadesimple.org/projects/richedit>

This file is free software; you can redistribute it and/or modify it
under the terms of the GNU Affero General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This file is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU Affero General Public License for more details.
<https://www.gnu.org/licenses/#AGPL>
*/

use CMSMS\LangOperations;

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Site Preferences')) exit;

$all = $this->ListEditors();

if (isset($params['apply'])) {
    foreach ($all as $editor => $val) {
        $n = strtolower($editor);
        $key = $n.'_url';
        if (isset($params[$key])) {
            $url = filter_var($params[$key], FILTER_SANITIZE_URL); //TODO handle error
            $this->SetPreference($n.'_source_url', $url);
        }
        if (isset($params[$key])) {
            $key = $n.'_theme';
            $this->SetPreference($key, trim($params[$key]));
        }
    }
}

$tpl = $smarty->createTemplate($this->GetTemplateResource('adminpanel.tpl'),null,null,$smarty);

$tpl->assign('info', $this->Lang('info_settings'))
  ->assign('form_start', $this->CreateFormStart($id, 'defaultadmin'));
if (!empty($warning)) {
    $tpl->assign('warning', $warning); //optional
}

$editors = [];
foreach ($all as $editor => $val) {
    $one = new stdClass();
    $n = strtolower($editor);
    $u = $this->GetPreference($n.'_source_url', 'MIS_SING');
    if ($u != 'MIS_SING') {
        $one->urlkey = $n.'_url'; //element name, lang key
        $one->urlval = $u;
        $one->urlhelp = $n.'_helpcdn'; //lang key for popup help
    }
    $key = $n.'_theme';
    $u = $this->GetPreference($key, 'MIS_SING');
    if ($u != 'MIS_SING') {
        $one->themekey = $key; //element name, lang key
        $one->themeval = $this->GetPreference($key);
        $one->themehelp = $n.'_helptheme';
    }
    $editors[] = $one;
}
$tpl->assign('editors', $editors);

$tpl->display();
return '';
