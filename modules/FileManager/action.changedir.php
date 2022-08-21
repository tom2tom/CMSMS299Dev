<?php
/*
FileManager module action: changedir
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
use FileManager\Utils;
use function CMSMS\log_error;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET[CMS_JOB_KEY]) && $_GET[CMS_JOB_KEY] > 0) {
    echo Utils::get_cwd();
    exit;
}

if (!isset($params['newdir']) && !isset($params['setdir'])) {
    $this->RedirectToAdminTab();
}

$path = null;
if (isset($params['newdir'])) {
    // set a relative directory.
    $newdir = trim($params['newdir']);
    $path = cms_join_path(Utils::get_cwd(), $newdir);
} elseif (isset($params['setdir'])) {
    // set an explicit directory
    $path = trim($params['setdir']);
    if ($path == '::top::') {
        $path = Utils::get_default_cwd();
    }
}

try {
    Utils::set_cwd($path);
    if (!isset($params['ajax'])) {
        Utils::set_cwd($path);
        $this->RedirectToAdminTab();
    }
} catch (Throwable $t) {
    log_error('Attempt to set invalid working directory', $path);
    if (isset($params['ajax'])) {
        exit('ERROR');
    }
    $this->SetError($this->Lang('invalidchdir', $path));
    $this->RedirectToAdminTab();
}

if (isset($params['ajax'])) {
    echo 'OK';
    exit;
}
$this->RedirectToAdminTab();
