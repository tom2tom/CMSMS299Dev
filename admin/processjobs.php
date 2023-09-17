<?php
/*
Process-async / background jobs
Copyright (C) 2022-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\AppState;
use CMSMS\internal\JobOperations;
use CMSMS\RequestParameters;

while (ob_get_level()) {
    @ob_end_clean();
}
ignore_user_abort(true);
$out = 'X-CMSMS: Processing';
$size = strlen($out);
header('Connection: Close');
header("Content-Length: $size");
header($out);
flush();

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
AppState::set(AppState::ASYNC_JOB);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

$log = (defined('ASYNCLOG')) ?
    TMP_CACHE_LOCATION.DIRECTORY_SEPARATOR.'debug.log' :
    false;

$valid = RequestParameters::check_secure_params($_GET);
/* TMI
if ($valid) {
    if ($log) {
        error_log('async processor: parameters check ok'."\n", 3, $log);
    }
} else {
*/
if (!$valid) {
    if ($log) {
        error_log('async processor: exit - parameters check failed'."\n", 3, $log);
    }
    exit;
}
if (!isset($_GET[CMS_SECURE_PARAM_NAME.'job'])) {
    if ($log) {
        $p = CMS_SECURE_PARAM_NAME;
        error_log("async processor: exit - no '{$p}job' parameter\n", 3, $log);
    }
    exit;
}

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'internal'.DIRECTORY_SEPARATOR.'class.JobOperations.php';
$ops = new JobOperations();
register_shutdown_function('\CMSMS\internal\JobOperations::errorhandler');

require_once __DIR__.DIRECTORY_SEPARATOR.'method.processjobs.php';
