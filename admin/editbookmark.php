<?php
/*
Modify a user's bookmark
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
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

// all $_POST members cleaned as needed
$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listbookmarks.php'.$urlext);
}

$userid = get_userid();
$themeObject = AppSingle::Theme();

if (isset($_GET['bookmark_id'])) {
    $bookmark_id = (int)$_GET['bookmark_id'];
} else {
    $bookmark_id = -1; // should never happen when editing?
}

if (isset($_POST['editbookmark'])) {
    $errors = [];
    $bookmark_id = (int)$_POST['bookmark_id'];

    $tmp = de_specialize(trim($_POST['title']));
    $title = sanitizeVal($tmp, CMSSAN_NONPRINT); // AND nl2br() ? striptags() ?
    if (!$title) {
        $errors[] = lang('nofieldgiven', lang('title'));
    }

    $tmp = de_specialize(trim($_POST['url']));
    if ($tmp) {
        $url = (new Url())->sanitize($tmp);
    } else {
        $errors[] = lang('nofieldgiven', lang('url'));
    }

    if (!$errors) {
        $markobj = new Bookmark();
        $markobj->bookmark_id = $bookmark_id;
        $markobj->title = $title;
        $markobj->url = $url;
        $markobj->user_id = $userid;

        if ($markobj->save()) {
            redirect('listbookmarks.php'.$urlext);
        } else {
            $errors[] = lang('errorupdatingbookmark');
        }
    }

    if ($errors) {
        $themeObject->RecordNotice('error', $errors);
    }

    $title = specialize($title);
    $url = specialize($url);
} elseif ($bookmark_id != -1) {
    $db = AppSingle::Db();
    $query = 'SELECT user_id,title,url FROM '.CMS_DB_PREFIX.'admin_bookmarks WHERE bookmark_id = ?';
    $row = $db->GetRow($query, [$bookmark_id]);
    if ($row) {
        if (!($row['user_id'] == $userid || check_permission($userid, 'Manage TODO'))) {
            $themeObject->ParkNotice('error', lang('TODO no perm'));
            redirect('listbookmarks.php'.$urlext);
        }
        $title = specialize($row['title']);
        $url = specialize($row['url']);
    } else {
        $themeObject->ParkNotice('error', lang('error_internal'));
        redirect('listbookmarks.php'.$urlext);
    }
} else { // id == -1 should never happen when editing ?
    $title = '';
    $url = '';
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
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
