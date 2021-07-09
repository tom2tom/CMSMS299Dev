<?php
/*
Procedure to record a bookmark for the current user
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

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

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Bookmark;
use CMSMS\Url;
use function CMSMS\de_specialize;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$tmp = $_SERVER['HTTP_REFERER'];
$link = (new Url())->sanitize($tmp);

if ($link) {
	$newmark = new Bookmark();
	$newmark->user_id = get_userid();
	$newmark->url = $link;
	$newmark->title = de_specialize($_GET['title']); // AND CMSMS\sanitizeVal(, CMSSAN_NONPRINT) etc

	if ($newmark->save()) {
		$config = AppSingle::Config();
		header('HTTP_REFERER: '.$config['admin_url'].'/menu.php');
		redirect($link);
	}
}

$urlext = get_secure_param();
$title = lang('erroraddingbookmark');
$backlink = 'addbookmark.php'.$urlext;
include __DIR__.DIRECTORY_SEPARATOR.'method.displayerror.php';
