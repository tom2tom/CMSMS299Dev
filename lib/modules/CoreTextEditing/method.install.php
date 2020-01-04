<?php
/*
CoreTextEditing module method: install
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
use const CoreTextEditing\Ace\ACE_CDN;
use const CoreTextEditing\Ace\ACE_THEME;
use const CoreTextEditing\CodeMirror\CM_CDN;
use const CoreTextEditing\CodeMirror\CM_THEME;

if (!isset($gCms)) exit;

$val = cms_siteprefs::get('syntax_editor');
if (!$val) {
	cms_siteprefs::set('syntax_editor', $this->GetName().'::Ace');
}
//TODO user-prefs too

require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.Ace.php';
$this->SetPreference('ace_source_url', ACE_CDN);
$this->SetPreference('ace_theme', ACE_THEME);
require_once __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'editor.CodeMirror.php';
$this->SetPreference('codemirror_source_url', CM_CDN);
$this->SetPreference('codemirror_theme', CM_THEME);
