<?php
#procedure to modify a user's bookmark
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
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AppSingle;
use CMSMS\AppState;
use CMSMS\Bookmark;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listbookmarks.php'.$urlext);
    return;
}

$themeObject = Utils::get_theme_object();

$title = '';
$url = '';
if (isset($_GET['bookmark_id'])) {
    $bookmark_id = (int)$_GET['bookmark_id'];
} else {
    $bookmark_id = -1;
}

if (isset($_POST['editbookmark'])) {
    $bookmark_id = (int)$_POST['bookmark_id'];
    $title = trim(cleanValue($_POST['title']));
    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);

    $validinfo = true;
    if ($title === '') {
        $validinfo = false;
        $themeObject->RecordNotice('error', lang('nofieldgiven', lang('title')));
    }
    if ($url === '') {
        $validinfo = false;
        $themeObject->RecordNotice('error', lang('nofieldgiven', lang('url')));
    }

    if ($validinfo) {
        $markobj = new Bookmark();
        $markobj->bookmark_id = $bookmark_id;
        $markobj->title = $title;
        $markobj->url = $url;
        $markobj->user_id = get_userid();

        if ($markobj->save()) {
            redirect('listbookmarks.php'.$urlext);
            return;
        } else {
            $themeObject->RecordNotice('error', lang('errorupdatingbookmark'));
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

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = AppSingle::Smarty();
$smarty->assign([
    'bookmark_id' => $bookmark_id,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'title' => $title,
    'url' => $url,
    'urlext' => $urlext,
]);

$content = $smarty->fetch('editbookmark.tpl');
require './header.php';
echo $content;
require './footer.php';
