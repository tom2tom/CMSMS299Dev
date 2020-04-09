#!/usr/bin/env php
<?php

use cms_installer\installer_base;

/* NOTE
this REQUIRES php extensions phar, zlib, and usually, zip
this benefits from php extension Fileinfo - probably built by default
*/
if (!extension_loaded('phar')) {
    die('PHP\'s phar extension is required for this process');
}
if (ini_get('phar.readonly')) {
    die('phar.readonly must be turned OFF in your php.ini');
}
if (!extension_loaded('zlib')) {
    die('PHP\'s zlib extension is required for this process');
}

// setup
$cli = php_sapi_name() == 'cli';
$owd = getcwd();
$script_file = basename(__FILE__);

if ($cli) {
    // check to make sure we are in the correct directory.
    if (!is_file(joinpath($owd, $script_file))) {
        die('This script must be executed from the same directory as the '.$script_file.' script');
    }
}

// regex patterns for sources not copied to tempdir for inclusion in the sources tarball,
// checked against root-dir-relative filepaths, after converting windoze path-sep's to *NIX
$src_excludes = [
'/\.#.*/',
'/\.bak$/',
'/\.git.*/',
'/\.md$/i',
'/\.svn/',
'/\/index\.html?$/',
'/#.*/',
'/^config\.php$/', //TODO does not exclude main config file! (must keep class.cms_config.php)
'/~$/',
'/phar_installer\/|phar_installer$/',
'/scripts\/|scripts$/',
'/svn-.*/',
'/tests\/|tests$/',
'/UNUSED.*/',
'/uploads\/.+/',
];
//TODO root-dir  '/\.htaccess$/',

// regex patterns for sources in the phar_intaller folder, but not included in the created phar file or expanded-zip
// NOTE README*.TXT will need to be independently processed into the respective files
$phar_excludes = [
'/\.#/',
'/\.bak$/',
'/\.git.*/',
'/\.svn\//',
'/#/',
'/~$/',
'/build\/|build$/',
'/out\/|out$/',
'/scripts\/|scripts$/',
'/source\/|source$/',
'/UNUSED.*/'
];
//'/README.*/',

//$zip_excludes = [ same as $phar_excludes
//'*/',
//'tmp/',
//'.#*',
//'#*',
//'*.bak'
//];

$phardir = dirname(__DIR__); //parent, a.k.a. phar_installer

$tmpdir = joinpath($phardir, 'source'); //place for sources to go into data.tar.gz
$datadir = joinpath($phardir, 'data'); //place for data.tar.gz etc
$outdir = joinpath($phardir, 'out'); //place for script results/output

$archive_only = 0;
$clean = 1;
$checksums = 1;
$rename = 1;
$sourceuri = 'file://'; // file-set source
$verbose = 0;
$zip = 1;

$config_file = str_replace('.php', '.ini', $script_file);
$fp = joinpath($owd, $config_file);
if (is_readable($fp)) {
    $config = parse_ini_file($fp);
    if ($config !== false) {
        verbose(1, "INFO: read config data from $config_file");
        extract($config);
    } else {
        verbose(1, "ERROR: Problem processing config file: $config_file");
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
                $zip = !$zip;
                break;
            }
        }
    }
} //cli

function output_usage()
{
    echo <<< 'EOS'
php build_release.php [options]
options:
  -h|--help      show this message
  -a|--archive   only create the data archive, do not create a phar
  -c|--clean     toggle cleaning of old output directories (default is on)
  -k|--checksums toggle creation of checksum files (default is on)
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

function rrmdir(string $fp, bool $keepdirs = false, bool $keeptop = false) : bool
{
    if (is_dir($fp)) {
        $res = true;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $fp,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $p) {
            if (is_dir($p)) {
                if ($keepdirs || !@rmdir($p)) {
                    $res = false;
                }
            } elseif (!@unlink($p)) {
                $res = false;
            }
        }
        if ($res && !($keeptop || $keepdirs)) {
            $res = @rmdir($fp);
        }
        return $res;
    }
    return false;
}

