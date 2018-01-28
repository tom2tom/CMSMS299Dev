#!/usr/bin/php
<?php
/* NOTE
this uses shell commands chmod tar zip
this requires php extensions zip
this benefits from php extension Fileinfo - probably built by default
*/
$owd = getcwd();
$debug = false;

if (!$debug) {
    if (php_sapi_name() != 'cli') {
        throw new Exception('This script must be executed via the CLI');
    }
    if (!isset($argv)) {
        throw new Exception('This script must be executed via the CLI');
    }
    //  check to make sure we are in the correct directory.
    $script_file = basename($argv[0]);
    if (!file_exists(joinpath($owd, $script_file))) {
        throw new Exception('This script must be executed from the same directory as the '.$script_file.' script');
    }
}

if (ini_get('phar.readonly')) {
    throw new Exception('phar.readonly must be turned OFF in your php.ini');
}

// patterns for sources not copied to tempdir for processing,
// checked against root-dir-relative filepaths
$src_excludes = [
'~^phar_installer[\\/]~',
'~^scripts[\\/]~',
'~^tests[\\/]~',
'~[\\/]?\.git.*~',
'~[\\/]?.*\.md$~i',
'~^\.svn~',
'~svn-.*~',
'~(?<!(/doc/))\.htaccess$~',
'~config\.php$~',
'~index\.html$~',
'~\.bak$~',
'/.*~$/',
'~\.#.*~',
'~#.*~',
];

// trivial exclusions from the archive
$exclude_patterns = [
'~\.git/~',
'~\.git.*~',
'~\.svn/~',
'~^ext/~',
'~^scripts/~',
'~^build/.*~',
'/.*~$/',
'~\.\#.*~',
'~\#.*~',
'~^out/~',
'~^README.*~',
'~index\.html~',
];

$exclude_from_zip = [
'*~',
'tmp/',
'.#*',
'#*'.
'*.bak'
];

$rootdir = dirname(__DIR__); //aka phar_installer

$srcdir = $rootdir;
$tmpdir = joinpath($rootdir, 'tmp');
$datadir = joinpath($rootdir, 'data');
$outdir = joinpath($rootdir, 'out');
$priv_file = joinpath(__DIR__, 'priv.pem');
$pub_file = joinpath(__DIR__, 'pub.pem');

$archive_only = 0;
$clean = 1;
$checksums = 1;
$rename = 1;
$verbose = 0;
$zip = 1;

if (!$debug) {
    $options = getopt('ahcks:ro:vz', [
    'archive',
    'help',
    'clean',
    'checksums',
    'src',
    'rename',
    'out',
    'verbose',
    'zip'
    ]);

    if (is_array($options) && count($options)) {
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
              case 'o':
              case 'out':
                  if (!is_dir($v)) {
                      throw new Exception("$v is not a valid directory for the out parameter");
                  }
                  $outdir = $v;
                  break;
              case 's':
              case 'src':
                  if (!is_dir($v)) {
                      throw new Exception("$v is not a valid directory for the src parameter");
                  }
                  $indir = $v;
                  break;
              case 'h':
              case 'help':
                  output_usage();
                  exit;
              case 'r':
              case 'rename':
                  $rename = !$rename;
                  break;
              case 'z':
              case 'zip':
                  $zip = !$zip;
                  break;
            }
        }
    }
} //debug

function output_usage()
{
    echo <<< 'EOS'
php build_local_release.php [options]
options:
  -h / --help      show this message
  -a / --archive   only create the data archive, do not create a phar
  -c / --clean     toggle cleaning of old output directories (default is on)
  -k / --checksums toggle creation of checksum files (default is on)
  -r / --rename    toggle renaming of .phar file to .php (default is on)
  -s / --src       specify source directory <path-to>phar_installer (default is this script's parent)
  -o / --out       specify destination directory for the phar file
  -v / --verbose:  increment verbosity level (can be used multiple times)
  -z / --zip       toggle zipping the output (phar or php) into a .zip file (default is on)
EOS;
}

function current_root()
{
    global $rootdir;

    $dir = $rootdir;
    while ($dir !== '.' && !is_dir(joinpath($dir, 'admin')) && !is_dir(joinpath($dir, 'phar_installer'))) {
        $dir = dirname($dir);
    }
    return $dir;
}

function joinpath(...$segments)
{
    $path = implode(DIRECTORY_SEPARATOR, $segments);
    return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
}

function rrmdir($path, $keepdirs = false)
{
    if (is_dir($path)) {
        $res = true;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,
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
        if ($res && !$keepdirs) {
            $res = @rmdir($path);
        }
        return $res;
    }
    return false;
}

function copy_source_files()
{
    global $indir, $tmpdir, $src_excludes;

    $excludes = $src_excludes;

    verbose(1, "INFO: Copying source files from $indir to $tmpdir");
    @mkdir($tmpdir, 0771, true);

    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($indir,
             FilesystemIterator::CURRENT_AS_PATHNAME |
             FilesystemIterator::FOLLOW_SYMLINKS |
             FilesystemIterator::SKIP_DOTS
        )
    );

    $len = strlen($indir.DIRECTORY_SEPARATOR);
    foreach ($iter as $fp) {
        $relpath = substr($fp, $len);
        foreach ($excludes as $excl) {
            if (preg_match($excl, $relpath)) {
                verbose(1, "EXCLUDED: $relpath (matched pattern $excl)");
                continue 2;
            }
        }
        $tp = joinpath($tmpdir, $relpath);
        @mkdir(dirname($tp), 0771, true);
        @copy($fp, $tp);
        verbose(2, "COPIED $relpath to $tp");
    }
}

