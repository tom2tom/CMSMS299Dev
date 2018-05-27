<?php
# FilePicker module action: upload
# Copyright (C) 2016 Fernando Morgado <jomorg@cmsmadesimple.org>
# Copyright (C) 2016-2018 Robert Campbell <calguy1000@cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

if( !defined('CMS_VERSION') ) exit;

$devmode = $this->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
$base = ( $devmode ) ? CMS_ROOT_PATH : $config['uploads_path'];
//TOOO some $param for relative path

$UploadHandler = new FilePicker\UploadHandler(['module'=>$this, 'upload_dir' => $base]);

header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');
header('X-Content-Type-Options: nosniff');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: OPTIONS, HEAD, GET, POST, DELETE');
header('Access-Control-Allow-Headers: X-File-Name, X-File-Type, X-File-Size');

switch( $_SERVER['REQUEST_METHOD'] ) {
	case 'OPTIONS':
        break;
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
	default:
        header('HTTP/1.1 405 Method Not Allowed');
}

exit;
