#!/usr/bin/env php
<?php
/*
NOTE interactive mode uses PHP extensions & methods which are *NIX-only
 i.e. interactive mode is not for Windoze.

Requires:
 PHP extension zlib. Also readline if in interactive mode
Prefers:
 PHP extension pcntl if in interactive mode
*/

$_scriptname = basename(__FILE__);
$_cli = php_sapi_name() == 'cli';
$_interactive = $_cli && (DIRECTORY_SEPARATOR !== '/');  //always false on windows
$_debug = false;
$_compress = true;
$_svnroot = 'http://svn.cmsmadesimple.org/svn/cmsmadesimple';
$_config = [
'do_md5'=>false,
'mode'=>'f',
'outfile'=>'MANIFEST.DAT',
'svn_root'=>$_svnroot,
'uri_from'=>'',
'uri_to'=>'',
];
$_tmpdir = sys_get_temp_dir().DIRECTORY_SEPARATOR.$_scriptname.'.'.getmypid();
$_tmpfile = $_tmpdir.DIRECTORY_SEPARATOR.'tmp.out';
$_configname = str_replace('.php', '.ini', $_scriptname);
$_configfile = get_config_file();
$_writecfg = true;
$_outfile = ($_cli) ? STDOUT : 'MANIFEST.DAT';
$_notdeleted = [];

$src_excludes = [
'/phar_installer\//',
'/scripts\//',
'/tests\//',
'/\.git.*/',
'/\.md$/i',
'/\.svn/',
'/svn-.*/',
'/\/config\.php$/',
'/\/index\.html$/',
'/\.bak$/',
'/UNUSED/',
'/~$/',
'/#.*/',
'/\.#.*/',
];
//TODO root-dir  '/\.htaccess$/',

$compare_excludes = [
'.git*','*.md','*.MD',
'.svn','svn-*',
'*.bak','*~',
'*.sh','*.pl','*.bat',
'.#*','#*',
'config.php',
'index.html',
'UNUSED*',
'tmp',
'scripts',
'tests',
'install',
'phar_installer',
];

if ($_cli) {
    $opts = getopt('c:dp:f:hsm:neo:r:t::', [
    'config',
    'debug',
    'dnd',  //-p : preserve
    'from',
    'help',
    'md5',  //-s : sum
    'mode',
    'nocompress',
    'nowrite', //-e : keep current config file
    'outfile',
    'root',
    'to',
    ]);
    // parse config-file argument
    $val = $opts['c'] ?? $opts['config'] ?? null;
    if ($val !== null) {
        $_configfile = $val;
    }
}

// attempt to read config file
if ($_configfile && $_configfile != '-') {
    if (!is_readable($_configfile)) {
        fatal("No valid config file at: $_configfile");
    }
    $_config = parse_ini_file($_configfile);
    if ($_config === false) {
        fatal("Problem processing config file: $_configfile");
    }
    info('Read config file from '.$_configfile);
}

if ($_cli) {
    // parse other command arguments
    foreach ($opts as $key => $val) {
        switch ($key) {
            case 'd':
            case 'debug':
                $_debug = true;
                break;

            case 'e':
            case 'nowrite':
                $_writecfg = false;
                break;

            case 'f':
            case 'from':
                $_config['uri_from'] = trim($val);
                break;

            case 'h':
            case 'help':
                usage();
                exit;

            case 'm':
            case 'mode':
                $val = trim($val);
                $val = strtolower($val[0]);
                switch ($val) {
                    case 'f':
                    case 'd':
                    case 'c':
                    case 'a':
                        $_config['mode'] = $val;
                }
                break;

            case 'n':
            case 'nocompress':
                $_compress = false;
                break;

            case 'o':
            case 'outfile':
                $val = trim($val);
                $_config['outfile'] = $val;
                break;

            case 'p':
            case 'dnd':
                if ($val) {
                    $tmp = explode(',', $val);
                    foreach ($tmp as $one) {
                        $one = trim($one, ' */\\');
                        if ($one) {
                            $_notdeleted[] = DIRECTORY_SEPARATOR.$one;
                        }
                    }
                }
                break;

            case 'r':
            case 'root':
                $_config['svn_root'] = trim($val);
                break;

            case 's':
            case 'md5':
                $_config['do_md5'] = true;
                break;

            case 't':
            case 'to':
                $_config['uri_to'] = trim($val);
                break;
        }
    }
}

