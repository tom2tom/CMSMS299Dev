<?php
/*
SyntaxEditing module method: installation
Copyright (C) 2019-2020 Tom Phane <tomph@cmsmadesimple.org>
This file is a component of the RichEditing module for CMS Made Simple
 <http://dev.cmsmadesimple.org/projects/syntaxedit>

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

$mod = cms_siteprefs::get('syntax_editor');
if (!$mod) {
    cms_siteprefs::set('syntax_editor', $me);
}
if (!$mod || $mod == $me) {
    $type = key($all);
    cms_siteprefs::set('syntax_type', $type);
}

$theme = '';
$base = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.';
foreach ($all as $editor => $val) {
    $fp = $base.$editor.'.php';
    require_once $fp;
    $n = strtolower($editor);
    $p = (!empty($const_prefix)) ? $const_prefix : strtoupper($editor).'_';
    $S = $me.'\\'.$editor.'\\'.$p;
    $prop = @constant($S.'CDN'); //no E_WARNING
    if ($prop !== null) {
        $this->SetPreference($n.'_source_url', $prop);
    }
    $prop = @constant($S.'THEME');
    if ($prop !== null) {
        $this->SetPreference($n.'_theme', $prop);
        if ($val == $me.'::'.$prop) {
            cms_siteprefs::set('syntax_theme', $prop);
            $theme = $prop;
        }
    }
}

if (!$mod || $mod == $me) {
    $users = UserOperations::get_instance()->GetList();
    foreach ($users as $uid => $uname) {
        $val = cms_userprefs::get_for_user($uid, 'syntax_editor');
        if (!$val) {
            cms_userprefs::set_for_user($uid, 'syntax_editor', $me);
            cms_userprefs::set_for_user($uid, 'syntax_type', $type);
            cms_userprefs::set_for_user($uid, 'syntax_theme', $theme);
        }
    }
}

//TODO register handlers for events which allow user-preferences change related to this module
