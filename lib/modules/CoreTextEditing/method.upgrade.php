<?php
/*
CoreTextEditing module method: upgrade
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

if (!isset($gCms)) exit;

$me = $this->GetName().'::';
$all = $this->ListEditors();

$val = cms_siteprefs::get('syntaxhighlighter');
if ($val && startswith($val, $me)) { //only if was in this module
    $def = reset($all);
    cms_siteprefs::set('syntaxhighlighter', $def);
}
// TODO user-preferences too?

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
        $val = defined($P.'CDN') ? constant($P.'CDN') : null;
        if ($val) {
            $this->SetPreference($n.'_source_url', $val);
        }
        $val = defined($P.'THEME') ? constant($P.'THEME') : null;
        if ($val) {
            $this->SetPreference($n.'_theme', $val);
        }
    }
}
//TODO remove any now-irrelevant-editor prefs
