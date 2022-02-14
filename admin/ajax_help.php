<?php
/*
Runtime help-string getter
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

use CMSMS\LangOperations;
use function CMSMS\sanitizeVal;

$dsep = DIRECTORY_SEPARATOR;
require ".{$dsep}admininit.php";

//check_login();
//$urlext = get_secure_param();

$key = ( isset($_GET['key']) ) ? sanitizeVal($_GET['key'], CMSSAN_PUNCTX, '_') : 'help';
if( !$key ) {
    $key = 'help';
}
if( strpos($key,'__') !== FALSE ) {
    list($domain,$key) = explode('__',$key,2);
    if( strcasecmp($domain,'core') == 0 ) {
        $domain = LangOperations::CMSMS_ADMIN_REALM;
    }
}
else {
    $domain = LangOperations::CMSMS_ADMIN_REALM;
}
$out = LangOperations::domain_string($domain,$key);

echo $out; //assume no sent headers need clearing
exit;
