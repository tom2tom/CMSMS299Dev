<?php
/*
AdminSearch module uininstallation process
Copyright (C) 2012-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\AppSingle;
use CMSMS\UserParams;

if (!function_exists('cmsms')) exit;

$this->RemovePermission('Use Admin Search');

// delete for current (so that local props cache is cleared)
$userid = get_userid();
$me = $this->GetName();
UserParams::remove_for_user($userid,$me.'saved_search');
// and all the others
$query = 'DELETE FROM '.CMS_DB_PREFIX.'userprefs WHERE preference = '.$me.'saved_search';
$db = AppSingle::Db();
$db->Execute($query);
