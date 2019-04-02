<?php
/*
Procedure to process ajax calls for retrieving parameters-information
for a named User Defined Tag (aka user-plugin)
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

$userid = get_userid();
if (check_permission($userid, 'View Tag Help')) {
	$name = cleanValue($_GET['name']);
	$meta = (new UserPluginOperations())->get_meta_data($name, 'parameters');
	if (!empty($meta)) {
		echo (nl2br(cms_htmlentities(trim($meta, " \t\n\r"))));
	}
}
exit;
