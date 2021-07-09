<?php
/*
Ajax alerts processing
Copyright (C) 2011-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Robert Campbell and all other contributors from the CMSMS Development Team.

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

use CMSMS\AdminAlerts\Alert;
use CMSMS\AppState;
use CMSMS\Error403Exception;
use function CMSMS\de_specialize;
use function CMSMS\sanitizeVal;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
$CMS_APP_STATE = AppState::STATE_ADMIN_PAGE; // in scope for inclusion, to set initial state
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

if (!isset($_REQUEST[CMS_SECURE_PARAM_NAME]) || !isset($_SESSION[CMS_USER_KEY]) || $_REQUEST[CMS_SECURE_PARAM_NAME] != $_SESSION[CMS_USER_KEY]) {
    throw new Error403Exception(lang('informationmissing'));
}

$userid = get_userid(false);
if( !$userid ) {
    throw new Error403Exception(lang('permissiondenied'));
}

$op = $_POST['op'] ?? 'delete'; // no sanitizeVal() etc cuz only 'delete' accepted
try {
    switch( $op ) {
    case 'delete':
        $val = de_specialize($_POST['alert']);
        $alert_name = sanitizeVal($val,CMSSAN_PUNCT);
        $alert = Alert::load_by_name($alert_name);
        $alert->delete();
        exit;
    default:
        throw new Exception('Unknown operation '.$op);
    }
}
catch( Throwable $t ) {
    $handlers = ob_list_handlers();
    for ($cnt = 0, $n = count($handlers); $cnt < $n; ++$cnt) { ob_end_clean(); }

    $msg = $t->GetMessage();
    header('HTTP/1.0 500 '.$msg);
    header('Status: 500 Server Error');
    echo $msg;
}
exit;
