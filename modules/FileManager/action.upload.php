<?php
use FileManager\UploadHandler;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) exit;

$fullpath = cms_join_path(CMS_ROOT_PATH, $params['path']);
$real = stream_resolve_include_path($fullpath);
if (!$real) exit;

$UploadHandler = new UploadHandler(['upload_dir'=>$real, 'param_name'=>$id.'files']);

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
