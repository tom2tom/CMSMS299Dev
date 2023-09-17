<?php

// access to CMSMS 3.0+ API is needed
use CMSMS\TemplateType;
use function cms_installer\get_app;
use function cms_installer\get_server_permissions;
use function cms_installer\status_msg;
use function cms_installer\verbose_msg;

status_msg('Performing structure changes for CMSMS 2.2');

$app = get_app();
$destdir = $app->get_destdir();

$create_private_dir = function(string $relative_dir) use ($destdir): void {
    $relative_dir = trim($relative_dir);
    if (!$relative_dir) {
        return;
    }

    $dir = $destdir.DIRECTORY_SEPARATOR.$relative_dir;
    if (!is_dir($dir)) {
        $dirmode = get_server_permissions()[3]; // read+write+access
        @mkdir($dir, $dirmode, true);
    }
    @touch($dir.DIRECTORY_SEPARATOR.'index.html');
};

$move_directory_files = function(string $srcdir, string $destdir): void {
    $srcdir = trim($srcdir);
    $destdir = trim($destdir);
    if (!is_dir($srcdir)) {
        return;
    }

    $files = glob($srcdir.DIRECTORY_SEPARATOR.'*'); // filesystem path
    if (!$files) {
        return;
    }

    foreach ($files as $src) {
        $bn = basename($src);
        $dest = $destdir.DIRECTORY_SEPARATOR.$bn;
        rename($src, $dest);
    }
    @touch($dir.DIRECTORY_SEPARATOR.'index.html');
};

//$gCms = cmsms();
$dbdict = $db->NewDataDictionary();
/*
$str = $db->server_info;
if (stripos($str, 'Maria') === false) {
    $tblengn = 'MyISAM';
} else {
    $tblengn = 'Aria';
}
$taboptarray = ['mysqli' => "ENGINE=$tblengn"];
*/
$sqlarray = $dbdict->AddColumnSQL(CMS_DB_PREFIX.TemplateType::TABLENAME, 'help_content_cb C(255), one_only I1');
$dbdict->ExecuteSQLArray($sqlarray);

verbose_msg(ilang('upgrading_schema', 202));
$query = 'UPDATE '.CMS_DB_PREFIX.'version SET version = 202';
$db->execute($query);

$type = TemplateType::load('__CORE__::page');
$type->set_help_callback('CMSMS\internal\std_layout_template_callbacks::tpltype_help_callback');
$type->save();

$type = TemplateType::load('__CORE__::generic');
$type->set_help_callback('CMSMS\internal\std_layout_template_callbacks::tpltype_help_callback');
$type->save();

// create the assets (however named) directory structure
verbose_msg('Creating assets structure');
$config = $app->get_config();
$aname = (!empty($config['assetsdir'])) ? $config['assetsdir'] : 'assets';
$create_private_dir($aname.DIRECTORY_SEPARATOR.'templates');
$create_private_dir($aname.DIRECTORY_SEPARATOR.'configs');
$create_private_dir($aname.DIRECTORY_SEPARATOR.'module_custom');
$create_private_dir($aname.DIRECTORY_SEPARATOR.'admin_custom');
$create_private_dir($aname.DIRECTORY_SEPARATOR.'plugins');
$create_private_dir($aname.DIRECTORY_SEPARATOR.'images');
$create_private_dir($aname.DIRECTORY_SEPARATOR.'css');
$srcdir = $destdir.DIRECTORY_SEPARATOR.'module_custom';
if (is_dir($srcdir)) {
    $move_directory_files($srcdir, $destdir.DIRECTORY_SEPARATOR.$aname.'/module_custom');
}
$srcdir = $destdir.DIRECTORY_SEPARATOR.'admin/custom';
if (is_dir($srcdir)) {
    $move_directory_files($srcdir, $destdir.DIRECTORY_SEPARATOR.$aname.'/admin_custom');
}
$srcdir = $destdir.DIRECTORY_SEPARATOR.'tmp/configs';
if (is_dir($srcdir)) {
    $move_directory_files($srcdir, $destdir.DIRECTORY_SEPARATOR.$aname.'/configs');
}
$srcdir = $destdir.DIRECTORY_SEPARATOR.'tmp/templates';
if (is_dir($srcdir)) {
    $move_directory_files($srcdir, $destdir.DIRECTORY_SEPARATOR.$aname.'/templates');
}
