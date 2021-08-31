<?php

use function cms_installer\get_app;
use function cms_installer\rrmdir;

status_msg('Performing directory changes for CMSMS 2.2.1');

$app = get_app();
$destdir = $app->get_destdir();
$plugins_from = $destdir.DIRECTORY_SEPARATOR.'plugins';
if (!is_dir($plugins_from)) {
    return;
}

$config = $app->get_config();
$aname = (!empty($config['assetsdir'])) ? $config['assetsdir'] : 'assets';
$plugins_to = $destdir.DIRECTORY_SEPARATOR.$aname.'/plugins';
$files = glob($plugins_from.DIRECTORY_SEPARATOR.'*'); // filesystem path
if (!$files) {
    return;
}

// check permissions
if (!is_dir($plugins_to) || !is_writable($plugins_to)) {
    error_msg('Note: Could not move plugins to /'.$aname.'/plugins because of permissions in the destination directory');
    return;
}
foreach ($files as $filespec) {
    if (!is_writable($filespec)) {
        error_msg('Note: Could not move plugins to /'.$aname.'/plugins because because of permissions in the source directory');
        return;
    }
}

$remove = function($in) {
    if (is_file($in)) {
        @unlink($in);
    } elseif (is_dir($in)) {
        rrmdir($in);
    }
};

// move the files
foreach ($files as $src_name) {
    $bn = basename($src_name);
    $dest_name = $plugins_to.DIRECTORY_SEPARATOR.$bn;
    if (!is_file($dest_name) && !is_dir($dest_name)) {
        rename($src_name, $dest_name);
    }
    $remove($src_name);
}

// maybe remove the directory
$files = glob($plugins_from.'/*'); // filesystem path
$do_remove = (count($files) == 0);
if (count($files) == 1) {
    $bn = strtolower(basename($files[0]));
    if ($bn == 'index.html' || $bn == 'index.htm') {
        $do_remove == true;
    }
}
if ($do_remove) {
    rrmdir($plugins_from);
}
@touch($plugins_to.DIRECTORY_SEPARATOR.'index.html');
