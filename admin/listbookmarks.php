<?php
/*
Procedure to display a user's bookmarks
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
use CMSMS\BookmarkOperations;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$userid = get_userid();
$access = check_permission($userid, 'Manage My Bookmarks'); //TODO or 'Manage Bookmarks' or always
$padd = $access || check_permission($userid, 'Add Bookmarks');

$marklist = (new BookmarkOperations())->LoadBookmarks($userid);
$n = count($marklist);
$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
$limit = 20;

if ($n > $limit) {
	$pagination = pagination($page, $n, $limit); //TODO
	$minsee = $page * $limit - $limit;
	$maxsee = $page * $limit - 1;
} else {
	$pagination = null;
	$minsee = 0;
	$maxsee = $n;
}

$themeObject = AppSingle::Theme();
$iconadd = $themeObject->DisplayImage('icons/system/newobject.gif', lang('addbookmark'),'','','systemicon');
$iconedit = $themeObject->DisplayImage('icons/system/edit.gif', lang('edit'),'','','systemicon');
$icondel = $themeObject->DisplayImage('icons/system/delete.gif', lang('delete'),'','','systemicon');

$extras = get_secure_param_array();
$urlext = get_secure_param();

$smarty = AppSingle::Smarty();
$smarty->assign([
	'access' => $access,
	'addurl' => 'addbookmark.php',
	'deleteurl' => 'deletebookmark.php',
	'editurl' => 'editbookmark.php',
	'extraparms' => $extras,
	'iconadd' => $iconadd,
	'icondel' => $icondel,
	'iconedit' => $iconedit,
	'marklist' => $marklist,
	'maxsee' => $maxsee,
	'minsee' => $minsee,
	'padd' => $padd,
	'pagination' => $pagination,
	'urlext' => $urlext,
]);

$content = $smarty->fetch('listbookmarks.tpl');
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
