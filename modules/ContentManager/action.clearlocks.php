<?php
/*
ContentManager module action: clear locks
Copyright (C) 2013-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\LockOperations;
use CMSMS\SingleItem;
use function CMSMS\log_notice;

//if( some worthy test fails ) exit;

$uid = get_userid();
$is_admin = SingleItem::UserOperations()->UserInGroup($uid,1);

if( $is_admin ) {
    // clear all locks of type content
    $db = cmsms()->GetDb();
    $sql = 'DELETE FROM '.CMS_DB_PREFIX. LockOperations::LOCK_TABLE.' WHERE type = ?';
    $db->execute($sql,['content']);
    log_notice('ContentManager','Cleared all content locks');
} else {
    // clear only my locks
    LockOperations::delete_for_user($type);
    log_notice("User $uid cleared his/her own content locks");
}

$this->SetMessage($this->Lang('msg_lockscleared'));
$this->Redirect($id,'defaultadmin');
