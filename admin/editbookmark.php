<?php
#procedure to modify a user's bookmark
#Copyright (C) 2004-2017 Ted Kulp <ted@cmsmadesimple.org>
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.	See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

check_login();

if (isset($_POST['cancel'])) {
	redirect('listbookmarks.php'.$urlext);
	return;
}

$title = '';
$url = '';
$error = '';
if (isset($_GET['bookmark_id'])) {
	$bookmark_id = (int)$_GET['bookmark_id'];
} else {
	$bookmark_id = -1;
}

if (isset($_POST['editbookmark'])) {
	$bookmark_id = (int)$_POST['bookmark_id'];
	$title = trim(cleanValue($_POST['title']));
	$url = trim(cleanValue($_POST['url']));

	$validinfo = true;
	if ($title === '') {
		$validinfo = false;
		$error .= '<li>'.lang('nofieldgiven', lang('title')).'</li>';
	}
	if ($url === '') {
		$validinfo = false;
		$error .= '<li>'.lang('nofieldgiven', lang('url')).'</li>';
	}

	if ($validinfo) {
		cmsms()->GetBookmarkOperations();
		$markobj = new Bookmark();
		$markobj->bookmark_id = $bookmark_id;
		$markobj->title = $title;
		$markobj->url = $url;
		$markobj->user_id = get_userid();

		if ($markobj->save()) {
			redirect('listbookmarks.php'.$urlext);
			return;
		} else {
			$error .= '<li>'.lang('errorupdatingbookmark').'</li>';
		}
	}
} elseif ($bookmark_id != -1) {
	$db = cmsms()->GetDb();
	$query = 'SELECT title,url FROM '.CMS_DB_PREFIX.'admin_bookmarks WHERE bookmark_id = ?';
	$result = $db->Execute($query, [$bookmark_id]);
	$row = $result->FetchRow();
	$title = $row['title'];
	$url = $row['url'];
}

include_once 'header.php';

$selfurl = basename(__FILE__);
$maintitle = $themeObject->ShowHeader('editbookmark');

$smarty->assign([
	'bookmark_id' => $bookmark_id,
	'error' => $error,
	'maintitle' => $maintitle,
	'title' => $title,
	'url' => $url,
	'selfurl' => $selfurl,
	'urlext' => $urlext,
]);

$smarty->display('editbookmark.tpl');

include_once 'footer.php';
