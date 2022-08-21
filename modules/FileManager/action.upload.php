<?php
/*
FileManager module action: upload
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

use FileManager\UploadHandler;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) {
    exit;
}

$fullpath = cms_join_path(CMS_ROOT_PATH, $params['path']);
$real = stream_resolve_include_path($fullpath);
if (!$real) {
    exit;
}

$UploadHandler = new UploadHandler(['upload_dir' => $real, 'param_name' => $id.'files']);

header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'HEAD':
    case 'GET':
        $UploadHandler->get();
        break;
    case 'POST':
        $UploadHandler->post();
        break;
    case 'DELETE':
        $UploadHandler->delete();
        break;
    case 'OPTIONS':
        break;
    default:
        $proto = $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.0';
        header($proto.' 405 Method Not Allowed');
}

exit;
