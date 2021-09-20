#!/usr/bin/env php
<?php

use cms_installer\installer_base;
use CMSMS\SingleItem;

const SVNROOT = 'http://svn.cmsmadesimple.org/svn/cmsmadesimple';
const INSTALLERTOP = 'installer'; // extended-intaller top folder name, no trailing separator

/* NOTE
This REQUIRES PHP's phar extension, and usually one of the archivers zip, zlib, bzip2
This benefits from PHP extension fileinfo - probably built by default
*/
if (!extension_loaded('phar')) {
    exit('PHP\'s phar extension is required for this process');
}
if (ini_get('phar.readonly')) {
    exit('phar.readonly must be turned OFF in your php.ini');
}

// setup
$cli = php_sapi_name() == 'cli';
$owd = getcwd();
$script_file = basename(__FILE__);

if ($cli) {
    // ensure we are in the correct directory
    if (!is_file(joinpath($owd, $script_file))) {
        die('This script must be executed from the same directory as the '.$script_file.' script');
    }
}

$installerdir = dirname($owd); //parent, probably 'phar_installer'
$sourcedir = joinpath($installerdir, 'sources'); //place for accumulating sourcefiles that will be passed to the installer
$outdir = joinpath($installerdir, 'out'); //place for this script's results/output

// regex patterns for source files/dirs to NOT be processed by the installer.
// all exclusion checks are against sources-tree root-dir-relative filepaths,
// after converting any windoze path-sep's to *NIX form
// NOTE: otherwise empty folders retain, or are given, respective index.html's
// so that they are not ignored by PharData when processing
$all_excludes = [
'~\.git.*~',
'~\.md$~i',
'~\.svn~',
'~svn\-~',
'~index\.html?$~',
'~config\.php$~',
'~siteuuid\.dat$~',
'~\.bak$~',
'/~$/',
'~\.#~',
'~UNUSED~',
'~DEVELOP~',
'~HIDE~',
];

// members of $src_excludes which need double-check before exclusion to confirm they're 'ours'
$src_checks = ['scripts', 'tmp', 'tests'];

$s = basename($installerdir);
$src_excludes = [
-4 => "~$s~",
-3 => '~scripts~',
-2 => '~tmp~',
-1 => '~tests~',
] + $all_excludes;
/*
// members of $phar_excludes which need double-check before exclusion to confirm they're 'ours'
$phar_checks = ['build', 'data', 'out'];

$phar_excludes = [
'~build~',
'~data~',
'~out~',
] + $all_excludes;
//'/README.*   REMOVE THIS GAP IF UNCOMMENTED   /',
$phar_excludes = $all_excludes;
*/
$archive_only = 0;
$clean = 1;
$checksums = 1;
$pack = 'zip'; //default archive type
$rename = 1;
$sourceuri = 'file://'; // file-set source
$verbose = 0;

function output_usage()
{
    echo <<< 'EOS'
php build_release.php [options]
options:
  -h|--help      show this message
  -a|--archive   create a source-files archive instead of a normal installer
  -c|--clean     toggle cleaning of old output directories (default is on)
  -k|--checksums toggle creation of checksum files (default is on)
  -p|--pack      specify the type of compression for the created files,
                 one of: zip (the default), zlib, bzip2,
                 or none to create uncompressed tar archives
  -r|--rename    toggle renaming of .phar file to .php (default is on)
  -u|--uri       specify files source, one of
                   file://detail or svn://detail or git://detail
                 With 'file', the detail may be 'local' or omitted, in order
                 to use the files in the local tree, otherwise a filesystem
                 path of a readable directory
                 With 'svn', the detail need only be 'trunk' or the branch
                 or tag relative to the CMSMS svn url root, or may be
                 omitted entirely if trunk is to be used.
                 With 'git', the detail is the urlpath of the repo.
  -v|--verbose   increment verbosity level (can be used multiple times)
  -z|--zip       toggle zipping the output (phar or php) into a .zip file (default is on)
                 deprecated equivalent to -p none
EOS;
}

function current_root(string $dir = '') : string
{
    if (!$dir) {
        $dir = __DIR__;
    }
    while ($dir !== '.' && !is_dir(joinpath($dir, 'admin')) && !is_dir(joinpath($dir, 'phar_installer'))) {
        $dir = dirname($dir);
    }
    return $dir;
}

function joinpath(...$segments) : string
{
    $fp = implode(DIRECTORY_SEPARATOR, $segments);
    return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $fp);
}

function rrmdir(string $topdir, bool $keepdirs = false, bool $keeptop = false) : bool
{
    if (is_dir($topdir)) {
        $res = true;
        //TODO to prevent massive duplication, use recursive readdir()'s instead
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $topdir,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $fp) {
            if (is_dir($fp)) {
                if (is_link($fp)) {
                    if ($keepdirs || !@unlink($fp)) {
                        $res = false;
                    }
                } elseif ($keepdirs || !@rmdir($fp)) {
                    $res = false;
                }
            } elseif (is_link($fp)) {
                if (!@unlink($fp)) {
                    $res = false;
                }
            } elseif (!@unlink($fp)) {
                $res = false;
            }
        }
        if ($res && !($keeptop || $keepdirs)) {
            $res = @rmdir($topdir);
        }
        return $res;
    }
    return false;
}

