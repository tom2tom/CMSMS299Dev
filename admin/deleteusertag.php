<?php
/*
Procedure to delete a named User Defined Tag (aka user-plugin) file
Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AppState;
use CMSMS\UserTagOperations;
use CMSMS\Utils;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$userid = get_userid();
$pmod = check_permission($userid, 'Manage User Plugins');
if (!$pmod) exit;

$themeObject = Utils::get_theme_object();

$tagname = sanitizeVal($_GET['name'], 3); // UDT might be file-stored
$ops = UserTagOperations::get_instance();
if ($ops->UserTagExists($tagname)) {  // UDT-files included
//if exists $ops->DoEvent( deleteuserpluginpre etc);
    if ($ops->RemoveUserTag($tagname)) {
        $themeObject->ParkNotice('success', lang('deleted_usrplg'));
//     $ops->DoEvent( deleteuserpluginpost etc);
    } else {
        $themeObject->ParkNotice('error', lang('error_usrplg_del'));
    }
} else {
    $themeObject->ParkNotice('error', lang('error_internal'));
}

$urlext = get_secure_param();
redirect('listusertags.php'.$urlext);
