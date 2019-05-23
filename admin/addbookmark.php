<?php
#procedure to add a bookmark for a user
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\Bookmark;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$urlext = get_secure_param();
if (isset($_POST['cancel'])) {
    redirect('listbookmarks.php'.$urlext);
    return;
}

$themeObject = cms_utils::get_theme_object();

$title= '';
$url = '';

if (isset($_POST['addbookmark'])) {
    $title = trim(cleanValue($_POST['title']));
    $url = filter_var($_POST['url'], FILTER_SANITIZE_URL);

    $validinfo = true;
    if ($title === '') {
        $validinfo = false;
        $themeObject->RecordNotice('error', lang('nofieldgiven', lang('title')));
    }
    if ($url === '') {
        $validinfo = false;
        $themeObject->RecordNotice('error', 'nofieldgiven', lang('url'));
    }

    if ($validinfo) {
        $markobj = new Bookmark();
        $markobj->title = $title;
        $markobj->url = $url;
        $markobj->user_id = get_userid();

        if ($markobj->save()) {
            redirect('listbookmarks.php'.$urlext);
            return;
        } else {
            $themeObject->RecordNotice('error', lang('errorinsertingbookmark'));
        }
    }
}

$selfurl = basename(__FILE__);
$extras = get_secure_param_array();

$smarty = CmsApp::get_instance()->GetSmarty();
$smarty->assign([
    'title' => $title,
    'url' => $url,
    'selfurl' => $selfurl,
    'extraparms' => $extras,
    'urlext' => $urlext,
]);

include_once 'header.php';
$smarty->display('addbookmark.tpl');
include_once 'footer.php';