function rchmod(string $topdir) : bool
{
    if (is_dir($topdir)) {
        //TODO to prevent massive duplication, use recursive readdir()'s instead
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $topdir,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        $res = true;
        foreach ($iter as $fp) {
            $mode = (is_dir($fp)) ? 0777 : 0666; // generic perms - actuals will be used during installation
            if (!@chmod($fp, $mode)) {
                $res = false;
            }
        }
        return $res;
    }
    return @chmod($topdir, 0666);
}

function valid_link($fp)
{
    $target = readlink($fp);
    if (@is_dir($fp)) {
        return $target;
    }
    if ($target) {
        if (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $target)) { //absolute path
            if (@is_file($target)) {
                return $target;
            }
        } elseif (realpath(joinpath(dirname($fp), $target))) {
            return $target;
        }
    }
    return false;
}

function get_alternate_files() : bool
{
    global $sourceuri, $sourcedir;

    if (strncmp($sourceuri, 'file://', 7) == 0) {
        $sourcedir = substr($sourceuri, 7);
        if (is_dir($sourcedir)) {
            //use files in that place
            return true;
        }
    } elseif (strncmp($sourceuri, 'svn://', 6) == 0) {
        $remnant = substr($sourceuri, 6);
        $url = SVNROOT;
        switch (strtolower(substr($remnant(0, 4)))) {
            case '':
            case 'trun':
                $url .= '/trunk';
                break;
            case 'tags':
            case 'bran':
                $url .= '/'. strtolower($remnant);
                break;
            case 'http':
                $url = $remnant;
                // no break
            case 'svn.':
                $url = 'http://'.$remnant;
                break;
            default:
                return false;
        }

        $cmd = escapeshellcmd("svn export -q $url $sourcedir");

        verbose(1, "INFO: retrieving files from SVN ($url)");
        system($cmd, $retval);
        return true; //$retval == 0?
    } elseif (strncmp($sourceuri, 'git://', 6) == 0) {
        $url = 'https://'.substr($sourceuri, 6);
        $cmd = escapeshellcmd("git clone -q $url $sourcedir");

        verbose(1, "INFO: retrieving files from GIT ($url)");
        system($cmd, $retval);
        return true; //$retval == 0?;
    }
    return false;
}

