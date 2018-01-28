#!/usr/bin/php
<?php
/* NOTE
this requires php extensions zlib, zip
this benefits from php extension Fileinfo - probably built by default
*/

// setup
$owd = getcwd();
$cli = php_sapi_name() == 'cli';
if ($cli) {
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
// checked against root-dir-relative filepaths, after converting windoze sep's to unix
$src_excludes = [
'/phar_installer\//',
'/scripts\//',
'/tests\//',
'/\.git.*/',
'/\.md$/i',
'/\.svn/',
'/svn-.*/',
'/config\.php$/',
'/index\.html$/',
'/\.bak$/',
'/~$/',
'/#.*/',
'/\.#.*/',
];
//TODO root-dir  '/\.htaccess$/',

$phar_excludes = [
'/\.git.*/',
'/\.svn\//',
'/ext\//',
'/scripts\//',
'/build\//',
'/\.bak$/',
'/~$/',
'/\.#/',
'/#/',
];
//'/README.*/',

//$exclude_from_zip = [
//'*/',
//'tmp/',
//'.#*',
//'#*'.
//'*.bak'
//];


$tmpdir = joinpath(sys_get_temp_dir(), 'cmsms-install'); //out of the source tree, to prevent recursion
if ((($tmp = is_dir($tmpdir)) && !is_writable($tmpdir))
 ||
   (!$tmp && !@mkdir($tmpdir, 0771, true))) {
    throw new Exception('system temp directory must be writable by the current process');
}

$phardir = dirname(__DIR__); //parent, a.k.a. phar_installer
$srcdir = current_root($phardir); //ancestor of this script's place

$datadir = joinpath($tmpdir, 'data');
$outdir = joinpath($tmpdir, 'out');

$archive_only = 0;
$clean = 1;
$checksums = 1;
$rename = 1;
$verbose = 0;
$zip = 1;

if ($cli) {
    $options = getopt('ahcks:rvz', [
    'archive',
    'help',
    'clean',
    'checksums',
    'src',
    'rename',
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
              case 's':
              case 'src':
                  if (!is_dir($v)) {
                      throw new Exception("$v is not a valid directory for the src parameter");
                  }
                  $srcdir = $v;
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
} //cli

function current_root($dir)
{
    while ($dir !== '.' && !is_dir(joinpath($dir, 'admin')) && !is_dir(joinpath($dir, 'phar_installer'))) {
        $dir = dirname($dir);
    }
    return $dir;
}

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
  -v / --verbose:  increment verbosity level (can be used multiple times)
  -z / --zip       toggle zipping the output (phar or php) into a .zip file (default is on)
EOS;
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

function rchmod($path)
{
    $res = true;
    if (is_dir($path)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS
            ), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iter as $fp) {
            if (!is_link($fp)) {
                $mode = (is_dir($fp)) ? 0755 : 0644;
                if (!@chmod($fp, $mode)) {
                    $res = false;
                }
            }
        }
        if (!is_link($path)) {
            $mode = (is_dir($path)) ? 0755 : 0644;
            return @chmod($path, $mode) && $res;
        }
    } elseif (!is_link($path)) {
        return @chmod($path, 0644);
    }
    return $res;
}

function copy_source_files()
{
    global $srcdir, $tmpdir, $src_excludes;

    $excludes = $src_excludes;

    verbose(1, "INFO: Copying source files from $srcdir to $tmpdir");
    //NOTE KEY_AS_FILENAME flag does not work as such - always get path here
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($srcdir,
            RecursiveIteratorIterator::SELF_FIRST |
            FilesystemIterator::KEY_AS_PATHNAME |
            FilesystemIterator::CURRENT_AS_FILEINFO |
            FilesystemIterator::UNIX_PATHS |
            FilesystemIterator::FOLLOW_SYMLINKS
        )
    );

    $len = strlen($srcdir.DIRECTORY_SEPARATOR);
    $matches = null;

    foreach ($iter as $fp => $inf) {
        foreach ($excludes as $excl) {
            if (preg_match($excl, $fp, $matches, 0, $len)) {
                $relpath = substr($fp, $len);
                verbose(2, "EXCLUDED: $relpath (matched pattern $excl)");
                continue 2;
            }
        }

        $relpath = substr($fp, $len);
        $fn = $inf->getFilename();
        if ($fn == '.') {
            $tp = joinpath($tmpdir, $relpath);
            @mkdir(dirname($tp), 0771, true);
        }  elseif ($fn !== '..') {
            $tp = joinpath($tmpdir, $relpath);
            @mkdir(dirname($tp), 0771, true);
            @copy($fp, $tp);
            verbose(2, "COPIED $relpath to $tp");
        }
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

// recursive method
function create_checksums($dir, $salt)
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
            if (is_array($tmp) && $tmp) {
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
    global $checksums,$outdir,$tmpdir,$version_php,$version_num;

    if (!$checksums) {
        return;
    }

    verbose(1, "INFO: Creating checksum file");
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
    global $clean,$tmpdir,$owd,$datadir,$srcdir,$version_php;

    if ($clean && is_dir($tmpdir)) {
        verbose(1, "INFO: removing old temporary files");
        rrmdir($tmpdir);
    }
    @mkdir($tmpdir, 0771, true);

    $fp = joinpath($srcdir, 'tmp');
    rrmdir($fp, true);

    copy_source_files();
    verbose(1, "INFO: Recursively setting permissions");
    rchmod($tmpdir);

    verbose(1, "INFO: Creating tar.gz sources archive");
    @mkdir($datadir, 0771, true);
    $fp = joinpath($datadir, 'data.tar');
    @unlink($fp.'.gz');

    try {
        $phar = new PharData($fp);
        //get all files
        $phar->buildFromDirectory($tmpdir);
        //get all empty dirs
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tmpdir,
                RecursiveIteratorIterator::SELF_FIRST |
                FilesystemIterator::KEY_AS_PATHNAME |
                FilesystemIterator::CURRENT_AS_FILEINFO |
                FilesystemIterator::FOLLOW_SYMLINKS
            )
        );
        $len = strlen($tmpdir.DIRECTORY_SEPARATOR);
        foreach ($iter as $tp => $inf) {
            $fn = $inf->getFilename();
            if ($fn == '.') {
                $dir = dirname($tp);
                $iter2 = new FilesystemIterator($dir);
                if (!$iter2->valid()) {
                    $phar->addEmptyDir(substr($dir, $len));
                }
                unset($iter2);
            }
        }

        $phar->compress(Phar::GZ);
        unset($phar); //close it
        unlink($fp);
    } catch (Exception $e) {
        $cmd = "tar -zcf {$fp}.gz *";
        verbose(2, "USING: shell command $cmd");
        chdir($tmpdir); //get appropriate relative-paths in archive
        system($cmd);
        chdir($owd);
    }
}

