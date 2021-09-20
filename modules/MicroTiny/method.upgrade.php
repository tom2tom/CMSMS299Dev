<?php
/*
MicroTiny module upgrade process
Copyright (C) 2009-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppParams;
use CMSMS\SingleItem;
use CMSMS\UserParams;

if( version_compare($oldversion,'1.1') < 0 ) {
	$this->CreatePermission('MicroTiny View HTML Source','MicroTiny View HTML Source');
}

if( version_compare($oldversion,'2.0') < 0 ) {
	$this->RemovePreference();
	$this->DeleteTemplate();
	include_once(__DIR__.'/method.install.php');
}

if( version_compare($oldversion,'2.3') < 0 ) {
	$profiles = $this->ListPreferencesByPrefix('profile_');
	foreach ($profiles as $name) {
		$full = 'profile_'.$name;
		$val = $this->GetPreference($full);
		if( $val ) {
			$data = unserialize($val, ['allowed_classes' => false]);
			$val = json_encode($data, JSON_NUMERIC_CHECK|JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
			$this->SetPreference($full, $val);
		}
	}

	$me = $this->GetName();
	$val = AppParams::get('wysiwyg');
	if (!$val) {
		AppParams::set('wysiwyg', $me);
	}
	$val = AppParams::get('frontendwysiwyg');
	if (!$val) {
		AppParams::set('frontendwysiwyg', $me);
	}

	$users = SingleItem::UserOperations()->GetList();
	foreach ($users as $uid => $uname) {
		$val = UserParams::get_for_user($uid, 'wysiwyg');
		if (!$val) {
			UserParams::set_for_user($uid, 'wysiwyg', $me);
		}
	}
}
