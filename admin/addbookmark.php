<?php
/*
Add a bookmark for the current user
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

use CMSMS\Bookmark;
use CMSMS\SingleItem;
use CMSMS\Url;
use function CMSMS\de_specialize;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listbookmarks.php'.$urlext);
}

//CMSMS\de_specialize_array($_POST);
if (isset($_POST['addbookmark'])) {
    $errors = [];
    $title = de_specialize(trim($_POST['title']));
    $title = sanitizeVal($title, CMSSAN_NONPRINT); // AND nl2br() ? striptags() ?
    if (!$title) {
        $errors[] = _la('nofieldgiven', _la('title'));
    }

    $tmp = de_specialize(trim($_POST['url']));
    if ($tmp) {
        $url = (new Url())->sanitize($tmp);
    } else {
        $errors[] = _la('nofieldgiven', _la('url'));
    }

    if (!$errors) {
        $markobj = new Bookmark();
        $markobj->title = $title;
        $markobj->url = $url;
        $markobj->user_id = get_userid();

        if ($markobj->save()) {
            redirect('listbookmarks.php'.$urlext);
        } else {
            $errors[] = _la('errorinsertingbookmark');
        }
    }

    SingleItem::Theme()->RecordNotice('error', $errors);

    $title = specialize($title);
    $url = specialize($url);
} else {
    $title = '';
    $url = '';
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = SingleItem::Smarty();
$smarty->assign([
    'title' => $title,
    'url' => $url,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
]);

$content = $smarty->fetch('addbookmark.tpl');
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
