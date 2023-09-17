<?php
/*
Procedure to delete a user's bookmark
Copyright (C) 2004-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\BookmarkOperations;
use CMSMS\Lone;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

if (!isset($_GET['bookmark_id'])) {
	return;
}

$urlext = get_secure_param();

$bookmark_id = (int)$_GET['bookmark_id'];
$markobj = (new BookmarkOperations())->LoadBookmarkByID($bookmark_id);

if ($markobj) {
	$userid = get_userid();
	if ($userid != $markobj->user_id && !check_permission($userid, 'Manage My Bookmarks')) { //TODO or 'Manage Bookmarks'
		Lone::get('Theme')->ParkNotice('error', _la('needpermissionto', '"Manage My Bookmarks"'));
		redirect('listbookmarks.php'.$urlext);
	}

	if (!$markobj->Delete()) {
		Lone::get('Theme')->ParkNotice('error', _la('failure'));
	}
} else {
	Lone::get('Theme')->ParkNotice('error', _la('invalid'));
}

redirect('listbookmarks.php'.$urlext);
