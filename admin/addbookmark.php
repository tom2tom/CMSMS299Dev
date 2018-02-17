<?php
#procedure to add a bookmark for the user
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
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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

$title= '';
$url = '';
$error = '';

if (isset($_POST['addbookmark'])) {
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
		$markobj->title = $title;
		$markobj->url = $url;
		$markobj->user_id = get_userid();

		if ($markobj->save()) {
			redirect('listbookmarks.php'.$urlext);
			return;
		} else {
			$error .= '<li>'.lang('errorinsertingbookmark').'</li>';
		}
	}
}

include_once 'header.php';

$maintitle = $themeObject->ShowHeader('addbookmark');
$selfurl = basename(__FILE__);

$smarty->assign([
	'error' => $error,
	'maintitle' => $maintitle,
	'title' => $title,
	'url' => $url,
	'urlext' => $urlext,
	'selfurl' => $selfurl,
]);

$smarty->display('addbookmark.tpl');

include_once 'footer.php';