function verbose($lvl, $msg)
{
    global $verbose;

    if ($verbose >= $lvl) {
        echo $msg."\n";
    }
}

// our "main function"
try {
    if (!is_dir($phardir) || !is_file(joinpath($phardir, 'index.php'))) {
        throw new Exception('Problem finding source files in '.$phardir);
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

    $version_php = get_version_php($srcdir);
    if (!file_exists($version_php)) {
        throw new Exception('Could not find file version.php in the source tree.');
    }

    include_once $version_php;
    $version_num = $CMS_VERSION;
    verbose(1, "INFO: found version: $version_num");

    create_source_archive();

    @mkdir($outdir, 0771, true);
    create_checksum_dat();

    if (!$archive_only) {
        $basename = 'cmsms-'.$version_num.'-install';
        $destname = $basename.'.phar';
        $destname2 = $basename.'.php';

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
        $phar = new Phar($fp);
        $phar->startBuffering();

        $relpath = joinpath('app', 'build.ini');
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
            new RecursiveDirectoryIterator($phardir,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::FOLLOW_SYMLINKS |
                FilesystemIterator::SKIP_DOTS
            )
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
            $arch->setExternalAttributesName(basename($infile), ZipArchive::OPSYS_UNIX, 0755 << 16);
            $arch->addFile($phardir.DIRECTORY_SEPARATOR.'README.TXT', 'README.TXT');
            $arch->setExternalAttributesName('README.TXT', ZipArchive::OPSYS_UNIX, 0644 << 16);
            $arch->addFile($phardir.DIRECTORY_SEPARATOR.'README-PHAR.TXT', 'README-PHAR.TXT');
            $arch->setExternalAttributesName('README-PHAR.TXT', ZipArchive::OPSYS_UNIX, 0644 << 16);
            $arch->close();
            @unlink($infile);
            // zip up most of the install dir contents, plus the sources archive
            $outfile = joinpath($outdir, $basename.'.expanded.zip');
            verbose(1, "INFO: zipping phar_installer and source data into $outfile");
            $arch = new ZipArchive;
            $arch->open($outfile, ZipArchive::OVERWRITE | ZipArchive::CREATE);
/*            $fp = joinpath($tmpdir, 'zip_excludes.dat');
            $str = implode("\n", $exclude_from_zip);
            file_put_contents($fp, $str);
            $arch->addFile($fp, basename($fp));
*/

			//TODO need mechanism to create app/upgrade/$version and related stuff e.g. MANIFEST
			//create_manifest.php ?
			$tp = joinpath($phardir, 'data');
			@mkdir($tp, 0755, true);
            @copy($version_php, joinpath($tp, 'version.php'));
			@copy(joinpath($datadir, 'data.tar.gz'),  joinpath($tp, 'data.tar.gz'));

//			rchmod($phardir); NO: build scripts are there

			$len = strlen($phardir.DIRECTORY_SEPARATOR);
			$iter = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($phardir,
					RecursiveIteratorIterator::LEAVES_ONLY |
					FilesystemIterator::FOLLOW_SYMLINKS |
					FilesystemIterator::SKIP_DOTS |
					FilesystemIterator::CURRENT_AS_PATHNAME
				)
			);
			foreach ($iter as $fp) {
				$relpath = substr($fp, $len);
				if (strpos($fp, 'build', $len) !== false) {
					verbose(2, "EXCLUDED: $relpath (matched pattern 'build')");
					continue;
				}
				verbose(2, "ADDING: $relpath to the zip");
	            $arch->addFile($fp, $relpath);
			}

            $arch->close();

			//eliminate files we're done with
            rrmdir($tp);
			$files = glob($tmpdir.DIRECTORY_SEPARATOR.'*', GLOB_NOSORT| GLOB_NOESCAPE);
			foreach ($files as $fp) {
				if (is_dir($fp)) {
					switch (basename($fp)) {
//DEBUG  				case 'data':
						case 'out':
							break;
						default:
							rrmdir($fp);
							break;
					}
				} else {
					@unlink($fp);
				}
			}
        } // zip
    } // archive only

    echo "INFO: Done, see files in $outdir\n";
} catch (Exception $e) {
    echo "ERROR: Problem building phar file ".$outdir.": ".$e->GetMessage()."\n";
}