// get a filtered set of source-files from the original tree into $sourcedir
function copy_local_files()
{
    global $sourcedir, $src_excludes, $src_checks, $verbose;

    $localroot = current_root();

    // default config settings
    $fp = joinpath(dirname(__DIR__), 'lib', 'assets', 'installer.ini');
    $config = (is_file($fp)) ? parse_ini_file($fp, false, INI_SCANNER_TYPED) : [];
    foreach ($config as $key => &$val) {
        switch ($key) {
            case 'coremodules':
            case 'extramodules':
                if (!is_array($val)) {
                    $val = [$val];
                }
                break;
            default:
                $val = null;
        }
    }
    unset($val);
    // custom config settings
    $fp = $localroot.DIRECTORY_SEPARATOR.'installer.ini';
    if (!is_file($fp)) {
        $fp = __DIR__.DIRECTORY_SEPARATOR.'installer.ini';
    }
    $xconfig = (is_file($fp)) ? parse_ini_file($fp, false, INI_SCANNER_TYPED) : [];
    foreach ($xconfig as $key => $val) {
        switch ($key) {
            case 'extramodules':
                if (!is_array($val)) {
                    $val = [$val];
                }
                if (isset($config[$key])) {
                    $config[$key] = array_merge($config[$key], $val);
                } else {
                    $config[$key] = $val;
                }
                // no break
            default:
                break;
        }
    }
    $modules = array_merge($config['coremodules'] ?? [], $config['extramodules'] ?? []);
    unset($config, $xconfig);
    if ($modules) {
        $modcheck = DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR;
        $mclen = strlen($modcheck);
    } else {
        $modcheck = ',,,,'; // something not matchable in a filepath
        $mclen = 2;
    }

    $len = strlen($localroot.DIRECTORY_SEPARATOR);
    $xchecks = [];
    foreach ($src_checks as $name) {
        $xchecks[$name] = $len + strlen($name);
    }

    verbose(1, "INFO: Copying source files from $localroot to $sourcedir");
    //TODO to prevent massive duplication, use recursive readdir()'s instead
    //TODO nicely deal with inaccessible dirs
    try {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $localroot,
                FilesystemIterator::KEY_AS_FILENAME |
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS |
                FilesystemIterator::UNIX_PATHS //|
//              FilesystemIterator::FOLLOW_SYMLINKS too bad if links not relative !!
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );
    } catch (Throwable $t) {
        die('ERROR: source files iterator failure: '.$t->GetMessage()."\n");
    }

    foreach ($iter as $fn => $fp) {
        // ignore unwanted filepath patterns
        foreach ($src_excludes as $excl) {
            if (preg_match($excl, $fp, $matches, 0, $len)) {
                $name = $matches[0];
                if (isset($xchecks[$name])) {  // isset($xchecks[$fn]) && ...
                    $cfp = $localroot.DIRECTORY_SEPARATOR.$name;
                    if (strncmp($fp, $cfp, $xchecks[$name]) != 0 || 0 || 0) { //TODO $fp[$xchecks[$name] + 1] NOT DIRECTORY_SEPARATOR | nothing
                        continue;
                    }
                }
                if ($verbose >= 2) {
                    $relpath = substr($fp, $len);
                    verbose(2, "EXCLUDED: $relpath (matched pattern $excl)");
                }
                continue 2;
            }
        }
        // ignore unwanted modules
        if (($p = strpos($fp, $modcheck)) !== false) {
            $ep = strpos($fp, DIRECTORY_SEPARATOR, $p + $mclen);
            if ($ep !== false) {
                $modname = substr($fp, $p + $mclen, $ep - $p - $mclen);
                $parent = false;
            } else {
                $modname = $fn;
                $parent = true;
            }
            if (!in_array($modname, $modules)) {
                if ($parent && $verbose >= 2) {
                    verbose(2, "EXCLUDED: unwanted module $modname");
                }
                continue;
            }
        }

        $relpath = substr($fp, $len);
        $tp = joinpath($sourcedir, $relpath);

        if (@is_dir($fp)) {
            if (!@is_link($fp)) {
                mkdir($tp, 0777, true); // generic perms, replaced during istallation
            } else {
                $target = readlink($fp);
                symlink($target, $tp);
                verbose(2, "MIGRATED LINK $fn to $tp");
            }
        } elseif (@is_link($fp)) {
            if (($target = valid_link($fp))) {
                // migrate to corresponding $sourcedir-link
                symlink($target, $tp);
                verbose(2, "MIGRATED LINK $fn to $tp");
            } else {
                verbose(0, "ERROR: failed to migrate link $fp");
            }
        } elseif (@is_file($fp)) {
            copy($fp, $tp);
            chmod($tp, 0666); // generic perms here
            verbose(2, "COPIED $fn to $tp");
        }
    }

    $config = []; // to be populated by the inclusion
    $fp = $localroot.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'config.php';
    if (!is_file($fp)) {
        $fp = $localroot.DIRECTORY_SEPARATOR.'config.php';
    }
    try {
        require_once $fp;
    } catch (Throwable $t) {
        die('ERROR: missing config file');
    }

    $fp = $sourcedir.DIRECTORY_SEPARATOR;
    if (!empty($config['admin_dir'])) {
        @rename($fp.$config['admin_dir'], $fp.'admin');
    } elseif (!empty($config['admin_path'])) {
        @rename($fp.$config['admin_path'], $fp.'admin');
    } elseif (!empty($config['admin_url'])) {
        if (!empty($config['root_url'])) {
            $s = substr($config['admin_url'], strlen($config['root_url']));
            $t = trim($s, ' /');
            if ($t) {
                $t = '/'.$t;
            } else {
                $t = '/assets';
            }
        } else {
            $s = rtrim($config['admin_url'], ' /');
            $p = strrpos($s, '/');
            if ($p !== false) {
                $t = substr($s, $p);
            } else {
                $t = '/assets';
            }
        }
        @rename($fp.$t, $fp.'admin');
    }

    if (!empty($config['uploads_path'])) {
        @rename($fp.$config['uploads_path'], $fp.'uploads');
    } elseif (!empty($config['uploads_url'])) {
        if (!empty($config['root_url'])) {
            $s = substr($config['uploads_url'], strlen($config['root_url']));
            $t = trim($s, ' /');
            if ($t) {
                $t = '/'.$t;
            } else {
                $t = '/uploads';
            }
        } else {
            $s = rtrim($config['uploads_url'], ' /');
            $p = strrpos($s, '/');
            if ($p !== false) {
                $t = substr($s, $p);
            } else {
                $t = '/uploads';
            }
        }
        @rename($fp.$t, $fp.'uploads');
    }
    rrmdir($fp.'uploads', false, true);

    if (!empty($config['assets_path'])) {
        @rename($fp.$config['assets_path'], $fp.'assets');
    } elseif (!empty($config['assets_url'])) {
//        @rename($fp.TODOfunc($config['assets_url']), $fp.'assets');
    }

    if (!empty($config['usertags_path'])) {
        @rename($fp.$config['usertags_path'], $fp.'assets'.DIRECTORY_SEPARATOR.'user_plugins');
    }
    $fp2 = joinpath($sourcedir, 'assets', '');
    // no change to {...[assets]/templates/*, ...[assets]/styles/*}, those files will be recorded in the relevant table
    foreach ([/*'templates','styles',*/'user_plugins'] as $name) {
        @rrmdir($fp2.$name, false, true);
    }

    $dh = opendir($sourcedir);
    while (($name = readdir($dh)) !== false) {
        $fp2 = $fp.$name;
        if (!($name == 'index.php' || is_dir($fp2))) {
            unlink($fp2);
        }
    }
    closedir($dh);
}

