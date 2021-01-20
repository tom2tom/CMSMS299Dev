<?php
/*
CoreTextEditing module method: uninstallation
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

$this->RemovePreference();

$text = $this->GetName().'::';
$val = cms_siteprefs::get('syntaxhighlighter');
if ($val && startswith($val, $text)) {
    cms_siteprefs::set('syntaxhighlighter','');
}

$users = UserOperations::get_instance()->GetList();
foreach ($users as $uid => $uname) {
    $val = cms_userprefs::get_for_user($uid, 'syntaxhighlighter');
    if ($val && startswith($val, $text)) {
        cms_userprefs::set_for_user($uid, 'syntaxhighlighter', '');
    }
}

//TODO un-register handlers for events which allow user-preferences-change related to this module
