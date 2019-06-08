<?php
#code for CMSMS
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AppState;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

// if this page was accessed directly, and the secure param name is not in the URL
// but it is in the session, assume it is correct.
if( isset($_SESSION[CMS_USER_KEY]) && !isset($_GET[CMS_SECURE_PARAM_NAME]) ) $_REQUEST[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];

$themeObject = cms_utils::get_theme_object();

$section = (isset($_GET['section'])) ? trim(cleanValue($_GET['section'])) : '';
include_once 'header.php';
$themeObject->do_toppage($section);
include_once 'footer.php';

AppState::remove_state(AppState::STATE_ADMIN_PAGE);
