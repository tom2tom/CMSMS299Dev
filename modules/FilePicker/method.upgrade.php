<?php
/*
FilePicker - A CMSMS module to provide various support services.
Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
Copyright (C) 2016-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Fernando Morgado and all other contributors from the CMSMS Development Team.

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
/* TODO work effectively with possible other filepicker modules
// always refresh the plugin, in case something in there has changed now
$fp = __DIR__.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'function.cms_filepicker.php';
$tp = CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'plugins'.DIRECTORY_SEPARATOR.'function.cms_filepicker.php';
if (copy($fp, $tp)) {
	$mode = get_server_permissions()[1];
	chmod($tp, $mode);
}
*/