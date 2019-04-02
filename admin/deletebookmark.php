<?php
#procedure to delete a user's bookmark
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\BookmarkOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

if (!isset($_GET['bookmark_id'])) {
	return;
}

$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

$bookmark_id = (int)$_GET['bookmark_id'];
$markobj = (new BookmarkOperations())->LoadBookmarkByID($bookmark_id);

if ($markobj) {
	$userid = get_userid();
	if ($userid != $markobj->user_id && !check_permission($userid, 'Manage My Bookmarks')) { //TODO or 'Manage Bookmarks'
		cms_utils::get_theme_object()->ParkNotice('error', lang('needpermissionto', '"Manage My Bookmarks"'));
		redirect('listbookmarks.php'.$urlext);
	}

	if (!$markobj->Delete()) {
		cms_utils::get_theme_object()->ParkNotice('error', lang('failure'));
	}
} else {
	cms_utils::get_theme_object()->ParkNotice('error', lang('invalid'));
}

redirect('listbookmarks.php'.$urlext);
