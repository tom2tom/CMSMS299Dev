<?php
/*
Procedure to delete a named User Defined Tag (aka user-plugin) file
Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\UserPluginOperations;

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$userid = get_userid();
$pmod = check_permission($userid, 'Modify User Plugins');
if (!$pmod) exit;

$themeObject = cms_utils::get_theme_object();

$tagname = cleanValue($_GET['name']);
$ops = new UserPluginOperations();
$fp = $ops->file_path($tagname);
if (is_file($fp)) {
//? send event :: deleteuserdefinedtagpre
    if ($ops->delete($tagname)) {
        $themeObject->ParkNotice('success', lang('deleted_udt'));
//? send event :: deleteuserdefinedtagpost
    } else {
        $themeObject->ParkNotice('error', lang('errordeletag'));
    }
} else {
    $themeObject->ParkNotice('error', lang('error_internal'));
}

$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
redirect('listusertags.php'.$urlext);
