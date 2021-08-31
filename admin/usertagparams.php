<?php
/*
Procedure to process ajax call to retrieve parameters-information for a named user-plugin
Copyright (C) 2018-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\Error403Exception;
use CMSMS\SingleItem;
use function CMSMS\de_specialize;
use function CMSMS\sanitizeVal;
use function CMSMS\specialize;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

$handlers = ob_list_handlers();
for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

$userid = get_userid(false);
if (check_permission($userid, 'View UserTag Help')) {
    $tmp = de_specialize($_GET['name']);
    $name = sanitizeVal($tmp, CMSSAN_FILE);
    $info = SingleItem::UserTagOperations()->GetUserTag($name, 'parameters');
    if (!empty($info)) {
        echo (nl2br(specialize(trim($info, " \t\n\r")), ENT_XML1 | ENT_QUOTES));
    }
    exit;
}
else {
    throw new Error403Exception(lang('permissiondenied')); // OR display error.tpl ?
}