if ($_interactive &&
    $_config['uri_from'] &&
    $_config['uri_to'] &&
    ($_config['svn_root'] || !(startswith($_config['uri_from'], 'svn://') || startswith($_config['uri_to'], 'svn://'))) &&
    $_config['outfile'] &&
    $_config['mode']) {
    $_interactive = false;
}

// interactive mode
if ($_cli && $_interactive) {
    if (!function_exists('readline')) {
        die('Abort '.$_scriptname.' : PHP readline extension is missing');
    }
    if (!extension_loaded('pcntl')) {
        info($_scriptname.' works better with pcntl extension');
    }
    if (function_exists('pcntl_signal')) {
        @pcntl_signal(SIGTERM, 'sighandler');
        @pcntl_signal(SIGINT, 'sighandler');
    }

    $_config['uri_from'] = ask_string("Enter 'comparison' fileset uri", $_config['uri_from']);
    $_config['uri_to'] = ask_string("Enter 'release' fileset uri", $_config['uri_to']);
    if (startswith($_config['uri_from'], 'svn://') || startswith($_config['uri_to'], 'svn://')) {
        $_config['svn_root'] = ask_string('Enter svn repository root url', $_config['svn_root']);
    }
    $_config['outfile'] = ask_string('Enter manifest file name', $_config['outfile']);
    $_config['mode'] = ask_options('Enter manifest mode (d|n|c|f)', ['d','n','c','f'], $_config['mode']);
}

if ($_compress) {
    if (!extension_loaded('zlib')) {
        die('Abort '.$_scriptname.' : PHP zlib extension is missing');
    }
    //
    // some debian based distros don't have gzopen (crappy)
    //
    if (!function_exists('gzopen') && function_exists('gzopen64')) {
        function gzopen($filename, $mode, $use_include_path = 0)
        {
            return gzopen64($filename, $mode, $use_include_path);
        }
    }

    if (!endswith($_config['outfile'], '.gz')) {
        $_config['outfile'] = $_config['outfile'] . '.gz';
    }
} elseif (endswith($_config['outfile'], '.gz') || endswith($_config['outfile'], '.GZ')) {
    $_config['outfile'] = substr($_config['outfile'], 0, -3);
}
if ($_config['outfile'] != '-' && $_config['outfile']) {
    $_outfile = $_config['outfile'];
}

// validate the config
if (empty($_config['uri_from'])) {
    fatal("No 'comparison' files uri provided");
}
if (!preg_match('~((file|svn|git)://|local)~', $_config['uri_from'])) {
    fatal("'comparison' files uri unrecognised - expect local or file://... or svn://... or git://...");
}
if (empty($_config['uri_to'])) {
    fatal("No 'release' files uri provided");
}
if (!preg_match('~((file|svn|git)://|local)~', $_config['uri_to'])) {
    fatal("'release' files uri unrecognised - expect local or file://... or svn://... or git://...");
}
if ($_config['uri_from'] == $_config['uri_to']) {
    fatal('Must process two different file-sets. ' .$_config['uri_from']. ' was specified for both');
}
if (startswith($_config['uri_from'], 'svn://') || startswith($_config['uri_to'], 'svn://')) {
    if (empty($_config['svn_root'])) {
        fatal('No repository root found');
    }
    if (!endswith($_config['svn_root'], '/')) {
        $_config['svn_root'] .= '/';
    }
}
if (startswith($_config['uri_from'], 'file://')) {
    $file = substr($_config['uri_from'], 7);
    if (!is_dir($file) || !is_readable($file)) {
        fatal('Specified files source ' .$file. ' is not accessable');
    }
}
if (startswith($_config['uri_to'], 'file://')) {
    $file = substr($_config['uri_to'], 7);
    if (!is_dir($file) || !is_readable($file)) {
        fatal('Specified files source ' .$file. ' is not accessable');
    }
}

