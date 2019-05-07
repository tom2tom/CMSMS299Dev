<?php

use cms_installer\utils;
use cms_installer\wizard\wizard;
use CMSMS\LogicException;
use function cms_installer\get_app;

$app = get_app();
$destdir = $app->get_destdir();
if (!$destdir || !is_dir($destdir)) {
    throw new LogicException('Destination directory does not exist');
}
$config = $app->get_config();
$s = (!empty($config['admindir'])) ? $config['admindir'] : 'admin';
$admindir = $destdir . DIRECTORY_SEPARATOR . $s;
$s = (!empty($config['assetsdir'])) ? $config['assetsdir'] : 'assets';
$assetsdir = $destdir . DIRECTORY_SEPARATOR . $s;

// 1. Rename folder if necessary
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

// 2. Create new folders if necessary
foreach ([
 ['admin','configs'],
 ['assets','admin_custom'],
 ['assets','classes'],
 ['assets','configs'],
 ['assets','css'],
 ['assets','images'],
 ['assets','module_custom'],
 ['assets','modules'],
 ['assets','user_plugins'],
 ['assets','plugins'],
 ['assets','templates'],
 ['lib','modules'],
] as $segs) {
    switch($segs[0]) {
        case 'admin':
            $fp = $admindir . DIRECTORY_SEPARATOR . $segs[1];
            break;
        case 'assets':
            $fp = $assetsdir . DIRECTORY_SEPARATOR . $segs[1];
            break;
        case 'lib':
            $fp = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . $segs[1];
            break;
        default:
            break 2;
    }
    if (!is_dir($fp)) @mkdir($fp, 0771, true);
    if (!is_dir($fp)) throw new LogicException("Could not create $fp directory");
    touch($fp . DIRECTORY_SEPARATOR . 'index.html');
}
touch($assetsdir . DIRECTORY_SEPARATOR . 'index.html');

// 3. Revert force-moved (by 2.2.90x upgrade) 'independent' modules from assets/modules to deprecated /modules
$wizard = wizard::get_instance();
$data = $wizard->get_data('version_info'); //version-datum from session
$fromvers = $data['version'];
if (version_compare($fromvers, '2.2.900') >= 0 && version_compare($fromvers, '2.2.910') < 0) {
    $fp = $assetsdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . '*';
    $dirs = glob($fp, GLOB_ONLYDIR);
    $d = '';
    foreach ($dirs as $fp) {
        $modname = basename($fp);
        if (!in_array($modname, ['MenuManager', 'CMSMailer', 'News'])) { //TODO exclude all non-core modules in files-tarball
            if (!$d) {
                $d = $destdir . DIRECTORY_SEPARATOR . 'modules';
                @mkdir($d, 0771, true);
            }
            $fp = realpath($fp);
            $to = $d . DIRECTORY_SEPARATOR . $modname;
            rename($fp, $to);
        }
    }
}

// 4. Move core modules to /lib/modules
foreach ([
 'AdminLog',
 'AdminSearch',
 'CMSContentManager',
 'CmsJobManager',
 'CoreAdminLogin',
 'CoreTextEditing',
 'FileManager',
 'FilePicker',
 'MicroTiny',
 'ModuleManager',
 'Navigator',
 'Search',
] as $modname) {
    $fp = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if (is_dir($fp)) {
        $to = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if (!is_dir($to)) {
            $d = realpath($fp); //it might be a link
            rename($d, $to);
            @unlink($fp);
        } else {
            utils::rrmdir($fp);
        }
    }
}

// 5. Move our ex-core modules to /assets/modules //TODO get all in archive
foreach (['DesignManager', 'MenuManager', 'CMSMailer', 'News'] as $modname) {
    $fp = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if (is_dir($fp)) {
        $to = $assetsdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if (!is_dir($to)) {
            $d = realpath($fp);
            rename($d, $to);
            @unlink($fp);
        } else {
            utils::rrmdir($fp);
        }
    }
}

if (version_compare($fromvers, '2.2.910') < 0) {
    $fp = $assetsdir . DIRECTORY_SEPARATOR . 'simple_plugins';
    if (is_dir($fp)) {
        rename($fp, $assetsdir . DIRECTORY_SEPARATOR . 'user_plugins');
    }
}
