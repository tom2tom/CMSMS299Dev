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
const SVNROOT = 'http://svn.cmsmadesimple.org/svn/cmsmadesimple';

$_scriptname = basename(__FILE__);
$_cli = php_sapi_name() == 'cli';
// default config params
$do_md5 = false;
$mode = 'f';
$outfile = 'MANIFEST.DAT.gz';
$svn_root = SVNROOT;
$uri_from = 'svn://';
$uri_to = 'file://';
// other params
$_debug = false;
$_compress = true;
$_interactive = false; //$_cli && (DIRECTORY_SEPARATOR !== '/');  //always false on windows
$_tmpdir = sys_get_temp_dir().DIRECTORY_SEPARATOR.$_scriptname.'.'.getmypid();
$_tmpfile = $_tmpdir.DIRECTORY_SEPARATOR.'tmp.out';
$_configname = str_replace('.php', '.ini', $_scriptname);
$_configfile = get_config_file();
$_writecfg = true;
//$_outfile = ($_cli) ? STDOUT : 'MANIFEST.DAT.gz';
$_outfile = 'MANIFEST.DAT.gz';
$_notdeleted = [];

$src_excludes = [
'/git/',
'/\.md$/i',
'/svn/',
'/index\.html?$/',
'/config\.php$/',
'/phar_installer/',
'/scripts/',
'/svn-.*/',
'/tests/',
'/UNUSED/',
'/HIDE/',
'/uploads/',
'/~$/',
'/\.bak$/',
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
    $opts = getopt('ic:de:f:hkm:npo:r::t::', [
    'ask', //-i : interactive
    'config',
    'debug',
    'dnd',  //-e : ??
    'from',
    'help',
    'md5',  //-k : checksum
    'mode',
    'nocompress',
    'nowrite', //-p : preserve current config file
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

// attempt to read config file, if the user wants
if ($_configfile && $_configfile != '-') {
    if (!is_readable($_configfile)) {
        fatal("No valid config file at: $_configfile");
    }
    $_config = parse_ini_file($_configfile, false, INI_SCANNER_TYPED);
    if ($_config === false) {
        fatal("Problem processing config file: $_configfile");
    }
    extract($_config);
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

            case 'f':
            case 'from':
                $uri_from = trim($val);
                break;

            case 'h':
            case 'help':
                usage();
                exit;

            case 'i':
            case 'ask':
                if (DIRECTORY_SEPARATOR !== '/') {
                    $_interactive = true;
                } else {
                    fatal('Prompted input of parameters is not supported on Windows');
                }
                break;

            case 'k':
            case 'md5':
                $do_md5 = true;
                break;

            case 'm':
            case 'mode':
                $val = trim($val);
                $val = strtolower($val[0]);
                switch ($val) {
                    case 'f':
                    case 'd':
                    case 'c':
                    case 'a':
                        $mode = $val;
                }
                break;

            case 'n':
            case 'nocompress':
                $_compress = false;
                break;

            case 'o':
            case 'outfile':
                $val = trim($val);
                $outfile = $val;
                break;

            case 'p':
            case 'nowrite':
                $_writecfg = false;
                break;

            case 'r':
            case 'root':
                $svn_root = trim($val);
                break;

            case 't':
            case 'to':
                $uri_to = trim($val);
                break;
        }
    }
}

if (!$_interactive && DIRECTORY_SEPARATOR !== '/' &&
    !($uri_from && $uri_to &&
    ($svn_root || !(startswith($uri_from, 'svn://') || startswith($uri_to, 'svn://'))) &&
    $outfile && $mode)) {
    $_interactive = true;
}

// interactive mode
if ($_cli && $_interactive) {
    if (!function_exists('readline')) {
        fatal('Abort '.$_scriptname.' : PHP readline extension is missing');
    }
    if (!extension_loaded('pcntl')) {
        info($_scriptname.' works better with pcntl extension');
    }
    if (function_exists('pcntl_signal')) {
        @pcntl_signal(SIGTERM, 'sighandler');
        @pcntl_signal(SIGINT, 'sighandler');
    }

    $uri_from = ask_string("Enter 'comparison' fileset uri", $uri_from);
    $uri_to = ask_string("Enter 'release' fileset uri", $uri_to);
    if (startswith($uri_from, 'svn://') || startswith($uri_to, 'svn://')) {
        $svn_root = ask_string('Enter svn repository root url', $svn_root);
    }
    $outfile = ask_string('Enter manifest file name', $outfile);
    $mode = ask_options('Enter manifest mode (d|n|c|f)', ['d','n','c','f'], $mode);
}

if ($_compress) {
    if (!extension_loaded('zlib')) {
        fatal('Abort '.$_scriptname.' : PHP zlib extension is missing');
    }
    // some debian-based distros don't have gzopen (crappy)
    if (!function_exists('gzopen') && function_exists('gzopen64')) {
        function gzopen($filename, $mode, $use_include_path = 0)
        {
            return gzopen64($filename, $mode, $use_include_path);
        }
    }

    if (!endswith($outfile, '.gz')) {
        $outfile = $outfile . '.gz';
    }
} elseif (endswith($outfile, '.gz') || endswith($outfile, '.GZ')) {
    $outfile = substr($outfile, 0, -3);
}

// validate the config
if (empty($uri_from)) {
    fatal("No 'reference' file-set source provided");
}
if (!preg_match('~(file|svn|git)://~', $uri_from)) {
    fatal("Unrecognised 'reference' file-set source. Specify file://... or git://... or svn://...");
}
if (empty($uri_to)) {
    fatal("No 'release' file-set source provided");
}
if (!preg_match('~(file|svn|git)://~', $uri_to)) {
    fatal("Unrecognised 'release' file-set source. Specify file://... or git://... or svn://...");
}
if ($uri_from == $uri_to) {
    fatal('Must process two different file-sets. ' .$uri_from. ' was specified for both');
}
if (startswith($uri_from, 'svn://') || startswith($uri_to, 'svn://')) {
    if (empty($svn_root)) {
        fatal('No repository root found');
    }
    if (!endswith($svn_root, '/')) {
        $svn_root .= '/';
    }
}
if (startswith($uri_from, 'file://')) {
    $file = substr($uri_from, 7);
    if ($file === '' || $file == 'local') {
        $uri_from = 'file://local';
    } elseif (!is_dir($file) || !is_readable($file)) {
        fatal('Specified file-set source ' .$file. ' is not accessable');
    }
}
if (startswith($uri_to, 'file://')) {
    $file = substr($uri_to, 7);
    if ($file === '' || $file == 'local') {
        $uri_to = 'file://local';
    } elseif (!is_dir($file) || !is_readable($file)) {
        fatal('Specified file-set source ' .$file. ' is not accessable');
    }
}

//
// begin the work
//

// create empty temp directories to hold the filesets
if (!(is_writable($_tmpdir) || mkdir($_tmpdir, 0771))) {
    fatal('Temp folder is not writable');
}
$_fromdir = $_tmpdir.DIRECTORY_SEPARATOR.'_from';
if (is_dir($_fromdir)) {
    rrmdir($_fromdir);
}
mkdir($_fromdir, 0771);
$_todir = $_tmpdir.DIRECTORY_SEPARATOR.'_to';
if (is_dir($_todir)) {
    rrmdir($_todir);
}
mkdir($_todir, 0771);

// retrieve sources
try {
    $res = get_sources($uri_from, $_fromdir);
} catch (Throwable $t) {
    info($t->GetMessage());
    $res = false;
}
if (!$res) {
    fatal('Retrieving files from ' .$uri_from. ' failed');
}
if (!is_file(joinpath($_fromdir, 'lib', 'version.php')) || !is_dir(joinpath($_fromdir, 'lib', 'classes', 'Database'))) {
    fatal('The files retrieved from ' .$uri_from. 'do not appear to be for a CMSMS installation');
}

try {
    $res = get_sources($uri_to, $_todir);
} catch (Throwable $t) {
    info($t->GetMessage());
    $res = false;
}
if (!$res) {
    fatal('Retrieving files from ' .$uri_to. ' failed');
}
if (!is_file(joinpath($_todir, 'lib', 'version.php')) || !is_dir(joinpath($_todir, 'lib', 'classes', 'Database'))) {
    fatal('The files retrieved from ' .$uri_to. 'do not appear to be for a CMSMS installation');
}

try {
    $obj = new compare_dirs($_fromdir, $_todir, $do_md5);
} catch (Throwable $t) {
    fatal($t->GetMessage());
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

if ($mode == 'd' || $mode == 'f') {
    $out = $obj->get_deleted_files();
    foreach ($out as $fn) {
        $file = $_fromdir.DIRECTORY_SEPARATOR.$fn;
        $md5 = md5_file($file);
        $str = "DELETED :: $md5 :: $fn";
        output($str);
    }
}

if ($mode == 'c' || $mode == 'f') {
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

if ($mode == 'n' || $mode == 'f') {
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
            $file = joinpath($dir, 'lib', 'upgrade', $_to_ver);
            if (is_dir($file)) {
                if (!is_file($file.DIRECTORY_SEPARATOR.'changelog.txt')) {
                    touch($file.DIRECTORY_SEPARATOR.'changelog.txt');
                }
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
        write_config_file([
        'do_md5'=>$do_md5,
        'mode'=>$mode,
        'outfile'=>$outfile,
        'svn_root'=>$svn_root,
        'uri_from'=>$uri_from,
        'uri_to'=>$uri_to,
        ], $file);
    } else {
        info('Cannot save config file '.$_configname);
    }
}

//cleanup();
info('DONE');
exit(0);

/////////////////////////
// CLASS AND FUNCTIONS //
/////////////////////////

function usage()
{
    global $_scriptname;
    echo <<<'EOT'
This is script generates a manifest of differences (additions/changes/deletions) between two sets of CMSMS files, to facilitate cleaning up and verification during a CMSMS upgrade.

EOT;
    echo <<<EOT
Usage: php $_scriptname [options]
options
  -c|--config <string> = config file name (or just '-' to skip reading a saved config file)
  -d|--debug           = enable debug mode
  -e|--dnd <string>    = a comma-separated series of filepaths (relative to the CMSMS root).
                         Files in those places will not be marked as deleted. This can be
                         useful if files will be moved manually during the upgrade process
  -f|--from <string>   = a fileset-source identifier, one of local or file://... or svn://... or git://...
  -h|--help            = display this message then exit
  -i|--ask             = interactive input of some parameters (N/A on Windows)
  -k|--md5             = enable file comparison using md5 hashes
  -m|--mode (d|n|c|f)  = generate a deleted/new/changed/full manifest
  -n|--nocompress      = do not gzip-compress the manifest file
  -o|--outfile <string> = a non-default manifest file (the default is STDOUT or MANIFEST.DAT)
  -p|--nowrite         = do not save a config file containing the parameters used in this script
  -r|--root <string>   = a non-default root url for svn-sourced fileset(s)
  -t|--to <string>     = the 'release' fileset-source identifier, same format as for -f option
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
        echo ("<br/>INFO: $str");
    }
}

function debug(string $str)
{
    global $_debug;
    if ($_debug) {
        if (defined('STDERR')) {
            fwrite(STDERR, "DEBUG: $str\n");
        } else {
            echo("<br/>DEBUG: $str");
        }
    }
}

function fatal(string $str)
{
    if (defined('STDERR')) {
        fwrite(STDERR, "FATAL: $str\n");
    } else {
        echo("<br/>FATAL: $str");
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
        info('ERROR: Invalid input. Please try again');
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
        info('ERROR: Invalid input. Please enter one of the valid options');
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
        new RecursiveDirectoryIterator($srcdir,
            FilesystemIterator::KEY_AS_FILENAME |
            FilesystemIterator::CURRENT_AS_PATHNAME |
            FilesystemIterator::UNIX_PATHS |
            FilesystemIterator::FOLLOW_SYMLINKS
        ),
        RecursiveIteratorIterator::SELF_FIRST);

    $len = strlen($srcdir.DIRECTORY_SEPARATOR);
    $matches = null;

    foreach ($iter as $fn => $fp) {
        foreach ($src_excludes as $excl) {
            if (preg_match($excl, $fp, $matches, 0, $len)) {
                $relpath = substr($fp, $len);
//              info("$relpath (matched pattern $excl)");
                continue 2;
            }
        }

        $relpath = substr($fp, $len);
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
        // cannot just include the file, constants in there cannot be re-defined ...
        $txt = file_get_contents($file);
        if ($txt) {
			$txt = str_replace(['<?php','>?'], ['',''], $txt);
			while (($p = strpos($txt, 'define')) !== false) {
				$txt[$p] = '/';
				$txt[$p+1] = '/';
			}
			while (($p = strpos($txt, 'const')) !== false) {
				$txt[$p] = '/';
				$txt[$p+1] = '/';
			}
			eval($txt);
            return [$CMS_VERSION, $CMS_VERSION_NAME];
        }
    }
    return ['',''];
}

function get_sources(string $sourceuri, string $tmpdir) : bool
{
    if (strncmp($sourceuri, 'file://', 7) == 0) {
        $dir = substr($sourceuri, 7);
        if ($dir == 'local' || $dir === '') {
            //get local root
            $dir = __DIR__;
            while ($dir !== '.' && !is_dir(joinpath($dir, 'admin')) && !is_dir(joinpath($dir, 'phar_installer'))) {
                $dir = dirname($dir);
            }
            if ($dir !== '.') {
                rcopy($dir, $tmpdir);
                return true;
            }
        } elseif (is_dir($dir)) {
            rcopy($dir, $tmpdir);
            return true;
        }
    } elseif (strncmp($sourceuri, 'svn://', 6) == 0) {
        $remnant = substr($sourceuri, 6);
        $url = 'http://svn.cmsmadesimple.org/svn/cmsmadesimple';
        switch (strtolower(substr($remnant, 0, 4))) {
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
                break;
            case 'svn.':
                $url = 'http://'.$remnant;
                break;
            default:
                return false;
        }

        $cmd = escapeshellcmd("svn export -q --force $url $tmpdir");

        info("Retrieve files from SVN ($url)");
        system($cmd, $retval);
        return ($retval == 0);
    } elseif (strncmp($sourceuri, 'git://', 6) == 0) {
        $url = 'https://'.substr($sourceuri, 6);
        $cmd = escapeshellcmd("git clone -q --bare $url $tmpdir");

        info("Retrieve files from GIT ($url)");
        system($cmd, $retval);
        return ($retval == 0);
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