//
// begin the work
//

// create temp directories to hold the filesets
if (!(is_writable($_tmpdir) || mkdir($_tmpdir, 0771))) {
    fatal('Temp folder is not writable');
}
$_fromdir = $_tmpdir.DIRECTORY_SEPARATOR.'_from';
mkdir($_fromdir, 0771);
$_todir = $_tmpdir.DIRECTORY_SEPARATOR.'_to';
mkdir($_todir, 0771);

// retrieve sources
try {
    $res = get_sources($_config['uri_from'], $_fromdir);
} catch (Exception $e) {
	info($e->GetMessage());
	$res = false;
}
if (!$res) {
    fatal('Retrieving files from ' .$_config['uri_from']. ' failed');
}
if (!is_file(joinpath($_fromdir, 'lib', 'version.php')) || !is_dir(joinpath($_fromdir, 'lib', 'classes', 'Database'))) {
    fatal('The files retrieved from ' .$_config['uri_from']. 'do not appear to be for a CMSMS installation');
}

try {
	$res = get_sources($_config['uri_to'], $_todir);
} catch (Exception $e) {
	info($e->GetMessage());
	$res = false;
}
if (!$res) {
    fatal('Retrieving files from ' .$_config['uri_to']. ' failed');
}
if (!is_file(joinpath($_todir, 'lib', 'version.php')) || !is_dir(joinpath($_todir, 'lib', 'classes', 'Database'))) {
    fatal('The files retrieved from ' .$_config['uri_to']. 'do not appear to be for a CMSMS installation');
}

try {
    $obj = new compare_dirs($_fromdir, $_todir, $_config['do_md5']);
} catch (Exception $e) {
    fatal($e->GetMessage());
}

// get version data
list($_from_ver, $_from_name) = get_version($_fromdir);
list($_to_ver, $_to_name) = get_version($_todir);

// begin output of manifest
output('MANIFEST GENERATED: '.time());
output('MANIFEST FROM VERSION: '.$_from_ver);
output('MANIFEST FROM NAME: '.$_from_name);
output('MANIFEST TO VERSION: '.$_to_ver);
output('MANIFEST TO NAME: '.$_to_name);

$obj->ignore($compare_excludes);
if ($_notdeleted) {
    $obj->do_not_delete($_notdeleted);
    output('MANIFEST SKIPPED: '.implode(', ', $_notdeleted));
}

if ($_config['mode'] == 'd' || $_config['mode'] == 'f') {
    $out = $obj->get_deleted_files();
    foreach ($out as $fn) {
        $file = $_fromdir.DIRECTORY_SEPARATOR.$fn;
        $md5 = md5_file($file);
        $str = "DELETED :: $md5 :: $fn";
        output($str);
    }
}

if ($_config['mode'] == 'c' || $_config['mode'] == 'f') {
    $out = $obj->get_changed_files();
    foreach ($out as $fn) {
        $file = $_todir.DIRECTORY_SEPARATOR.$fn;
        if (is_dir($file)) {
            continue;
        }
        $md5 = md5_file($file);
        $str = "CHANGED :: $md5 :: $fn";
        output($str);
    }
}

if ($_config['mode'] == 'n' || $_config['mode'] == 'f') {
    $out = $obj->get_new_files();
    foreach ($out as $fn) {
        $file = $_todir.DIRECTORY_SEPARATOR.$fn;
        $md5 = md5_file($file);
        $str = "ADDED :: $md5 :: $fn";
        output($str);
    }
}

