<?php
/*
RichEditing module method: installation
Copyright (C) 2019-2020 Tom Phane <tomph@cmsmadesimple.org>
This file is a component of the RichEditing module for CMS Made Simple
 <http://dev.cmsmadesimple.org/projects/richedit>

This file is free software; you can redistribute it and/or modify it
under the terms of the GNU Affero General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This file is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU Affero General Public License for more details.
<https://www.gnu.org/licenses/#AGPL>
*/

if (!isset($gCms)) exit;

$me = $this->GetName();
$all = $this->ListEditors();

$mod = cms_siteprefs::get('frontendwysiwyg');
if (!$mod || $mod == $me || $mod = 'MicroTiny') {
    cms_siteprefs::set('frontendwysiwyg', $me);
    $type = key($all);
    cms_siteprefs::set('frontendwysiwyg_type', $type);
} else {
    $type = '';
}

$mod = cms_siteprefs::get('wysiwyg'); //aka 'richtext_editor'?
if (!$mod || $mod = 'MicroTiny') {
    cms_siteprefs::set('wysiwyg', $me);
}
if (!$mod || $mod == $me || $mod = 'MicroTiny') {
    if (!$type) { $type = key($all); }
    cms_siteprefs::set('wysiwyg_type', $type);
}

$theme = '';
$base = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR;
foreach ($all as $editor => $val) {
    $fp = $base.$editor.DIRECTORY_SEPARATOR.'editor-setup.php';
    require_once $fp;
    $n = strtolower($editor);
    $p = (!empty($const_prefix)) ? $const_prefix : strtoupper($editor).'_';
    $S = $me.'\\'.$editor.'\\'.$p;
    $prop = @constant($S.'CDN'); //no E_WARNING
    if ($prop !== null) {
        $this->SetPreference($n.'_source_url', $prop);
    }
    $prop  = @constant($S.'THEME');
    if ($prop !== null) {
        $this->SetPreference($n.'_theme', $prop);
        if ($val == $me.'::'.$type) {
            cms_siteprefs::set('wysiwyg_theme', $prop);
            $theme = $prop;
        }
    }
}

if (!$mod || $mod == $me || $mod = 'MicroTiny') {
    $users = UserOperations::get_instance()->GetList();
    foreach ($users as $uid => $uname) {
        $val = cms_userprefs::get_for_user($uid, 'wysiwyg'); //aka 'richtext_editor');
        if (!$val) {
            cms_userprefs::set_for_user($uid, 'wysiwyg', $me);
            cms_userprefs::set_for_user($uid, 'wysiwyg_type', $type);
            cms_userprefs::set_for_user($uid, 'wysiwyg_theme', $theme);
        }
    }
}

//TODO register handlers for events which allow user-preferences change related to this module