function rchmod(string $fp) : bool
{
    $res = true;
    if (is_dir($fp)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $fp,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS
            ),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iter as $fp) {
            if (!is_link($fp)) {
                $mode = (is_dir($fp)) ? 0751 : 0644;
                if (!@chmod($fp, $mode)) {
                    $res = false;
                }
            }
        }
        if (!is_link($fp)) {
            $mode = (is_dir($fp)) ? 0751 : 0644;
            return @chmod($fp, $mode) && $res;
        }
    } elseif (!is_link($fp)) {
        return @chmod($fp, 0644);
    }
    return $res;
}

function get_alternate_files() : bool
{
    global $sourceuri, $tmpdir;

    if (strncmp($sourceuri, 'file://', 7) == 0) {
        $tmpdir = substr($sourceuri, 7);
        if (is_dir($tmpdir)) {
            //use files in that place
            return true;
        }
    } elseif (strncmp($sourceuri, 'svn://', 6) == 0) {
        $remnant = substr($sourceuri, 6);
        $url = 'http://svn.cmsmadesimple.org/svn/cmsmadesimple';
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

        $cmd = escapeshellcmd("svn export -q $url $tmpdir");

        verbose(1, "INFO: retrieving files from SVN ($url)");
        system($cmd, $retval);
        return true; //$retval == 0?
    } elseif (strncmp($sourceuri, 'git://', 6) == 0) {
        $url = 'https://'.substr($sourceuri, 6);
        $cmd = escapeshellcmd("git clone -q $url $tmpdir");

        verbose(1, "INFO: retrieving files from GIT ($url)");
        system($cmd, $retval);
        return true; //$retval == 0?;
    }
    return false;
}

function copy_local_files()
{
    global $tmpdir, $src_excludes;

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
            default:
                break;
        }
    }
    $modules = array_merge($config['coremodules'], $config['extramodules']);
    unset($config, $xconfig);
    $modcheck = DIRECTORY_SEPARATOR.'modules'.DIRECTORY_SEPARATOR;
    $mclen = strlen($modcheck);

    verbose(1, "INFO: Copying source files from $localroot to $tmpdir");

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $localroot,
            FilesystemIterator::KEY_AS_FILENAME |
            FilesystemIterator::CURRENT_AS_PATHNAME |
            FilesystemIterator::SKIP_DOTS |
            FilesystemIterator::UNIX_PATHS //|
//          FilesystemIterator::FOLLOW_SYMLINKS too bad if links not relative !!
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $len = strlen($localroot.DIRECTORY_SEPARATOR);

    foreach ($iter as $fn=>$fp) {
        // ignore unwanted filepath patterns
        foreach ($src_excludes as $excl) {
            if (preg_match($excl, $fp, $matches, 0, $len)) {
                $relpath = substr($fp, $len);
                verbose(2, "EXCLUDED: $relpath (matched pattern $excl)");
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
                if ($parent) { verbose(2, "EXCLUDED: unwanted module $modname"); }
                continue;
            }
        }

        $relpath = substr($fp, $len);
        $tp = joinpath($tmpdir, $relpath);
        if (!is_dir($fp)) {
            copy($fp, $tp);
            verbose(2, "COPIED $fn to $tp");
        } else {
            mkdir($tp, 0771, true);
        }
    }
    //clear all tmp/*/* files
    $tp = joinpath($tmpdir, 'tmp');
    rrmdir($tp, true);
    //TODO maybe clear all relevant from ...[assets]/[simple_plugins]
    //BUT no change to {...[assets]/templates/*, ...[assets]/css/*}, those files will be recorded in the relevant table

    //workaround failed exclusion
    $tp = joinpath($tmpdir, 'config.php');
    @unlink($tp);
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
    global $tmpdir;

    $len = strlen($tmpdir); //paths have leading DIRECTORY_SEPARATOR
    $out = [];
    $dh = opendir($dir);

    while (($fn = readdir($dh)) !== false) {
        if ($fn == '.' || $fn == '..') {
            continue;
        }

        $fp = joinpath($dir, $fn);
        if (is_dir($fp)) {
            $tmp = create_checksums($fp, $salt); //recurse
            if ($tmp) {
                $out = array_merge($out, $tmp);
            }
        } else {
            $relpath = substr($fp, $len);
            $out[$relpath] = md5($salt.md5_file($fp));
        }
    }
    return $out;
}