if ($_compress) {
    info('Compress manifest');
    $_gzfile = $_tmpfile.'.gz';
    $_fh = gzopen($_gzfile, 'w9');
    gzwrite($_fh, file_get_contents($_tmpfile));
    gzclose($_fh);
    copy($_gzfile, $_tmpfile);
    @unlink($_gzfile);
}

if (defined('STDOUT') && $_outfile == STDOUT) {
    readfile($_tmpfile);
} else {
    $file = '';
    if ($_to_ver) {
        $dir = __DIR__;
        while ($dir != '.' && basename($dir) != 'phar_installer') {
            $dir = dirname($dir);
        }
        if ($dir != '.') {
            $file = joinpath($dir, 'assets', 'upgrade', $_to_ver);
            if (is_dir($file)) {
                $file .= DIRECTORY_SEPARATOR.$_outfile;
            } elseif (mkdir($file, 0771, true)) {
                touch($file.DIRECTORY_SEPARATOR.'changelog.txt');
                $file .= DIRECTORY_SEPARATOR.$_outfile;
            }
        }
    }
    if (!$file) {
        $file = __DIR__.DIRECTORY_SEPARATOR.$_outfile;
    }
    info('Copy manifest to '.$file);
    copy($_tmpfile, $file);
}

if ($_writecfg) {
    if ($_configfile && is_writable($_configfile)) {
        $file = $_configfile;
    } else {
        $file = '';
        $home = getenv('HOME');
        if ($home) {
            $home = realpath($home);
            if (is_dir($home) && is_writable($home)) {
                $file = $home.DIRECTORY_SEPARATOR.$_configname;
                if ($file == $_configfile) {
                    $file = '';
                }
            }
        }
        if (!$file && is_writable(__DIR__)) {
            $file = __DIR_.DIRECTORY_SEPARATOR.$_configname;
            if ($file == $_configfile) {
                $file = '';
            }
        }
    }
    if ($file) {
        info('Write config file to '.$file);
        write_config_file($_config, $file);
    } else {
        info('Cannot save config file '.$_configname);
    }
}

cleanup();
info('DONE');
exit(0);

///////////////////////////
// CLASS AND FUNCTIONS   //
///////////////////////////

function usage()
{
    global $_scriptname;
    echo <<<'EOT'
This is script compares two sets of source-files, and generates a manifest of files which have been added/changed/deleted between the sets, to facilitate cleaning up and verification of files during the upgrade process.

Ideally this script should be executed from the assets/upgrade/<to_version> directory.

The created manifest should be placed in the assets/upgrade/<to_version> directory as MANIFEST.DAT.gz.

EOT;
    echo <<<EOT
Usage: php $_scriptname [options]
options
  -c|--config <string> = config file name (or just '-' to skip reading a saved config file)
  -d|--debug           = enable debug mode
  -f|--from <string>   = a fileset-source identifier, one of local or file://... or svn://... or git://...
  -s|--md5             = enable file comparison using md5 hashes
  -o|--outfile <string> = a non-default manifest file (the default is STDOUT or MANIFEST.DAT)
  -h|--help            = display this message then exit
  -n|--nocompress      = do not gzip-compress the manifest file
  -e|--nowrite         = do not save a config file containing the parameters used in this script
  -r|--root <string>   = a non-default root url for svn-sourced fileset(s)
  -t|--to <string>     = the other fileset-source identifier, same format as for -f option
  -m|--mode (d|n|c|f)  = generate a deleted/new/changed/full manifest
  -p|--dnd <string>    = a comma-separated series of filepaths (relative to the CMSMS root)
                         This can be useful if files will be moved manually during the upgrade process
EOT;
}

function output(string $str)
{
    global $_tmpfile;
    static $_mode = 'a';
    $fh = fopen($_tmpfile, $_mode);
    $_mode = 'a';
    if (!$fh) {
        fatal('Problem opening file ('.$_tmpfile.') for writing');
    }
    fwrite($fh, "$str\n");
    fclose($fh);
}

