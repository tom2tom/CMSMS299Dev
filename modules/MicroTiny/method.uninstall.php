<?php
/*
MicroTiny module uninstallation process
Copyright (C) 2009-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of the Microtiny module for CMS Made Simple
<http://dev.cmsmadesimple.org/projects/microtiny>

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

$this->RemovePermission('MicroTiny View HTML Source');

$me = $this->GetName();
$val = AppParams::get('wysiwyg');
if ($val == $me) {
	AppParams::set('wysiwyg', '');
}
$val = AppParams::get('frontendwysiwyg');
if ($val == $me) {
	AppParams::set('frontendwysiwyg', '');
}

$users = SingleItem::UserOperations()->GetList();
foreach ($users as $uid => $uname) {
	$val = UserParams::get_for_user($uid, 'wysiwyg');
	if ($val == $me) {
		UserParams::set_for_user($uid, 'wysiwyg', '');
	}
}