// get a filtered set of ...our_path/libs files into TODO WHERE?
function copy_installer_files()
{
    global $all_excludes, $xchecks;

    $cleaninstalls = $TODO; //desination filepath

    $fp = dirname(__DIR__).DIRECTORY_SEPARATOR.'lib';
    try {
        //TODO to prevent massive duplication, use recursive readdir()'s instead
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $fp,
                FilesystemIterator::KEY_AS_FILENAME |
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS |
                FilesystemIterator::UNIX_PATHS //|
//             FilesystemIterator::FOLLOW_SYMLINKS too bad if links not relative !!
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );
    } catch (Throwable $t) {
        die('ERROR: source files iterator failure: '.$t->GetMessage()."\n");
    }

    $len = strlen(dirname(__DIR__).DIRECTORY_SEPARATOR);

    foreach ($iter as $fn => $fp) {
        // ignore unwanted filepath patterns
        foreach ($all_excludes as $excl) {
            if (preg_match($excl, $fp, $matches, 0, $len)) {
                $name = $matches[0];
                if (isset($xchecks[$name])) {  // isset($xchecks[$fn]) && ...
                    //TODO use substr_compare()
                    $cfp = $localroot.DIRECTORY_SEPARATOR.$name;
                    if (strncmp($fp, $cfp, $xchecks[$name]) != 0 || 0 || 0) { //TODO $fp[$xchecks[$name] + 1] NOT DIRECTORY_SEPARATOR | nothing
                        continue;
                    }
                }
                if ($verbose >= 2) {
                    $relpath = substr($fp, $len);
                    verbose(2, "EXCLUDED: $relpath (matched pattern $excl)");
                }
                continue 2;
            }
        }

        $relpath = substr($fp, $len);
        $tp = joinpath($cleaninstalls, $relpath);

        if (@is_dir($fp)) {
            if (!@is_link($fp)) {
                mkdir($tp, 0777, true); // generic perms, replaced during istallation
            } else {
                $target = readlink($fp);
                symlink($target, $tp);
                verbose(2, "MIGRATED LINK $fn to $tp");
            }
        } elseif (@is_link($fp)) {
            if (($target = valid_link($fp))) {
                // migrate to corresponding $sourcedir-link
                symlink($target, $tp);
                verbose(2, "MIGRATED LINK $fn to $tp");
            } else {
                verbose(0, "ERROR: failed to migrate link $fp");
            }
        } elseif (@is_file($fp)) {
            copy($fp, $tp);
            chmod($tp, 0666); // generic perms here
            verbose(2, "COPIED $fn to $tp");
        }
    }
}

function get_version_php(string $startdir) : string
{
    $fp = joinpath($startdir, 'lib', 'version.php');
    if (is_file($fp)) {
        return $fp;
    }
    return '';
}

// recursive method
function create_checksums(string $dir, string $salt) : array
{
    global $sourcedir;

    $len = strlen($sourcedir); //paths have leading DIRECTORY_SEPARATOR
    $out = [];
    $dh = opendir($dir);

    while (($name = readdir($dh)) !== false) {
        if ($name == '.' || $name == '..') {
            continue;
        }

        $fp = joinpath($dir, $name);
        if (@is_dir($fp)) {
            if (!@is_link($fp)) {
                $tmp = create_checksums($fp, $salt); //recurse
                if ($tmp) {
                    $out = array_merge($out, $tmp);
                }
            } else {
                $relpath = substr($fp, $len);
                $target = readlink($fp);
                $out[$relpath] = md5($salt.$target);
            }
        } elseif (@is_link($fp)) {
            if (($target = valid_link($fp))) {
                $relpath = substr($fp, $len);
                $out[$relpath] = md5($salt.$target);
            } else {
                verbose(0, 'WARNING: no checksum for invalid link '.$fp);
            }
        } elseif (@is_file($fp)) {
            $relpath = substr($fp, $len);
            $out[$relpath] = md5($salt.md5_file($fp));
        }
    }
    return $out;
}

