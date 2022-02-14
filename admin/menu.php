<?php
/*
Admin menu top/section processor
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\SingleItem;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

check_login();

// if this page was accessed directly, and the secure param value is not
// in the URL but it is in the session, assume the latter is correct.
if( !isset($_GET[CMS_SECURE_PARAM_NAME]) && isset($_SESSION[CMS_USER_KEY]) ) {
    $_REQUEST[CMS_SECURE_PARAM_NAME] = $_SESSION[CMS_USER_KEY];
}

$section = (isset($_GET['section'])) ? sanitizeVal($_GET['section'], CMSSAN_PURE) : ''; // valid non-words are '.' '-' TODO AND de_specialize()?

$content = SingleItem::Theme()->fetch_menu_page($section);
require ".{$dsep}header.php";
echo $content;
require ".{$dsep}footer.php";
