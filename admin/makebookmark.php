<?php
#procedure to record a bookmark for the current user
#Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Bookmark;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();

$link = $_SERVER['HTTP_REFERER'];
$newmark = new Bookmark();
$newmark->user_id = get_userid();
$newmark->url = $link;
$newmark->title = $_GET['title'];
$result = $newmark->save();

if ($result) {
    $config = AppSingle::Config();
	header('HTTP_REFERER: '.$config['admin_url'].'/menu.php');
	redirect($link);
}

//TODO use an error-display template perhaps with popup notice

require './header.php';
echo '<h3>'. lang('erroraddingbookmark') . '</h3>';
require './footer.php';