function get_version_php($startdir)
{
    $fp = joinpath($startdir, 'lib', 'version.php');
    if (is_file($fp)) {
        return $fp;
    }
    $fp = joinpath($startdir, 'version.php');
    if (is_file($fp)) {
        return $fp;
    }
}

function create_checksums($dir, $salt)
{
    global $tmpdir;

    $out = [];
    $dh = opendir($dir);
    while (($file = readdir($dh)) !== false) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $fp = joinpath($dir, $file);
        if (is_dir($fp)) {
            $tmp = create_checksums($fp, $salt);
            if (is_array($tmp) && count($tmp)) {
                $out = array_merge($out, $tmp);
            }
        } else {
            $relpath = substr($fp, strlen($tmpdir));
            $out[$relpath] = md5($salt.md5_file($fp));
        }
    }
    return $out;
}

function create_checksum_dat()
{
    global $checksums,$outdir,$tmpdir,$version_php,$version_num;

    if (!$checksums) {
        return;
    }

    $fp = joinpath($tmpdir, 'index.php');
    if (!file_exists($fp)) {
        throw new Exception('Could not find index.php file in tmpdir');
    }

    verbose(1, "INFO: Creating checksum file");
    $salt = md5_file($version_php).md5_file($fp);

    $out = create_checksums($tmpdir, $salt);
    $outfile = joinpath($outdir, "cmsms-{$version_num}-checksum.dat");

    $xfh = fopen($outfile, 'w');
    if (!$xfh) {
        echo "WARNING: problem opening $outfile for writing\n";
    } else {
        foreach ($out as $key => $val) {
            fprintf($xfh, "%s --::-- %s\n", $val, $key);
        }
        fclose($xfh);
    }
}

function create_source_archive()
{
    global $clean,$tmpdir,$owd,$datadir,$indir,$version_php;

    if ($clean && is_dir($tmpdir)) {
        verbose(1, "INFO: removing old temporary files");
        rrmdir($tmpdir);
    }

    copy_source_files();

    // change permissions
    verbose(1, "INFO: Recursively change to more-restrictive permissions");
    $cmd = "chmod -R g-w,o-w {$tmpdir}";
    verbose(2, "USING: shell command $cmd");
    $junk = null;
    $cmd = escapeshellcmd($cmd);
    exec($cmd, $junk);

    create_checksum_dat();

    verbose(1, "INFO: Creating tar archive");
    $fp = joinpath($datadir, 'data.tar.gz');
    @unlink($fp);
    $cmd = "tar -zcf $fp *";
    verbose(2, "USING: shell command $cmd");
    chdir($tmpdir); //appropriate relative-paths in archive
    system($cmd);
    chdir($owd);

    @copy($version_php, joinpath($datadir, 'version.php'));
    rrmdir($tmpdir);
}

function verbose($lvl, $msg)
{
    global $verbose;

    if ($verbose >= $lvl) {
        echo $msg."\n";
    }
}