function create_checksum_dat()
{
    global $outdir, $sourcedir, $version_php, $version_num;

    verbose(1, 'INFO: Creating checksum file');
    $salt = md5_file($version_php);

    $out = create_checksums($sourcedir, $salt);
    $outfile = joinpath($outdir, "cmsms-{$version_num}-checksum.dat");

    $fh = fopen($outfile, 'c');
    if (!$fh) {
        echo "WARNING: problem opening $outfile for writing\n";
    } else {
        foreach ($out as $key => $val) {
            fprintf($fh, "%s --::-- %s\n", $val, $key);
        }
        fclose($fh);
    }
}

function create_source_archive()
{
    global $outdir, $pack, $packext;

    @mkdir($outdir, 0777, true); // generic perms, replaced during istallation
    $archpath = joinpath($outdir, 'sources.phar');
    @unlink($archpath);
    if ($packext) {
        @unlink($archpath.$packext);
    }

    try {
        verbose(1, 'INFO: Creating sources archive '.basename($archpath.$packext));
        $phar = new PharData($archpath);
        //get files
        $phar->buildFromDirectory($sourcedir);
        //backfill empty dirs
        try {
            //TODO to prevent massive duplication, use recursive readdir()'s instead
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $sourcedir,
                    FilesystemIterator::KEY_AS_FILENAME |
                    FilesystemIterator::CURRENT_AS_PATHNAME |
                    FilesystemIterator::UNIX_PATHS |
                    FilesystemIterator::FOLLOW_SYMLINKS
                ),
                RecursiveIteratorIterator::SELF_FIRST
            );
        } catch (Throwable $t) {
            die('ERROR: source files iterator failure: '.$t->GetMessage()."\n");
        }

        $len = strlen($sourcedir.DIRECTORY_SEPARATOR);
        foreach ($iter as $fn => $tp) {
//          $fn = basename($tp);
//          if ($fn == '.') {
            if (is_dir($tp)) {
                $dir = dirname($tp);
                $iter2 = new FilesystemIterator($dir);
                if (!$iter2->valid()) {
                    $phar->addEmptyDir(substr($dir, $len));
                }
                unset($iter2);
            }
        }

        switch ($pack) {
            case 'zip':
                $phar->convertToData(Phar::ZIP, Phar::NONE, $packext);
                break;
            case 'zlib':
                $phar->compress(Phar::GZ, $packext); //TODO care with '.' chars in filename before extension
                break;
            case 'bzip2':
                $phar->compress(Phar::BZ2, $packext); //TODO care with '.' chars in filename before extension
                break;
            default:
                $phar->convertToData(Phar::TAR, Phar::NONE, $packext);
                break;
        }

        unset($phar); //close it
        if ($packext) {
            unlink($archpath);
        }
    } catch (Throwable $t) {
        die('ERROR: sources archive creation failed : '.$t->GetMessage()."\n");
    }
}

function create_phar_installer()
{
    global $installerdir, $outdir, $version_num, $packext, $rename;

    $archname = 'cmsms-'.$version_num.'-install'.$packext;
    $destname = 'cmsms-'.$version_num.'-install.phar';
    $destname2 = 'cmsms-'.$version_num.'-install.php'; // for renaming

    $content = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'initiator.php');
    if (!$content) {
        die("ERROR: phar stub-file 'initiator.php' is missing\n");
    }
    $init = sprintf($content, $archname);
    if ($init == $content) {
        die("ERROR: phar stub-file 'initiator.php' is malformed\n");
    }

    $fp = $outdir.DIRECTORY_SEPARATOR.$destname;
    if (is_file($fp)) {
        unlink($fp);
    }

    $phar = new Phar($fp, 0, $destname);
    $phar->setSignatureAlgorithm(Phar::SHA1);
//    $phar->interceptFileFuncs();

    $phar->startBuffering();

    $phar->addFromString('initiator.php', $init);
    $phar->addFromString('cliinitiator.php', <<<EOS
#!/usr/bin/env php
<?php
require __DIR__.DIRECTORY_SEPARATOR.'initiator.php';

EOS
    );

    $fp = $outdir.DIRECTORY_SEPARATOR.$archname;
    chmod($fp, 0664);
    $phar->addFile($fp, $archname);
    $fp = $installerdir.DIRECTORY_SEPARATOR.'README-PHAR.TXT';
    chmod($fp, 0664);
    $phar->addFile($fp, 'README-PHAR.TXT');

    $phar->setDefaultStub('cliinitiator.php', 'initiator.php');

    $phar->stopBuffering();

    unset($phar); //close it

    $fp = $outdir.DIRECTORY_SEPARATOR.$destname; // again
    chmod($fp, 0664);
    if ($rename) {
        // rename to a php file to enable execution on pretty-much all hosts
        verbose(1, 'INFO: Renaming phar file to php');
        $tp = $outdir.DIRECTORY_SEPARATOR.$destname2;
        rename($fp, $tp);
    }
}

