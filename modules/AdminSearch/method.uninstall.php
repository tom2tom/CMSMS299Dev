<?php
/*
AdminSearch module uininstallation process
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\SingleItem;
use CMSMS\UserParams;

if (empty($this) || !($this instanceof AdminSearch)) exit;
//$installing = AppState::test(AppState::INSTALL);
//if (!($installing || $this->CheckPermission('Modify Modules'))) exit;

$this->RemovePermission('Use Admin Search');

//try to remove relocated deprecated class-aliases
$bp = CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'aliases';
if (is_writable($bp)) {
    $tpl2 = $bp.DIRECTORY_SEPARATOR.'class.%s.php';
    foreach (['AdminSearch_tools', 'AdminSearch_slave'] as $nm) {
        $fp = sprintf($tpl2, $nm);
        try {
            @unlink($fp);
        } catch (Throwable $t) {} //ignore error
    }
}

// delete for current (so that local props cache is cleared)
$userid = get_userid();
$me = $this->GetName();
UserParams::remove_for_user($userid,$me.'saved_search');
// and all the others
$query = 'DELETE FROM '.CMS_DB_PREFIX.'userprefs WHERE preference = '.$me.'saved_search';
$db = SingleItem::Db();
$db->execute($query);