function info(string $str)
{
    if (defined('STDERR')) {
        fwrite(STDERR, "INFO: $str\n");
    } else {
        echo ("INFO: $str<br/>");
    }
}

function debug(string $str)
{
    global $_debug;
    if ($_debug) {
        if (defined('STDERR')) {
            fwrite(STDERR, "DEBUG: $str\n");
        } else {
            echo("DEBUG: $str<br/>");
        }
    }
}

function fatal(string $str)
{
    if (defined('STDERR')) {
        fwrite(STDERR, "FATAL: $str\n");
    } else {
        echo("FATAL: $str<br/>");
    }
    cleanup();
    exit(1);
}

function startswith(string $haystack, string $needle) : bool
{
    return (strncmp($haystack, $needle, strlen($needle)) == 0);
}

function endswith(string $haystack, string $needle) : bool
{
    $o = strlen($needle);
    if ($o > 0 && $o <= strlen($haystack)) {
        return strpos($haystack, $needle, -$o) !== false;
    }
    return false;
}

function joinpath(...$segs)
{
    if (is_array($segs[0])) {
        $segs = $segs[0];
    }
    $path = implode(DIRECTORY_SEPARATOR, $segs);
    return str_replace(DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR, $path);
}

function rrmdir(string $dir)
{
    if (is_dir($dir)) {
        $objects = scandir($dir);
        foreach ($objects as $object) {
            if ($object != '.' && $object != '..') {
                $file = $dir.DIRECTORY_SEPARATOR.$object;
                if (is_dir($file)) {
                    rrmdir($file);
                } else {
                    unlink($file);
                }
            }
        }
        reset($objects);
        rmdir($dir);
    }
}

function sighandler($signum)
{
    info('Signal received');
    cleanup();
    exit(1);
}

function cleanup($signum = null)
{
    global $_tmpdir;
    debug('Clean up');
    rrmdir($_tmpdir);
}

function ask_string(string $prompt, $dflt = null, bool $allow_empty = false)
{
    while (1) {
        if ($dflt) {
            $prompt = $prompt." [default=$dflt]: ";
        }
        if (!endswith($prompt, ': ') || !endswith($prompt, ' ')) {
            $prompt .= ': ';
        }
        $tmp = trim(readline('INPUT: '.$prompt));
        if ($tmp) {
            return $tmp;
        }

        if ($allow_empty) {
            return;
        }
        if ($dflt) {
            return $dflt;
        }
        info("ERROR: Invalid input. Please try again");
    }
}

function ask_options(string $prompt, array $options, $dflt)
{
    while (1) {
        if ($dflt) {
            $prompt = $prompt." [default=$dflt] :";
        }
        if (!endswith($prompt, ': ') || !endswith($prompt, ' ')) {
            $prompt .= ': ';
        }
        $tmp = trim(readline('INPUT: '.$prompt));

        if (!$tmp) {
            $tmp = $dflt;
        }
        if (in_array($tmp, $options)) {
            return $tmp;
        }
        info("ERROR: Invalid input. Please enter one of the valid options");
    }
}

function write_config_file(array $config_data, string $filename)
{
    @copy($filename, $filename.'.bak');
    $fh = fopen($filename, 'w');
    fwrite($fh, "[config]\n");
    foreach ($config_data as $key => $val) {
        if (!is_numeric($val)) {
            $val = '"'.$val.'"';
        }
        fwrite($fh, "$key = $val\n");
    }
    fclose($fh);
}

function get_config_file() : string
{
    global $_configname;
    // detect user's home directory
    $home = getenv('HOME');
    if ($home) {
        $home = realpath($home);
    }
    if (is_dir($home)) {
        $file = $home.DIRECTORY_SEPARATOR.$_configname;
        if (is_readable($file)) {
            return $file;
        }
    }
    $file = __DIR__.DIRECTORY_SEPARATOR.$_configname;
    if (is_readable($file)) {
        return $file;
    }
    return '';
}