function create_extended_installer()
{
    global $installerdir, $outdir, $version_num, $arch, $nfiles, $ndirs, $pack, $packext;

    $nfiles = 0;
    $ndirs = 0;

    $skips = ['build', 'data', 'out'];
//CHECKME keep these ? demo-content installer needs them?
//  $arr = installer_base::UPLOADFILESDIR;
//  $skips[] = end($arr);
//  $arr = installer_base::CUSTOMFILESDIR;
//  $skips[] = end($arr);
    $scans = [];
    $dirs = glob($installerdir.DIRECTORY_SEPARATOR.'*', GLOB_NOSORT | GLOB_NOESCAPE | GLOB_ONLYDIR);
    $len = strlen($installerdir) + 1;

    foreach ($dirs as $fp) {
        $name = basename($fp);
        if (!in_array($name, $skips)) {
            $scans[$name] = $len;
        }
    }

    $outfile = joinpath($outdir, 'cmsms-'.$version_num.'-install'.$packext);
    verbose(1, "INFO: compressing installation and system files into $outfile");

    switch ($pack) {
        case 'zip':
            // we use an actual zip, not phardata-converted-to-zip, to avoid a symlink-related bug
            // and it's mucho faster
            $arch = new ZipArchive();
            $arch->open($outfile, ZipArchive::OVERWRITE | ZipArchive::CREATE);
            $fp = joinpath($installerdir,'README.TXT');
            $arch->addFile($fp, INSTALLERTOP.'/README.TXT');
            $arch->setExternalAttributesName(INSTALLERTOP.'/README.TXT', ZipArchive::OPSYS_UNIX, 0664 << 16);
            $fp = joinpath($installerdir,'index.php');
            $arch->addFile($fp, INSTALLERTOP.'/index.php');
            $arch->setExternalAttributesName(INSTALLERTOP.'/index.php', ZipArchive::OPSYS_UNIX, 0660 << 16);
            $nfiles = 2;
            break;
        case 'zlib':
        case 'bzip2':
        case 'none':
            // NOTE subsequent processing by phar removes everything after the
            // 1st '.' in this supplied filename, before appending the new extension
            // hence a dummy for now, later renamed
            $workfile = joinpath($outdir, 'installer.work');
            $arch = new PharData($workfile);
            $arch->startBuffering();
            $fp = joinpath($installerdir,'README.TXT');
            chmod($fp, 0664);
            $arch->addFile($fp, INSTALLERTOP.'/README.TXT');
            $fp = joinpath($installerdir,'index.php');
            chmod($fp, 0660);
            $arch->addFile($fp, INSTALLERTOP.'/index.php');
            $nfiles = 2;
            break;
        default:
            return;
    }

    foreach ($scans as $name => $skip) {
        $fp = $installerdir.DIRECTORY_SEPARATOR.$name;
        populatetree($fp, $skip);
    }

    try {
        switch ($pack) {
            case 'zip':
                $arch->close();
                break;
            case 'zlib':
            case 'bzip2':
            case 'none':
                $arch->stopBuffering();
                switch ($pack) {
                    case 'zlib':
                        $arch->convertToData(Phar::TAR, Phar::GZ, $packext);
                        break;
                    case 'bzip2':
                        $arch->convertToData(Phar::TAR, Phar::BZ2, $packext);
                        break;
                    case 'none':
                        $arch->convertToData(Phar::TAR, Phar::NONE, $packext);
                        break;
                }
                unset($arch); // close it
                $fp = joinpath($outdir, 'installer'.$packext);
                rename($fp, $outfile);
                unlink($workfile);
                break;
        }
        verbose(1, "INFO: added $nfiles files and $ndirs empty directories to the output archive");
    } catch (Throwable $t) {
        verbose(0, 'ERROR: creating extended installer failed: '.$t->getMessage());
    }
}

