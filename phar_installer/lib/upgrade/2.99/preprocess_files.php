<?php

use Exception;
use function cms_installer\get_app;
use function cms_installer\get_server_permissions;
use function cms_installer\rrmdir;

$app = get_app();
$destdir = $app->get_destdir();
if (!$destdir || !is_dir($destdir)) {
    throw new Exception('Destination directory does not exist');
}
$config = $app->get_config();
$s = (!empty($config['admin_path'])) ? $config['admin_path'] : 'admin';
$admindir = $destdir . DIRECTORY_SEPARATOR . $s;
$s = (!empty($config['assets_path'])) ? $config['assets_path'] : 'assets';
$assetsdir = $destdir . DIRECTORY_SEPARATOR . $s;
$s = (!empty($config['usertags_path'])) ? $config['usertags_path'] : '';
$plugsdir = ($s) ? $destdir . DIRECTORY_SEPARATOR . $s : $assetsdir . DIRECTORY_SEPARATOR . 'user_plugins';
$modes = get_server_permissions();

// 1. Create new folders if necessary
$dirmode = $modes[3];
foreach ([
 ['admin', 'configs'],
 ['assets', 'admin_custom'],
 ['assets', 'classes'],
 ['assets', 'configs'],
 ['assets', 'font'],
 ['assets', 'images'],
 ['assets', 'jobs'],
 ['assets', 'js'],
 ['assets', 'module_custom'],
 ['assets', 'modules'], //CHECKME using temp or distinct non-core-modules place
 ['assets', 'plugins'],
 ['assets', 'styles'],
 ['assets', 'templates'],
 ['assets', 'themes'], // for future use
 ['tags', $plugsdir], //UDTfiles
 ['lib', 'modules'], //CHECKME iff using temp or distinct core-modules place
] as $segs) {
    switch ($segs[0]) {
        case 'admin':
            $fp = $admindir . DIRECTORY_SEPARATOR . $segs[1];
            break;
        case 'assets':
            $fp = $assetsdir . DIRECTORY_SEPARATOR . $segs[1];
            break;
        case 'lib':
            $fp = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $segs[1];
            break;
        case 'tags':
            $fp = $segs[1];
            break;
        default:
            break 2;
    }
    if (!is_dir($fp)) {
        @mkdir($fp, $dirmode, true);
    }
    if (!is_dir($fp)) {
        throw new Exception("Could not create $fp directory");
    }
    touch($fp . DIRECTORY_SEPARATOR . 'index.html');
}
touch($assetsdir . DIRECTORY_SEPARATOR . 'index.html');

$s = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR;
foreach ([
 'aliases',
 'jobs',
 'Log',
 'module_support',
] as $d) {
    $fp = $s . $d;
    if (!is_dir($fp)) {
        @mkdir($fp, $dirmode, true);
    }
    if (!is_dir($fp)) {
        throw new Exception("Could not create $fp directory");
    }
    touch($fp . DIRECTORY_SEPARATOR . 'index.html');
}

// 2. Remove redundant folders and contents if necessary
// note : keep unwanted modules until later, after their uninstallation
foreach ([
 ['lib', 'phpmailer'],
 ['lib', 'smarty'],
 ['lib', 'tasks'],
] as $segs) {
    $fp = $destdir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segs);
    if (is_dir($fp)) {
        if (!rrmdir($fp)) {
            throw new Exception("Could not remove $fp directory");
        }
    }
}

// 3. Rename folders if necessary
$s = $assetsdir . DIRECTORY_SEPARATOR . 'css';
if (is_dir($s)) {
    $to = $assetsdir . DIRECTORY_SEPARATOR . 'styles';
    rename($s, $to);
    touch($to . DIRECTORY_SEPARATOR . 'index.html');
}
/*
$s = $assetsdir . DIRECTORY_SEPARATOR . 'user_plugins';
if ($s != $plugsdir && is_dir($s)) {
    @rename($s, $plugsdir);
    touch($plugsdir . DIRECTORY_SEPARATOR . 'index.html');
} else {
    $s = $assetsdir . DIRECTORY_SEPARATOR . 'file_plugins';
    if ($s != $plugsdir && is_dir($s)) {
        @rename($s, $plugsdir);
        touch($plugsdir . DIRECTORY_SEPARATOR . 'index.html');
    } elseif (!is_dir($plugsdir)) {
        @mkdir($plugsdir, $modes[3], true);
        touch($plugsdir . DIRECTORY_SEPARATOR . 'index.html');
    }
}
*/
/*
// X. Move non-system plugins from /lib/plugins to /[assets]/plugins
$ours = [no-glob each basename.php in installer-sources-dir/lib/plugins];
$from = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
$to = $assetsdir . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR;
foreach (glob($from.'*.php' OR any file ??) as $fp) { // filesystem path
    $fn = basename($fp);
    if ($fn endswith '.php' && in_array $ours) continue
    if ($to.$fn exists) continue ??
    if $fp contents contains '../'  | '..' | dirname(X) where X != '__FILE__' continue
    @unlink($to.$fn);
    rename($from.$fn, $to.$fn);
}
*/

// 4. Move/remove/replace files (before the main files-update)
//unlink($destdir . DIRECTORY_SEPARATOR . 'config.php'); //TODO ensure was replaced by ../lib/config.php
$s = $destdir . DIRECTORY_SEPARATOR . 'config.php';
$to = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'config.php';
if (is_file($to)) {
    @unlink($s);
} else {
    @rename($s, $to);
}
//unlink($admindir . DIRECTORY_SEPARATOR . 'moduleinteface.php'); // manifest should handle this
@unlink($admindir . DIRECTORY_SEPARATOR . 'debug.log');
@unlink($admindir . DIRECTORY_SEPARATOR . 'error_log');
