<?php
# Stylesheeets operations performer
# Copyright (C) 2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

$CMS_ADMIN_PAGE = 1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    exit;
}

check_login();
$userid = get_userid();
if( !check_permission($userid,'Manage Stylesheets') ) {
	return;
}
$urlext = '?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
//$themeObject = cms_utils::get_theme_object();

cleanArray($_REQUEST);

$css_id = (int)$_REQUEST['css']; //< 0 for a group
//OR array for bulk

switch ($_REQUEST['op']) {
	case 'copy':
		break;
	case 'delete':
		break;
	case 'deleteall':
		break;
	case 'replace':
		break;
	case 'append':
		break;
	case 'prepend':
		break;
	case 'remove':
		break;
}

redirect('X.php'.$urlext);