function create_checksum_dat()
{
    global $outdir, $tmpdir, $version_php, $version_num;

    verbose(1, 'INFO: Creating checksum file');
    $salt = md5_file($version_php).md5_file($tmpdir.DIRECTORY_SEPARATOR.'index.php'); //TODO joinpath

    $out = create_checksums($tmpdir, $salt);
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
    global $tmpdir, $datadir;

    @mkdir($datadir, 0771, true);
    $fp = joinpath($datadir, 'data.tar');
    @unlink($fp);
    @unlink($fp.'.gz');

    try {
        verbose(1, 'INFO: Creating sources archive data.tar.gz');
        $phar = new PharData($fp);
        //get files
        $phar->buildFromDirectory($tmpdir);
        //get empty dirs
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $tmpdir,
                FilesystemIterator::KEY_AS_FILENAME |
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::UNIX_PATHS |
                FilesystemIterator::FOLLOW_SYMLINKS
            ),
            RecursiveIteratorIterator::SELF_FIRST
        );
        $len = strlen($tmpdir.DIRECTORY_SEPARATOR);
        foreach ($iter as $tp) {
//          $fn = basename($tp);
//          if ($fn == '.') {
            if (is_dir($tp)) {
                $dir = dirname($tp);
                $iter2 = new FilesystemIterator($dir);
                if (!$iter2->valid()) {
                    $phar->addEmptyDir(substr($dir, $len));
                }
                unset($iter2);
//          } else {
//              $phar->addFile($tp, substr($tp, $len));
            }
        }

        $phar->compress(Phar::GZ);
        unset($phar); //close it
        unlink($fp);
    } catch (Throwable $t) {
        die('ERROR: sources tarball creation failed : '.$t->GetMessage()."\n");
    }
}

// compress all smarty stuff in the sources tree into a distinct tarball
// in $datadir, for use by the expanded installer when PHP phar support
// is N/A (at least)
function create_smarty_archive()
{
    global $datadir;

    @mkdir($datadir, 0771, true);
    $fp = joinpath($datadir, 'smarty.tar');
    @unlink($fp);
    @unlink($fp.'.gz');

    verbose(1, 'INFO: Creating archive smarty.tar.gz');
    $sp = joinpath(current_root(), 'lib', 'vendor', 'smarty', 'smarty'); //composer-conformant location
    try {
        $phar = new PharData($fp);
        $phar->buildFromDirectory($sp);
        $phar->compress(Phar::GZ); //TODO can a windows-based system decompress tar.gz without PHP phar extension?
        unset($phar); //close it
        unlink($fp);
    } catch (Throwable $t) {
        die('ERROR: installer-smarty tarball creation failed : '.$t->GetMessage()."\n");
    }
}

function verbose(int $lvl, string $msg)
{
    global $verbose;

    if ($verbose >= $lvl) {
        echo $msg."\n";
    }
}

// our "main function"
if (!$archive_only) {
    if (!extension_loaded('zip')) {
        die('PHP\'s zip extension is required for this process');
    }
}

