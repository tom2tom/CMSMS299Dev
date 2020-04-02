<?php
/*
SyntaxEditing module method: upgrade
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

if (!isset($gCms)) exit;

$me = $this->GetName().'::';
$all = $this->ListEditors();

$val = cms_siteprefs::get('syntax_editor');
if ($val && startswith($val, $me)) { //only if was in this module
    $def = reset($all);
    cms_siteprefs::set('syntax_editor', $def);
}
// TODO user-preferences too?

//TODO remove any now-irrelevant-editor prefs c.f.
//$this->RemovePreference('%_source_url', true);  where % not in array_keys($all)
//$this->RemovePreference('%_theme', true);  where % not in array_keys($all)

// refresh all editor-related prefs
$base = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.';
foreach ($all as $editor => $val) {
    $n = strtolower($editor);
    $val = $this->GetPreference($n.'_source_url');
    if (!$val) {
        $val = $this->GetPreference($n.'_url', 'MIS_SING'); //try old format
        if ($val) {
            if ($val !== 'MIS_SING') {
                $this->RemovePreference($n.'_url');
                $this->SetPreference($n.'_source_url', $val);
            } else {
                $val = '';
            }
        }
    }
    if (!$val || !startswith($val, CMS_ROOT_URL)) {//only if non-site url
        $fp = $base.$editor.'.php';
        require_once $fp;
        $P = (!empty($const_prefix)) ? $const_prefix : strtoupper($editor).'_';
        $val = @constant($P.'CDN');
        if ($val !== null) {
            $this->SetPreference($n.'_source_url', $val);
        }
        $val = @constant($P.'THEME');
        if ($val !== null) {
            $this->SetPreference($n.'_theme', $val);
        }
    }
}
