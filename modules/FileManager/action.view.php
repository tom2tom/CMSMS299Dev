<?php
use FileManager\Utils;

//if (some worthy test fails) exit;
if( !isset($params['file']) ) {
    $params['fmerror'] = 'nofilesselected';
    $this->Redirect($id,'defaultadmin',$returnid,$params);
}

$filename = $this->decodefilename($params['file']);
$src = cms_join_path(CMS_ROOT_PATH,Utils::get_cwd(),$filename);
if( !file_exists($src) ) {
    $params['fmerror'] = 'filenotfound';
    $this->Redirect($id,'defaultadmin',$returnid,$params);
}

// get its mime type
$mimetype = Utils::mime_content_type($src);

$handlers = ob_list_handlers();
for ($cnt = 0; $cnt < count($handlers); ++$cnt) { ob_end_clean(); }
//TODO reconcile with CMSMS\sendheaders()
header("Content-Type: $mimetype");
header('Expires: 0');
header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
echo file_get_contents($src);
exit;
