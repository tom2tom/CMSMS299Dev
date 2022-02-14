<?php
/*
Procedure to record a bookmark for the current user
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Bookmark;
use CMSMS\SingleItem;
use CMSMS\Url;
use function CMSMS\de_specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$tmp = $_SERVER['HTTP_REFERER'];
$link = (new Url())->sanitize($tmp);

if ($link) {
	$newmark = new Bookmark();
	$newmark->user_id = get_userid();
	$newmark->url = $link;
	$newmark->title = de_specialize($_GET['title']); // AND CMSMS\sanitizeVal(, CMSSAN_NONPRINT) etc

	if ($newmark->save()) {
		$config = SingleItem::Config();
		header('HTTP_REFERER: '.$config['admin_url'].'/menu.php');
		redirect($link);
	}
}

$urlext = get_secure_param();
$title = _la('erroraddingbookmark');
$backlink = 'addbookmark.php'.$urlext;
include ".{$dsep}method.displayerror.php";
