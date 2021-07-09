<?php
/*
Runtime help-string getter
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

use CMSMS\AppState;
use CMSMS\Error403Exception;
use CMSMS\LangOperations;
use function CMSMS\sanitizeVal;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    throw new Error403Exception(lang('informationmissing'));
}

//check_login();
//$urlext = get_secure_param();

$key = ( isset($_GET['key']) ) ? sanitizeVal($_GET['key'], CMSSAN_PUNCTX, '_') : 'help';
if( !$key ) { $key = 'help'; }
if( strpos($key,'__') !== FALSE ) {
    list($realm,$key) = explode('__',$key,2);
    if( strcasecmp($realm,'core') == 0 ) $realm = 'admin';
}
else {
    $realm = 'admin';
}
$out = LangOperations::lang_from_realm($realm,$key);

echo $out; //assume no sent headers need clearing
exit;
