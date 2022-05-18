<?php
/*
Procedure to delete a named User Defined Tag (aka user-plugin) file
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Error403Exception;
use CMSMS\Lone;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$userid = get_userid();
if (!check_permission($userid, 'Manage User Plugins')) {
//TODO some pushed popup c.f. javascript:cms_notify('error', _la('no_permission') OR _la('needpermissionto', _la('perm_Manage_Groups')), ...);
    throw new Error403Exception(_la('permissiondenied')); // OR display error.tpl ?
}

$themeObject = Lone::get('Theme');

$tagname = sanitizeVal($_GET['name'], CMSSAN_FILE); // UDT might be file-stored BUT no space(s) ?
$ops = Lone::get('UserTagOperations');
if ($ops->UserTagExists($tagname)) {  // UDT-files included
//if exists $ops->DoEvent( deleteuserpluginpre etc);
    if ($ops->RemoveUserTag($tagname)) {
        $themeObject->ParkNotice('success', _la('deleted_usrplg'));
//     $ops->DoEvent( deleteuserpluginpost etc);
    } else {
        $themeObject->ParkNotice('error', _la('error_usrplg_del'));
    }
} else {
    $themeObject->ParkNotice('error', _la('error_internal'));
}

$urlext = get_secure_param();
redirect('listusertags.php'.$urlext);