// this is the main function.
try {
    if (!is_dir($srcdir) || !is_file(joinpath($srcdir, 'index.php'))) {
        throw new Exception('Problem finding source files in '.$srcdir);
    }

    if ($clean && is_dir($outdir)) {
        verbose(1, "INFO: Removing old output file(s)");
        rrmdir($outdir);
    }

    @mkdir($outdir, 0771, true);
    @mkdir($datadir, 0771, true);
    if (!is_dir($datadir) || !is_dir($outdir)) {
        throw new Exception('Problem creating working directories: '.$datadir.' and '.$outdir);
    }

	$indir = current_root();

    $version_php = get_version_php($indir);
    if (!file_exists($version_php)) {
        throw new Exception('Could not find file version.php in the source tree.');
    }

    include_once $version_php;
    $version_num = $CMS_VERSION;
    verbose(1, "INFO: found version: $version_num");

    create_source_archive();

    if (!$archive_only) {
        $basename = 'cmsms-'.$version_num.'-install';
        $destname = $basename.'.phar';
        $destname2 = $basename.'.php';

        verbose(1, "INFO: Writing build.ini");
        $fp = joinpath($srcdir, 'app', 'build.ini');
        $fh = fopen($fp, 'w');
        fwrite($fh, "[build]\n");
        fwrite($fh, 'build_time = '.time()."\n");
        fwrite($fh, 'build_user = '.get_current_user()."\n");
        fwrite($fh, 'build_host = '.gethostname()."\n");
        fclose($fh);

		if (function_exists('finfo_open')) {
	        $finfo = finfo_open(FILEINFO_MIME_TYPE);
		} else {
			$finfo = null;
		}
        $len = strlen($srcdir.DIRECTORY_SEPARATOR);

        // new phar file
        verbose(1, "INFO: Creating phar file");
        $phar = new Phar(joinpath($outdir, $destname));
        $phar->startBuffering();

        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($srcdir,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::FOLLOW_SYMLINKS |
                FilesystemIterator::SKIP_DOTS
            )
        );
        foreach ($iter as $fp) {
            // exclusions
            $matches = null;
            foreach ($exclude_patterns as $pattern) {
                if (preg_match($pattern, $fp, $matches, 0, $len)) {
                    continue 2;
                }
            }

            $relname = substr($fp, $len);
            verbose(1, "ADDING: $relname to the archive");

            $extension = substr($relname, strrpos($relname, '.') + 1);
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
                default:
                    if ($finfo) {
                        $mimetype = finfo_file($fp);
                    } else {
                        $mimetype = 'text/plain';
                    }
                    break;
            }

            if ($mimetype == 'unknown' || $mimetype == 0) {
                $mimetype = 'text/plain';
            }

            $phar[$relname] = file_get_contents($fp);
            $phar[$relname]->setMetaData(['mime-type'=>$mimetype]);
        }

        $phar->setMetaData(['bootstrap'=>'index.php']);
        $stub = $phar->createDefaultStub('cli.php', 'index.php');
        $phar->setStub($stub);
        $phar->setSignatureAlgorithm(Phar::SHA1);
        $phar->stopBuffering();
        unset($phar);

        if ($finfo) {
            finfo_close($finfo);
        }

		$fp = joinpath($outdir, $destname);
        // rename it to a php file so it's executable on pretty much all hosts
        if ($rename) {
            verbose(1, "INFO: Renaming phar file to php");
			$tp = joinpath($outdir, $destname2);
            rename($fp, $tp);
        }

        if ($zip) {
            $infile = ($rename) ? $tp : $fp;
            $outfile = joinpath($outdir, $basename.'.zip');

            verbose(1, "INFO: compressing phar file into $outfile");
            $arch = new ZipArchive;
            $arch->open($outfile, ZipArchive::OVERWRITE | ZipArchive::CREATE);
            $arch->addFile($infile, basename($infile));
            $arch->setExternalAttributesName(basename($infile), ZipArchive::OPSYS_UNIX, 0644 << 16);
            $arch->addFile("$rootdir/README-PHAR.TXT", "$rootdir/README-PHAR.TXT");
            $arch->setExternalAttributesName('README-PHAR.TXT', ZipArchive::OPSYS_UNIX, 0644 << 16);
            $arch->close();
            @unlink($infile);

            // zip up the install dir itself. (uses shell)
//            $tmpfile = joinpath(sys_get_temp_dir(), 'zip_excludes.dat');
            $tmpfile = joinpath(sys_get_temp_dir(), 'zip_excludes.dat');
            $str = implode("\n", $exclude_from_zip);
            file_put_contents($tmpfile, $str);
            chdir($rootdir);
            $outfile = joinpath($outdir, $basename.'expanded.zip');
            verbose(1, "INFO: zipping install directory into $outfile");
            $cmd = "zip -q -r -x@{$tmpfile} $outfile README.TXT index.php app lib data";
            verbose(2, "USING: shell command $cmd");
            $cmd = escapeshellcmd($cmd);
            system($cmd);
            @unlink($tmpfile);
        } // zip
    } // archive only

    echo "INFO: Done, see files in $outdir\n";
} catch (Exception $e) {
    echo "ERROR: Problem building phar file ".$outdir.": ".$e->GetMessage()."\n";
}
