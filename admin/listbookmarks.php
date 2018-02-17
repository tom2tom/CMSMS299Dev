<?php
#procedure to display a user's bookmarks
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

$userid = get_userid();
$access = check_permission($userid, 'Manage Bookmarks');
$padd = $access || check_permission($userid, 'Add Bookmarks');

$bookops = cmsms()->GetBookmarkOperations();
$marklist = $bookops->LoadBookmarks($userid);
$n = count($marklist);
$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
$limit = 20;

include_once 'header.php';

if ($n > $limit) {
	$pagination = pagination($page, $n, $limit); //TODO
	$minsee = $page * $limit - $limit;
	$maxsee = $page * $limit - 1;
} else {
	$pagination = null;
	$minsee = 0;
	$maxsee = $n;
}

$iconadd = $themeObject->DisplayImage('icons/system/newobject.gif', lang('addbookmark'),'','','systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.gif', lang('edit'),'','','systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'),'','','systemicon');

$maintitle = $themeObject->ShowHeader('bookmarks');

$smarty->assign([
	'access' => $access,
	'addurl' => 'addbookmark.php',
	'deleteurl' => 'deletebookmark.php',
	'editurl' => 'editbookmark.php',
	'iconadd' => $iconadd,
	'icondel' => $icondel,
	'iconedit' => $iconedit,
	'maintitle' => $maintitle,
	'markslist' => $marklist,
	'maxsee' => $maxsee,
	'minsee' => $minsee,
	'padd' => $padd,
	'pagination' => $pagination,
	'urlext' => $urlext,
]);

$smarty->display('listbookmarks.tpl');

include_once 'footer.php';