function populatetree($dirpath, $baselen)
{
    global $arch, $all_excludes, $nfiles, $ndirs, $installerdir, $pack;

    $dh = opendir($dirpath);
    if ($dh == false) {
        throw new Exception('Directory "'.$dirpath.'" cannot be read');
    }
    $len = strlen($installerdir) + 1; //TODO calc this once-only
    $sfiles = $nfiles; // baselines for empty-folder check
    $sdirs = $ndirs;

    while (($name = readdir($dh)) !== false) {
        if ($name == '.' || $name == '..') {
            continue;
        }
        $fp = $dirpath.DIRECTORY_SEPARATOR.$name;
        // check and store 'portable' sub-paths
        $relpath = strtr(substr($fp, $baselen), '\\', '/');
        if (strncmp($relpath, 'lib', 3) == 0) {
            // screen items in the $installerdir/lib tree only (not yet sanitized)
            foreach ($all_excludes as $excl) {
                if (preg_match($excl, $fp, $matches, 0, $len)) {
//                  $name = $matches[0];
//                  if (isset($xchecks[$name])) {
//                      if (substr_compare($fp, $name, $len, $xchecks[$name]) != 0) { //TODO $fp[$xchecks[$name] + 1] NOT DIRECTORY_SEPARATOR | nothing
//                          continue;
//                      }
//                  }
                    verbose(2, "EXCLUDED: $relpath (matched pattern $excl)");
                    continue 2;
                }
            }
        }
        // keep this one
        $relpath = INSTALLERTOP.'/'.$relpath;
        if (@is_dir($fp)) {
            if (!@is_link($fp)) {
                $iter = new FilesystemIterator($fp);
                $check = $iter->valid();
                unset($iter);
                if ($check) {
                    populatetree($fp, $baselen); //recurse
                } else {
/*                  $arch->addEmptyDir($relpath);
                    ++$ndirs;
*/
                    $arch->addFromString($relpath.'/index.html', '');
                    ++$nfiles;
                }
            } elseif (($target = valid_link($fp))) {
                if ($pack == 'zip') {
                    // a zip archive cannot include (valid) links to folders, so we add a proxy
                    $arch->addFromString($relpath.' FOLDER ', strtr($target, '\/', '||').' SYMLINK PROXY', "TODO: convert this to a link to $target");
                    $s = substr($fp, $baselen); //hide the INSTALLERTOP.'/' prefix
                    verbose(0, "NOTE: folder-link $s (target: $target) will need to be re-created after the extended intaller is unpacked");
                } else {
                    $arch->addFile($fp, $relpath);
                }
                ++$nfiles;
            } else {
                verbose(0, "WARNING: ignored invalid link $fp");
            }
        } elseif (@is_link($fp)) {
            if (($target = valid_link($fp))) {
                try {
                    $arch->addFile($fp, $relpath);
                    ++$nfiles;
                } catch (Throwable $t) {
                    verbose(0, "WARNING: ignored invalid link $fp (target: $target)");
                }
            } else {
                verbose(0, "WARNING: ignored invalid link $fp");
            }
        } elseif (@is_file($fp)) {
            $arch->addFile($fp, $relpath);
            ++$nfiles;
        }
    } // $name != false
    closedir($dh);
    if ($sfiles == $nfiles && $sdirs == $ndirs) {
        $fp = $dirpath.DIRECTORY_SEPARATOR.'index.html';
        $relpath = strtr(substr($fp, $baselen), '\\', '/');
        $arch->addFromString(INSTALLERTOP.'/'.$relpath, '');
        ++$nfiles;
    }
}

function verbose(int $lvl, string $msg)
{
    global $verbose, $cli;

    if ($verbose >= $lvl) {
        if ($cli) {
            echo $msg.PHP_EOL;
        } else {
            echo $msg.'<br/>';
        }
    }
}

// our "main" functionality
$config_file = str_replace('.php', '.ini', $script_file);
$fp = joinpath($owd, $config_file);
if (is_readable($fp)) {
    $config = parse_ini_file($fp);
    if ($config !== false) {
        verbose(1, "INFO: read config data from $config_file");
        extract($config);
    } else {
        verbose(0, "ERROR: Problem processing config file: $config_file");
    }
}

if ($cli) {
    $options = getopt('ahckru:vz', [
    'archive',
    'help',
    'clean',
    'checksums',
    'rename',
    'uri',
    'verbose',
    'zip'
    ]);

    if ($options) {
        foreach ($options as $k => $v) {
            switch ($k) {
             case 'a':
             case 'archive':
                $archive_only = !$archive_only;
                break;
             case 'c':
             case 'clean':
                $clean = !$clean;
                break;
             case 'k':
             case 'checksums':
                $checksums = !$checksums;
                break;
             case 'v':
             case 'verbose':
                ++$verbose;
                break;
             case 'h':
             case 'help':
                output_usage();
                exit;
             case 'p':
             case 'pack':
                $pack = $v;
                // no break
             case 'r':
             case 'rename':
                $rename = !$rename;
                break;
             case 'u':
             case 'uri':
                if (!preg_match('~^(file|svn|git)://~', $v)) {
                    die("$v is not valid for the source-uri parameter");
                }
                if (strncmp($v, 'file://', 7) == 0) {
                    $fp = substr($v, 7);
                    if ($fp === '' || $fp == 'local') {
                        $v = 'file://';
                    } elseif (!is_dir($fp) || !is_readable($fp)) {
                        die("The path specified in the uri parameter ($fp) is not a valid directory");
                    }
                }
                $sourceuri = $v;
                break;
             case 'z':
             case 'zip':
                $pack = 'none';
                break;
            }
        }
    }
} else {
    echo '<br/>';
} //cli

if (empty($pack)) {
    $pack = 'zip';
}
switch ($pack) {
    case 'zip':
    case 'zlib':
        if (!extension_loaded($pack)) {
            die("ERROR: PHP's $pack extension is required for this process");
        }
        $packext = ($pack == 'zip') ? '.zip' : '.tar.gz';
        break;
    case 'bzip2':
        if (!extension_loaded('bz2')) {
            die("ERROR: PHP's bz2 extension is required for this process");
        }
        $packext = '.tar.bz2';
        break;
    default:
        if ($pack != 'none') {
            die("ERROR: Unrecognized archive compression type '$pack'");
        }
        $packext = '.tar';
        break;
}

