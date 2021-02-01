<?php

use cms_installer\wizard\wizard;
use CMSMS\Crypto;
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
$s = (!empty($config['admin_path'])) ? $config['admin_path'] : 'assets';
$admindir = $destdir . DIRECTORY_SEPARATOR . $s;
$s = (!empty($config['assets_path'])) ? $config['assets_path'] : 'assets';
$assetsdir = $destdir . DIRECTORY_SEPARATOR . $s;
$s = (!empty($config['usertags_path'])) ? $config['usertags_path'] : '';
$plugsdir = ($s) ? $destdir . DIRECTORY_SEPARATOR . $s : $assetsdir . DIRECTORY_SEPARATOR . 'user_plugins';

// 0. Remove/replace redundant files
unlink($admindir . DIRECTORY_SEPARATOR . 'moduleinteface.php');

$modes = get_server_permissions();

// 1. Rename folder if necessary
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
// 1. rename any existing 2.3BETA plugins UDTfiles plus any format change?
$s = $assetsdir . DIRECTORY_SEPARATOR . 'user_plugins';
if ($s != $plugsdir && is_dir($s)) {
    rename($s, $plugsdir);
    touch($plugsdir . DIRECTORY_SEPARATOR . 'index.html');
}
$files = glob($plugsdir.DIRECTORY_SEPARATOR.'*.cmsplugin', GLOB_NOESCAPE | GLOB_NOSORT);
foreach ($files as $fp) {
    $to = $plugsdir.DIRECTORY_SEPARATOR.basename($fp, 'cmsplugin').'phphp'; //c.f. UserTagOperations::PLUGEXT
    rename($fp, $to);
}

// 2. Create new folders if necessary
$dirmode = $modes[3];
foreach ([
 ['admin','configs'],
 ['assets','admin_custom'],
 ['assets','classes'],
 ['assets','configs'],
 ['assets','styles'],
 ['assets','images'],
 ['assets','module_custom'],
 ['assets','modules'], //CHECKME iff using distinct non-core-modules place
 ['assets','plugins'],
// ['assets','resources'],
 ['assets','templates'],
 ['assets','themes'],
 ['tags',$plugsdir], //UDTfiles
 ['lib','modules'], //CHECKME iff using distinct core-modules place
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
        case 'tags':
            $fp = $segs[1];
            break;
        default:
            break 2;
    }
    if (!is_dir($fp)) @mkdir($fp, $dirmode, true);
    if (!is_dir($fp)) throw new Exception("Could not create $fp directory");
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
    if (!is_dir($fp)) @mkdir($fp, $dirmode, true);
    if (!is_dir($fp)) throw new Exception("Could not create $fp directory");
    touch($fp . DIRECTORY_SEPARATOR . 'index.html');
}

//3. remove redundant folders and contents if necessary
foreach ([
 ['lib', 'phpmailer'],
 ['lib', 'smarty'],
 ['lib', 'tasks'],
] as $segs) {
    $fp = $destdir . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $segs);
    if (is_dir($fp)) {
        if (!recursive_delete($fp)) {
            throw new Exception("Could not remove $fp directory");
        }
    }
}

// 5. siteuuid-file
$s = Crypto::random_string(72); //max byte-length of BCRYPT passwords
$p = -1;
while (($p = strpos($s, '\0', $p+1)) !== false) {
    $c = crc32(substr($s, 0, $p) . 'A') & 0xff;
    $s[$p] = $c;
}
$fp = $assetsdir.DIRECTORY_SEPARATOR.'configs'.DIRECTORY_SEPARATOR.'siteuuid.dat';
file_put_contents($fp, $s);
chmod($fp, $modes[0]);

/*
// 6. Revert force-moved (by 2.2.90x upgrade) 'independent' modules from assets/modules to deprecated /modules
$wizard = wizard::get_instance();
$data = $wizard->get_data('version_info'); //version-datum from session
$fromvers = $data['version'];
if (version_compare($fromvers, '2.2.900') >= 0 && version_compare($fromvers, '2.2.910') < 0) {
    $fp = $assetsdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . '*';
    $dirs = glob($fp, GLOB_ONLYDIR);
    $d = '';
    foreach ($dirs as $fp) {
        $modname = basename($fp);
        if (!in_array($modname, ['MenuManager', / *'CMSMailer',* / 'News'])) { //TODO exclude all non-core modules in files-tarball
            if (!$d) {
                $d = $destdir . DIRECTORY_SEPARATOR . 'modules';
                @mkdir($d, $dirmode, true);
            }
            $fp = realpath($fp);
            $to = $d . DIRECTORY_SEPARATOR . $modname;
            rename($fp, $to);
        }
    }
}

// 7. Move core modules to /lib/modules CHECKME
foreach ([
// 'AdminLog', internal
 'AdminLogin', // a.k.a. ModuleOperations::STD_LOGIN_MODULE
 'AdminSearch', // non-core?
 'CMSContentManager',
// 'CmsJobManager', reverted to core classes
 'CoreTextEditing',
 'FileManager',
 'FilePicker',
 'MicroTiny',
 'ModuleManager',
 'Navigator',
 'Search', // non-core?
] as $modname) {
    $fp = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if (is_dir($fp)) {
        $to = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if (!is_dir($to)) {
            $d = realpath($fp); //it might be a link
            rename($d, $to);
            @unlink($fp);
        } else {
            rrmdir($fp);
        }
    }
}

// 8. Move our ex-core modules to /assets/modules  CHECKME TODO get all in archive
foreach (['DesignManager', 'MenuManager', 'CMSMailer', 'News'] as $modname) {
    $fp = $destdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
    if (is_dir($fp)) {
        $to = $assetsdir . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $modname;
        if (!is_dir($to)) {
            $d = realpath($fp);
            rename($d, $to);
            @unlink($fp);
        } else {
            rrmdir($fp);
        }
    }
}
*/