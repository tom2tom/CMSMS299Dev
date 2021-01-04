<?php
/*
CMSContentManager module action: clear locks
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\Lock;
use CMSMS\LockOperations;
use CMSMS\UserOperations;

if( !isset($gCms) ) exit;

$uid = get_userid();
$is_admin = UserOperations::get_instance()->UserInGroup($uid,1);

if( $is_admin ) {
    // clear all locks of type content
    $db = cmsms()->GetDb();
    $sql = 'DELETE FROM '.CMS_DB_PREFIX.Lock::LOCK_TABLE.' WHERE type = ?';
    $db->Execute($sql,['content']);
    cms_notice('Cleared all content locks');
} else {
    // clear only my locks
    LockOperations::delete_for_user($type);
    cms_notice("User $uid Cleared his own content locks");
}

$this->SetMessage($this->Lang('msg_lockscleared'));
$this->Redirect($id,'defaultadmin');