function rcopy(string $srcdir, string $tmpdir)
{
    global $src_excludes;

    info("Copy source files from $srcdir to $tmpdir");
    //NOTE KEY_AS_FILENAME flag does not work as such - always get path here
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $srcdir,
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
        foreach ($src_excludes as $excl) {
            if (preg_match($excl, $fp, $matches, 0, $len)) {
                $relpath = substr($fp, $len);
//              info("$relpath (matched pattern $excl)");
                continue 2;
            }
        }

        $relpath = substr($fp, $len);
        $fn = $inf->getFilename();
        if ($fn == '.') {
            $tp = joinpath($tmpdir, $relpath);
            @mkdir(dirname($tp), 0771, true);
        } elseif ($fn !== '..') {
            $tp = joinpath($tmpdir, $relpath);
            @mkdir(dirname($tp), 0771, true);
            @copy($fp, $tp);
        }
    }
}

function get_version(string $basedir) : array
{
    $file = joinpath($basedir, 'lib', 'version.php');
    if (is_file($file)) {
		$lvl = error_reporting();
		error_reporting(0);
        require $file;
        error_reporting($lvl);
        return [$CMS_VERSION, $CMS_VERSION_NAME];
    }
    return ['',''];
}

function get_sources(string $sourceuri, string $tmpdir) : bool
{
    if ($sourceuri == 'local') {
        //get local root
        $dir = __DIR__;
        while ($dir !== '.' && !is_dir(joinpath($dir, 'admin')) && !is_dir(joinpath($dir, 'phar_installer'))) {
            $dir = dirname($dir);
        }
        if ($dir !== '.') {
            rcopy($dir, $tmpdir);
            return true;
        }
    } elseif (strncmp($sourceuri, 'file://', 7) == 0) {
        $dir = substr($sourceuri, 7);
        if (is_dir($dir)) {
            rcopy($dir, $tmpdir);
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

        info("Retrieve files from SVN ($url)");
        system($cmd, $retval);
        return true; //$retval == 0?
    } elseif (strncmp($sourceuri, 'git://', 6) == 0) {
        $url = 'https://'.substr($sourceuri, 6);
        $cmd = escapeshellcmd("git clone -q $url $tmpdir");

        info("Retrieve files from GIT ($url)");
        system($cmd, $retval);
        return true; //$retval == 0?;
    }
    return false;
}

function get_svn_branch() : string
{
    $cmd = "svn info | grep '^URL:' | egrep -o '(tags|branches)/[^/]+|trunk'";
    $out = exec($cmd);
    return $out;
}

//
// a class to compare directories
//
class compare_dirs
{
    private $_a;
    private $_b;
    private $_do_md5;
    private $_has_run = null;
    private $_base_dir;
    private $_ignored = [];
    private $_donotdelete;

    public function __construct(string $dir_a, string $dir_b, bool $do_md5 = false)
    {
        if (!is_dir($dir_a)) {
            throw new Exception('Invalid directory '.$dir_a);
        }
        if (!is_readable($dir_a)) {
            throw new Exception('Directory '.$dir_a.' is not readable');
        }
        if (!is_dir($dir_b)) {
            throw new Exception('Invalid directory '.$dir_b);
        }
        if (!is_readable($dir_b)) {
            throw new Exception('Directory '.$dir_b.' is not readable');
        }

        $this->_a = $dir_a;
        $this->_b = $dir_b;
        $this->_do_md5 = (bool)$do_md5;
    }

    public function do_not_delete($in)
    {
        if (!$in) {
            return;
        }
        if (!is_array($in)) {
            $in = [$in];
        }

        foreach ($in as $one) {
            $one = trim($one);
            if ($one) {
                $this->_donotdelete[] = $one;
            }
        }
    }

    public function ignore($in)
    {
        if (!$in) {
            return;
        }
        if (!is_array($in)) {
            $in = [$in];
        }

        foreach ($in as $one) {
            $one = trim($one);
            if ($one) {
                $this->_ignored[] = $one;
            }
        }
    }

    private function _set_base(string $dir)
    {
        $this->_base_dir = $dir;
    }

    private function _get_base()
    {
        return $this->_base_dir;
    }

    private function _is_ignored(string $filename) : bool
    {
        foreach ($this->_ignored as $pattern) {
            if ($pattern == $filename || fnmatch($pattern, $filename, FNM_CASEFOLD)) {
                return true;
            }
        }
        return false;
    }

    private function _read_dir($dir = null)
    {
        if (!$dir) {
            $dir = $this->_base_dir;
        }
        if (!$dir) {
            throw new Exception('No directory specified to _read_dir');
        }

        $out = [];
        $dh = opendir($dir);
        if (!$dh) {
            throw new Exception('Problem getting directory handle for '.$dir);
        }

        while (($file = readdir($dh)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $fn = $dir.DIRECTORY_SEPARATOR.$file;

            if ($this->_is_ignored($file)) {
                continue;
            }

            $base = substr($fn, strlen($this->_get_base()));
            if (is_dir($fn)) {
                $tmp = $this->_read_dir($fn);
                $out = array_merge($out, $tmp);
                $rec = [];
                $rec['size'] = @filesize($fn);
                $rec['mtime'] = @filemtime($fn);
                if ($this->_do_md5) {
                    $rec['md5'] = md5_file($fn);
                }
                $out[$base] = $rec;
                continue;
            }

            if (!is_readable($fn)) {
                debug("$fn is not readable");
                continue;
            }

            $rec = [];
            $rec['size'] = @filesize($fn);
            $rec['mtime'] = @filemtime($fn);
            if ($this->_do_md5) {
                $rec['md5'] = md5_file($fn);
            }
            $out[$base] = $rec;
        }

        return $out;
    }

    public function run()
    {
        if ($this->_has_run) {
            return;
        }
        $this->_has_run = true;

        $this->_set_base($this->_a);
        $this->_list_a = $this->_read_dir();
        $this->_set_base($this->_b);
        $this->_list_b = $this->_read_dir($this->_b);
    }

    public function get_new_files() : array
    {
        $this->run();

        // get all the files in b that are not in a
        $tmp_a = array_keys($this->_list_a);
        $tmp_b = array_keys($this->_list_b);
        return array_diff($tmp_b, $tmp_a);
    }

    public function get_deleted_files() : array
    {
        $this->run();

        // get all the files in b that are not in a
        $tmp_a = array_keys($this->_list_a);
        $tmp_b = array_keys($this->_list_b);
        $out = array_diff($tmp_a, $tmp_b);
        if (count($out) && count($this->_donotdelete)) {
            foreach ($out as $file) {
                $skipped = false;
                foreach ($this->_donotdelete as $nd) {
                    if (startswith($file, $nd)) {
                        // skip this file at this stage.
                        $skipped = true;
                        break;
                    }
                }
                if (!$skipped) {
                    $new_out[] = $file;
                } else {
                    debug('skipped '.$file.', it is in the notdeleted list');
                }
            }
            $out = $new_out;
        }
        return $out;
    }

    public function get_changed_files() : array
    {
        $this->run();

        $out = [];
        foreach ($this->_list_a as $path => $rec_a) {
            if (!isset($this->_list_b[$path])) {
                continue;
            } // deleted/moved in b.
            $rec_b = $this->_list_b[$path];
            if ($rec_a['size'] != $rec_b['size'] || $rec_a['mtime'] != $rec_b['mtime'] ||
            (isset($rec_a['md5']) && isset($rec_b['md5']) && $rec_a['md5'] != $rec_b['md5'])) {
                $out[] = $path;
            }
        }
        return $out;
    }
} // class
