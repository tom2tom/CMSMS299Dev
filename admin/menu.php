<?php
/*
Admin menu top/section processor
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\AppSingle;
use CMSMS\AppState;
use function CMSMS\sanitizeVal;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

// if this page was accessed directly, and the secure param value is not
// in the URL but it is in the session, assume the latter is correct.
if( !isset($_GET[CMS_SECURE_PARAM_NAME]) && isset($_SESSION[CMS_USER_KEY]) ) {
    $_REQUEST[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
}

$section = (isset($_GET['section'])) ? sanitizeVal($_GET['section'], CMSSAN_PURE) : ''; // valid non-words are '.' '-' TODO AND de_specialize()?

$content = AppSingle::Theme()->fetch_menu_page($section);
$sep = DIRECTORY_SEPARATOR;
require ".{$sep}header.php";
echo $content;
require ".{$sep}footer.php";