try {
    if (!is_dir($installerdir) || !is_file(joinpath($installerdir, 'index.php'))) {
        die('ERROR: Problem finding source files in '.$installerdir);
    }

    if (!is_dir($outdir)) {
        @mkdir($outdir, 0777, true); // generic perms, pending actuals for istallation
    } elseif ($clean) {
        verbose(1, 'INFO: Removing old output file(s)');
        rrmdir($outdir, false, true);
    }
    // used for accumulating sources
    if (!is_dir($sourcedir)) {
        mkdir($sourcedir, 0777, true);
    } else {
        rrmdir($sourcedir, false, true);
    }

    if (!is_dir($outdir) || !is_dir($sourcedir)) {
        die('Problem creating working directories: '.$outdir.' and/or '.$sourcedir);
    }

    $fp = joinpath($installerdir, 'lib', 'classes', 'class.installer_base.php');
    require_once $fp;
    $arr = installer_base::CONTENTXML;
    $xmlfile = joinpath($installerdir, ...$arr);
    if (!is_file($xmlfile)) {
        $localroot = current_root();
        $CMS_JOB_TYPE = 2; //in-scope for included file
        $fp = joinpath($localroot, 'lib', 'include.php');
        try {
            require_once $fp;
        } catch (Throwable $t) {
            die('Failed to generate demo content: no access to CMSMS system resources');
        }
        $arr = installer_base::UPLOADFILESDIR;
        $uploadspath = joinpath($installerdir, ...$arr);
        $arr = installer_base::CUSTOMFILESDIR;
        $workerspath = joinpath($installerdir, ...$arr);
        $db = SingleItem::Db();
        $space = @require_once joinpath($installerdir, 'lib', 'iosite.functions.php');
        if ($space === false) {
            die('Site-content exporter is missing.');
        } elseif ($space === 1) {
            $space = '';
        }
        $funcname = ($space) ? $space.'\export_content' : 'export_content';
        verbose(1, "INFO: export site content to $xmlfile");
        $funcname($xmlfile, $uploadspath, $workerspath, $db);
    }

    if (strncmp($sourceuri, 'file://', 7) == 0) {
        $fp = substr($sourceuri, 7);
        if ($fp === '' || $fp == 'local') {
            try {
                copy_local_files();
            } catch (Throwable $t) {
                die('ERROR: '.$t->GetMessage());
            }
            /* DEBUG min size
                        mkdir($sourcedir.'/lib', 0777);
                        $s1 = $installerdir.'/fake-sources/version.php';
                        $d2 = $sourcedir.'/lib/version.php';
                        copy($s1, $d2);
            */
        } elseif (!get_alternate_files()) {
            die('ERROR: sources not available');
        }
    } elseif (!get_alternate_files()) {
        die('ERROR: sources not available');
    }

    $version_php = get_version_php($sourcedir);
    if (!is_file($version_php)) {
        die('Could not find file version.php in the source tree.');
    }
    if (!defined('CMS_VERSION')) {
        include_once $version_php;
    }
    $version_num = $CMS_VERSION ?? constant('CMS_VERSION');
    if ($version_num) {
        verbose(1, "INFO: found version: $version_num");
    } else {
        verbose(0, 'ERROR: no CMSMS-version identifier is available');
    }

    $fp = joinpath($installerdir, 'lib', 'upgrade', $version_num);
    @mkdir($fp, 0777, true); // generic perms, pending actuals for installation
    $bp = $fp.DIRECTORY_SEPARATOR.'MANIFEST.DAT';
    if (!(
        is_file($bp) ||
        is_file($bp.'.gz') ||
        is_file($bp.'.bzip2') ||
        is_file($bp.'.zip')
    )) {
        verbose(0, "ERROR: no $version_num-upgrade files-manifest is present");
        // MAYBE automatically create MANIFEST.DAT using create_manifest.php, but what 'reference' fileset?
    }
    if (!is_file($fp.DIRECTORY_SEPARATOR.'changelog.txt')) {
        verbose(0, "WARNING: no $version_num-upgrade changelog is present");
        // MAYBE extract some of doc/CHANGELOG.txt ?
    }

    verbose(1, 'INFO: Recursively setting permissions');
    rchmod($sourcedir);

    if ($checksums) {
        create_checksum_dat();
    }

    if ($archive_only) {
        create_source_archive();
    } else {
//      copy_installer_files();
        // for sane memory usage, we constuct these serially
        create_extended_installer();
        create_phar_installer();
    }
    rrmdir($sourcedir); //sources can go now
    verbose(0, "Done, see files in $outdir");
} catch (Throwable $t) {
    verbose(0, 'ERROR: Problem building phar file '.$outdir.': '.$t->GetMessage());
}
