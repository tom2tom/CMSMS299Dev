<?php
/*
CoreTextEditing module method: uninstallation
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

$this->RemovePreference();

$text = $this->GetName().'::';
$val = cms_siteprefs::get('syntax_editor');
if ($val && startswith($val, $text)) {
    cms_siteprefs::set('syntax_editor','');
}

$users = UserOperations::get_instance()->GetList();
foreach ($users as $uid => $uname) {
	$val = cms_userprefs::get_for_user($uid, 'syntax_editor');
	if ($val && startswith($val, $text)) {
		cms_userprefs::set_for_user($uid, 'syntax_editor', '');
	}
}

//TODO un-register handlers for events which allow user-preferences-change related to this module
