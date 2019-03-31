<?php

use CMSMS\LogicException;
use function cms_installer\get_app;

$app = get_app();
$destdir = $app->get_destdir();
if (!$destdir || !is_dir($destdir)) {
    throw new LogicException('Destination directory does not exist');
}
$config = $app->get_config();
$s = (!empty($config['assetsdir'])) ? $config['assetsdir'] : 'assets';
$assetsdir = $destdir . DIRECTORY_SEPARATOR . $s;

$tp = $assetsdir . DIRECTORY_SEPARATOR . 'user_plugins';
$fp = $assetsdir . DIRECTORY_SEPARATOR . 'simple_plugins';
if (is_dir($fp)) {
    @rename($fp, $tp);
    touch($tp . DIRECTORY_SEPARATOR . 'index.html');
} else {
    $fp = $assetsdir . DIRECTORY_SEPARATOR . 'file_plugins';
    if (is_dir($fp)) {
        @rename($fp, $tp);
        touch($tp . DIRECTORY_SEPARATOR . 'index.html');
    } elseif (!is_dir($tp)) {
        @mkdir($tp, 0771, true);
        touch($tp . DIRECTORY_SEPARATOR . 'index.html');
    }
}
