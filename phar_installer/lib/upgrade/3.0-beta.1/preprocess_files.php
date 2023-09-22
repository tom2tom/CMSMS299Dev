<?php
use Exception;
use function cms_installer\get_app;
use function cms_installer\get_server_permissions;
use function cms_installer\rrmdir;

// vars in scope from includer: $destdir $upgrade_dir $version_info $smarty
$app = get_app();
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
 ['assets', 'jobs'],
 ['assets', 'js'],
 ['assets', 'layouts'],
 ['assets', 'media'], // replaces deprecated 'images'
 ['assets', 'module_custom'],
 ['assets', 'modules'], // possible temp usage during upgrades
 ['assets', 'plugins'],
 ['assets', 'styles'], // replaces deprecated 'css'
 ['assets', 'themes'], // for future use
// ['assets', 'vendor'], //TODO needed?
 ['lib', 'assets'], // deprecated container for links
 ['lib', 'font'],
 ['lib', 'js'],
 ['lib', 'layouts'],
 ['lib', 'security'],
 ['lib', 'vendor'],
 ['tags', $plugsdir], //UDTfiles
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
        if (!is_dir($fp)) {
            throw new Exception("Could not create $fp directory");
        }
    } else {
        @chmod($fp, $dirmode); // TODO recursive
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

// 1A. folder-content moves
function reposition (string $r, string $s)
{
    $files = glob($r . DIRECTORY_SEPARATOR . '*');
    if ($files) {
        $l = strlen($r);
        foreach ($files as $fp) {
            $to = $s . substr($fp, $l);
            rename($fp, $to);
        }
    }
    rrmdir($r);
    symlink($s, $r); // deprecated replacement
}

$from = $assetsdir . DIRECTORY_SEPARATOR;
$r = $from . 'images';
$s = $from . 'media';
reposition($r, $s);

$r = $from . 'css';
$s = $from . 'styles';
reposition($r, $s);

$r = $from . 'templates';
$s = $from . 'layouts';
reposition($r, $s);

$r = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'templates';
$s = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'layouts';
reposition($r, $s);

$from = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR;
$r = $from . 'images';
$s = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'media';
reposition($r, $s);

$r = $from . 'templates';
$s = $destdir . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'layouts';
reposition($r, $s);

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
/*
$s = $assetsdir . DIRECTORY_SEPARATOR . 'css';
if (is_dir($s)) {
    $to = $assetsdir . DIRECTORY_SEPARATOR . 'styles';
    rename($s, $to);
    touch($to . DIRECTORY_SEPARATOR . 'index.html');
}
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
if ($to != $config['config_file']) {
    $app->set_config_val('config_file', $to);
}

//unlink($admindir . DIRECTORY_SEPARATOR . 'moduleinteface.php'); // manifest should handle this
@unlink($admindir . DIRECTORY_SEPARATOR . 'debug.log');
@unlink($admindir . DIRECTORY_SEPARATOR . 'error_log');