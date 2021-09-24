<?php
/*
Procedure to delete an admin user
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Events;
use CMSMS\SingleItem;
use CMSMS\UserParams;
use function CMSMS\log_info;

if (!isset($_GET['user_id'])) {
    return;
}

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

$urlext = get_secure_param();
$cur_userid = get_userid();
if( !check_permission($cur_userid, 'Manage Users') ) {
    SingleItem::Theme()->ParkNotice('error', _la('needpermissionto', '"Manage Users"'));
    redirect('listusers.php'.$urlext);
}

$key = '';
$userid = (int)$_GET['user_id'];
if ($userid != $cur_userid) {
    $userops = SingleItem::UserOperations();
    $ownercount = $userops->CountPageOwnershipByID($userid);
    if ($ownercount <= 0) {
        $oneuser = $userops->LoadUserByID($userid);
        $user_name = $oneuser->username;

        Events::SendEvent( 'Core', 'DeleteUserPre', ['user'=>&$oneuser] );

        if ($oneuser->Delete()) {
            UserParams::remove_for_user($userid);

            Events::SendEvent( 'Core', 'DeleteUserPost', ['user'=>&$oneuser] );

            // put mention into the admin log
            log_info($userid, 'Admin User '.$user_name, 'Deleted');
        } else {
            $key = 'failure';
        }
    } else {
        $key = 'erroruserinuse';
    }
} else {
    $key = 'cantremove';
}

if ($key) {
    SingleItem::Theme()->ParkNotice('error', _la($key));
}
redirect('listusers.php'.$urlext);
