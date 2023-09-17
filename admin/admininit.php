<?php
/*
Generic init for admin-console scripts
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
use CMSMS\Error403Exception;
use CMSMS\Lone;
use function CMSMS\log_error;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'classes'.DIRECTORY_SEPARATOR.'class.AppState.php';
AppState::set(AppState::ADMIN_PAGE);
require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';
$_N_ = $_REQUEST[CMS_SECURE_PARAM_NAME] ?? -1;
$_K_ = $_SESSION[CMS_USER_KEY] ?? 1;
if ($_N_ === $_K_) {
    unset($_N_, $_K_);
    return;
}
if ($_N_ === -1) {
    log_error(_la('missingtype', 'secure parameter'), $_SERVER['REQUEST_URI'] ?? $_SERVER['PHP_SELF']);
}
if (defined('CMS_ROOT_URL')) {
// TODO evaulate risk of this - in effect, $_REQUEST[CMS_SECURE_PARAM_NAME] is never needed
    if ($_N_ === -1 && $_K_ !== 1/* && defined('CMS_DEBUG')*/) {
        // the user should not normally be hassled about a malformed request,
        // but somebody|thing malicious might have spoofed TODO handle that
        // cookie-check to perhaps avoid a force-login
        if (Lone::get('AuthOperations')->authenticate($_K_)) {
            $_REQUEST[CMS_SECURE_PARAM_NAME] = $_K_;
            unset($_N_, $_K_, $key);
            return;
        }
        unset($key);
    }
    unset($_N_, $_K_);
    redirect(Lone::get('Config')['admin_url'].'/login.php');
} else {
    unset($_N_, $_K_);
    throw new Error403Exception(_la('error_informationbad'));
}