try {
    if (!is_dir($phardir) || !is_file(joinpath($phardir, 'index.php'))) {
        die('Problem finding source files in '.$phardir);
    }

    if ($clean && is_dir($outdir)) {
        verbose(1, 'INFO: Removing old output file(s)');
        rrmdir($outdir, true);
    }
    if (!is_dir($outdir)) {
        @mkdir($outdir, 0771, true);
    }
    if (!is_dir($datadir)) {
        @mkdir($datadir, 0771, true);
    }
    if (!is_dir($datadir) || !is_dir($outdir)) {
        die('Problem creating working directories: '.$datadir.' and/or '.$outdir);
    }

    $fp = joinpath($phardir, 'lib', 'classes', 'class.installer_base.php');
    require_once $fp;
    $arr = installer_base::CONTENTXML;
    $xmlfile = joinpath($phardir, ...$arr);
    if (!is_file($xmlfile)) {
        $localroot = current_root();
        $fp = joinpath($localroot, 'config.php');
        if (is_file($fp)) {
            // probably an installed site
            require_once $fp;
            $CMS_JOB_TYPE = 2; //in-scope for included file
            $fp = joinpath($localroot, 'lib', 'include.php');
            require_once $fp;
            $arr = installer_base::CONTENTFILESDIR;
            $filesin = joinpath($phardir, ...$arr);
            $db = CmsApp::get_instance()->GetDb();
            require_once joinpath($phardir, 'lib', 'iosite.functions.php');
            verbose(1, "INFO: export site content to $xmlfile");
            export_content($xmlfile, $filesin, $db);
        }
    }

    if ($clean && is_dir($tmpdir)) {
        verbose(1, 'INFO: removing old temporary files');
        rrmdir($tmpdir);
    }
    @mkdir($tmpdir, 0771, true);

    if (strncmp($sourceuri, 'file://', 7) == 0) {
        $fp = substr($sourceuri, 7);
        if ($fp === '' || $fp == 'local') {
            try {
                copy_local_files();
            } catch (Throwable $t) {
                die($t->GetMessage());
            }
        } elseif (!get_alternate_files()) {
            die('ERROR: sources not available');
        }
    } elseif (!get_alternate_files()) {
        die('ERROR: sources not available');
    }

    $version_php = get_version_php($tmpdir);
    if (!is_file($version_php)) {
        die('Could not find file version.php in the source tree.');
    }

    include_once $version_php;
    $version_num = CMS_VERSION;
    verbose(1, "INFO: found version: $version_num");

    $fp = joinpath($phardir, 'lib', 'upgrade', $version_num);
    @mkdir($fp, 0771, true);
    if (!(is_file($fp.DIRECTORY_SEPARATOR.'MANIFEST.DAT.gz') || is_file($fp.DIRECTORY_SEPARATOR.'MANIFEST.DAT'))) {
        verbose(1, 'ERROR: no upgrade-files manifest is present');
        // MAYBE create MANIFEST.DAT.gz using create_manifest.php, but what 'reference' fileset?
    }
    if (!is_file($fp.DIRECTORY_SEPARATOR.'changelog.txt')) {
        verbose(1, 'WARNING: no upgrade changelog is present');
        // MAYBE extract some of doc/CHANGELOG.txt ?
    }

    verbose(1, 'INFO: Recursively setting permissions');
    rchmod($tmpdir);

    if ($checksums) {
        create_checksum_dat();
    }

    create_source_archive();

    if (!$archive_only) {
        $basename = 'cmsms-'.$version_num.'-install';
        $destname = $basename.'.phar';
        $destname2 = $basename.'.php';

        @copy($version_php, joinpath($datadir, 'version.php'));

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
        } else {
            $finfo = null;
        }

        $len = strlen($phardir.DIRECTORY_SEPARATOR);
        $matches = null;

        // new phar file
        $fp = joinpath($outdir, $destname);
        verbose(1, "INFO: Creating phar file $fp");
        $phar = new Phar($fp); // no iterator flags, we will self-manage
        $phar->startBuffering();

        $relpath = joinpath('lib', 'assets', 'installer.ini');
        $t = time();
        $u = get_current_user();
        $h = gethostname();
        $phar[$relpath] = <<<EOS
[build]
build_time = $t
build_user = $u
build_host = $h
EOS;
        $phar[$relpath]->setMetaData(['mime-type'=>'text/plain']);

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator(
                $phardir,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::FOLLOW_SYMLINKS |
                FilesystemIterator::SKIP_DOTS
            )
            // NB default RecursiveIteratorIterator::LEAVES_ONLY means no folders
        );
        foreach ($iter as $fp) {
            $relpath = substr($fp, $len);
            // skip unwanted
            foreach ($phar_excludes as $excl) {
                if (preg_match($excl, $fp, $matches, 0, $len)) {
                    verbose(2, "EXCLUDED: $relpath (matched pattern $excl)");
                    continue 2;
                }
            }
            if (strcasecmp($relpath, 'README.TXT') == 0) {
                verbose(2, "EXCLUDED: $relpath");
                continue; //zip-specific file not covered by a regex
            }

            verbose(2, "ADDING: $relpath to the phar");

            $extension = substr($relpath, strrpos($relpath, '.') + 1);
            switch (strtolower($extension)) {
                case 'inc':
                case 'php':
                case 'php4':
                case 'php5':
                case 'phps':
                    $mimetype = Phar::PHP;
                    break;
                case 'js':
                    $mimetype = 'text/javascript';
                    break;
                case 'css':
                    $mimetype = 'text/css';
                    break;
                case 'xml':
                    $mimetype = 'application/xml';
                    break;
                default:
                    if ($finfo) {
                        $mimetype = finfo_file($fp);
                        if ($mimetype == 'unknown' || $mimetype == 0) {
                            $mimetype = 'text/plain';
                        }
                    } elseif ($extension == 'png') {
                        $mimetype = 'image/png';
                    } elseif ($extension == 'gif') {
                        $mimetype = 'image/gif';
                    } elseif ($extension == 'svg') {
                        $mimetype = 'image/svg+xml';
                    } else {
                        $mimetype = 'text/plain';
                    }
                    break;
            }

            $phar[$relpath] = file_get_contents($fp);
            $phar[$relpath]->setMetaData(['mime-type'=>$mimetype]);
        }

        $phar->setMetaData(['bootstrap'=>'index.php']);
        $stub = $phar->createDefaultStub('cli.php', 'index.php');
        $phar->setStub($stub);
        $phar->setSignatureAlgorithm(Phar::SHA1);
        $phar->stopBuffering();
        unset($phar); //close it

        if ($finfo) {
            finfo_close($finfo);
        }

        $fp = joinpath($outdir, $destname);
        if ($rename) {
            // rename it to a php file so it's executable on pretty much all hosts
            verbose(1, 'INFO: Renaming phar file to php');
            $tp = joinpath($outdir, $destname2);
            rename($fp, $tp);
        }

        if ($zip) {
            $infile = ($rename) ? $tp : $fp;
            $outfile = joinpath($outdir, $basename.'.zip');

            verbose(1, "INFO: compressing phar file into $outfile");
            $arch = new ZipArchive();
            $arch->open($outfile, ZipArchive::OVERWRITE | ZipArchive::CREATE);
            $arch->addFile($infile, basename($infile));
            $arch->setExternalAttributesName(basename($infile), ZipArchive::OPSYS_UNIX, 0751 << 16);
            $arch->addFile($phardir.DIRECTORY_SEPARATOR.'README-PHAR.TXT', 'README-PHAR.TXT');
            $arch->setExternalAttributesName('README-PHAR.TXT', ZipArchive::OPSYS_UNIX, 0644 << 16);
            $arch->close();
            @unlink($infile);

            rrmdir($tmpdir); //sources can go now

            create_smarty_archive();

            // zip up most of the install dir contents, plus the sources archive
            $outfile = joinpath($outdir, $basename.'.expanded.zip');
            verbose(1, "INFO: zipping phar_installer and source data into $outfile");
            $arch = new ZipArchive();
            $arch->open($outfile, ZipArchive::OVERWRITE | ZipArchive::CREATE);
/*          $fp = joinpath($tmpdir, 'zip_excludes.dat');
            $str = implode("\n", $zip_excludes);
            file_put_contents($fp, $str);
            $arch->addFile($fp, basename($fp));
*/
//          rchmod($phardir); NO: build scripts are there

            $pharname = 'installer'.DIRECTORY_SEPARATOR;
            $len = strlen($phardir.DIRECTORY_SEPARATOR);
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $phardir,
                    FilesystemIterator::FOLLOW_SYMLINKS |
                    FilesystemIterator::SKIP_DOTS |
                    FilesystemIterator::CURRENT_AS_PATHNAME
                )
                //NB default RecursiveIteratorIterator::LEAVES_ONLY
            );
            foreach ($iter as $fp) {
                $relpath = substr($fp, $len);
                if (strncmp($relpath, 'build', 5) == 0 ||
                    strncmp($relpath, 'out', 3) == 0 ||
                    strncasecmp($relpath, 'README-PHAR', 11) == 0) { //phar-specific file not covered by a regex
                    verbose(2, "EXCLUDED: $relpath from the zip");
                } else {
                    foreach ($phar_excludes as $excl) {
                        if (preg_match($excl, $fp, $matches, 0, $len)) {
                            verbose(2, "EXCLUDED: $relpath from the zip");
                            continue 2;
                        }
                    }
                    verbose(2, "ADDING: $relpath to the zip");
                    $arch->addFile($fp, $pharname.$relpath);
                }
            }

            $arch->close();

            rrmdir($tp);
        } else { // zip
            rrmdir($tmpdir); //sources can go now
        }
    } // archive only

    rrmdir($datadir);

    echo "INFO: Done, see files in $outdir\n";
} catch (Throwable $t) {
    echo 'ERROR: Problem building phar file '.$outdir.': '.$t->GetMessage()."\n";
}
