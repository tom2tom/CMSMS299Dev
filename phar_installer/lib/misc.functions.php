<?php

namespace cms_installer {

use cms_installer\cms_smarty;
use cms_installer\installer_base;
use cms_installer\langtools;
use cms_installer\nlstools;
use Exception;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Throwable;
use const CMSSAN_ACCOUNT;
use const CMSSAN_FILE;
use const CMSSAN_HIGH;
use const CMSSAN_NAME;
use const CMSSAN_NONPRINT;
use const CMSSAN_PATH;
use const CMSSAN_PUNCT;
use const CMSSAN_PUNCTX;
use const CMSSAN_PURE;
use const CMSSAN_PURESPC;

static $_writable_error = [];

/**
 *
 * @param string $to URL
 */
function redirect(string $to)
{
    $_SERVER['PHP_SELF'] = null;
    //TODO generally support the websocket protocol 'wss' : 'ws'
    $schema = $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http';
    $host = strlen($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];

    $components = parse_url($to);
    if( count($components) > 0 ) {
        $to =  (isset($components['scheme']) && startswith($components['scheme'], 'http') ? $components['scheme'] : $schema) . '://';
        $to .= isset($components['host']) ? $components['host'] : $host;
        $to .= isset($components['port']) ? ':' . $components['port'] : '';
        if( isset($components['path']) ) {
            if( in_array(substr($components['path'],0,1),['\\','/']) ) { //Path is absolute, just append.
                $to .= $components['path'];
            }
            //Path is relative, append current directory first.
            else if( isset($_SERVER['PHP_SELF']) && !is_null($_SERVER['PHP_SELF'])) { //Apache
                $to .= (strlen(dirname($_SERVER['PHP_SELF'])) > 1 ?  dirname($_SERVER['PHP_SELF']).'/' : '/') . $components['path'];
            }
            else if( isset($_SERVER['REQUEST_URI']) && !is_null($_SERVER['REQUEST_URI'])) { //Lighttpd
                if( endswith($_SERVER['REQUEST_URI'], '/') ) {
                    $to .= (strlen($_SERVER['REQUEST_URI']) > 1 ? $_SERVER['REQUEST_URI'] : '/') . $components['path'];
                } else {
                    $to .= (strlen(dirname($_SERVER['REQUEST_URI'])) > 1 ? dirname($_SERVER['REQUEST_URI']).'/' : '/') . $components['path'];
                }
            }
        }
        else {
            $to .= $_SERVER['REQUEST_URI'];
        }
        $to .= isset($components['query']) ? '?' . $components['query'] : '';
        $to .= isset($components['fragment']) ? '#' . $components['fragment'] : '';
    }
    else {
        $to = $schema.'://'.$host.'/'.$to;
    }

    session_write_close();

    if( headers_sent() ) {
        // use javascript instead
        echo '<script type="text/javascript"><!-- location.replace("'.$to.'"); // --></script><noscript><meta http-equiv="Refresh" content="0;URL='.$to.'"></noscript>';
        exit;
    }
    else {
        header("Location: $to");
        exit;
    }
}

/**
 * @return installer_base object
 */
function get_app()
{
    return installer_base::get_instance();
}

/**
 * @return cms_smarty object, a Smarty subclass
 */
function smarty()
{
    return cms_smarty::get_instance();
}

/**
 * @return nlstools object
 */
function nls()
{
    return new nlstools();
}

/**
 * @return langtools object
 */
function translator()
{
    return langtools::get_instance();
}

function startswith(string $haystack, string $needle) : bool
{
    return (strncmp($haystack,$needle,strlen($needle)) == 0);
}

function endswith(string $haystack, string $needle) : bool
{
    $o = strlen( $needle );
    if( $o > 0 && $o <= strlen($haystack) ) {
        return strpos($haystack, $needle, -$o) !== false;
    }
    return false;
}

function joinpath(string ...$args) : string
{
    if( is_array($args[0])) {
        $args = $args[0];
    }
    $path = implode(DIRECTORY_SEPARATOR, $args);
    return str_replace(['\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR],
        [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
}

function lang(...$args)
{
    try {
        return langtools::get_instance()->translate(...$args);
    }
    catch( Throwable $t ) {
        return '';
    }
}

/**
 *
 * @param mixed $in
 * @param bool $strict Default false
 * @return mixed bool or null
 */
function to_bool($in, bool $strict = false)
{
    if( is_bool($in) ) return $in;
    $in = strtolower((string) $in);
    if( in_array($in,['1','y','yes','true','t','on']) ) return true;
    if( in_array($in,['0','n','no','false','f','off']) ) return false;
    return ( $strict ) ? null : ($in != false);
}

/**
 *
 * @param string $str
 * @return bool
 */
function is_email(string $str) : bool
{
    $str = trim('' . $str);
    return (bool)preg_match('/\S+.*@[\w.\-\x80-\xff]+$/', $str);
}

/**
 * Scrub inappropriate chars from the supplied string.
 * This is a cousin of PHP's filter_var(FILTER_SANITIZE...).
 * @see also CMSMS\execSpecialize()
 * @internal
 * @since 2.99
 *
 * @param string $str String to be cleaned
 * @param int $scope Optional enumerator
 * CMSSAN_NONPRINT
 *  remove non-printable chars < 0x80 (e.g. for a password, description, anything else minimally-restricted)
 * CMSSAN_HIGH
 *  as for CMSSAN_NONPRINT, but also remove chars >= 0x80
 * CMSSAN_PUNCT
 *  remove non-printable chars < 0x80 plus these: " ' ; = ? ^ ` < >
 *    plus repeats of non-alphanum chars
 *    (e.g. for a 'normal' html-element attribute value)
 *    Hence allowed are non-repeats of non-word-chars other than the above e.g. ~ & @ % : [ ] ( ) { } \ / space
 *    but removals include the 2nd from valid pairs e.g. \\ :: __
 *    NOTE html allows element props/attributes (e.g. name, id) to
 *    include meta-characters !"#$%&'()*+,./:;<=>?@[\]^`{|}~ but any
 *    such must sometimes be escaped e.g. in a jQuery selector
 * CMSSAN_NAME
 *  as for CMSSAN_PUNCT, but allow non-repeats of these: _ / + - , . space
 *    which are allowed by AdminUtils::is_valid_itemname()
 *    NOTE '/' >> not for possibly-file-stored items
 * CMSSAN_PUNCTX
 *  as for CMSSAN_PUNCT, but allow multiples of non-alphanum char(s) specified in $ex
 * CMSSAN_PURE (default)
 *  remove non-'word' chars < 0x80, other than these: - .
 *    (e.g. for a 'pure' 1-word name).
 * CMSSAN_PURESPC
 *  as for CMSSAN_PURE, but allow space(s) (e.g. for a clean multi-word value)
 * CMSSAN_FILE
 *  remove non-printable chars plus these: * ? \ / and substitute '_' for each space
 *    (e.g. for file names, modules, plugins, UDTs, templates, stylesheets, admin themes, frontend themes)
 * CMSSAN_PATH
 *  as for CMSSAN_FILE, but allow \ / (e.g. for file paths)
 * CMSSAN_ACCOUNT
 *  email address accepted verbatim, otherwise treated as scope 0 (in future, maybe 2 or 21 per username policy)
 *    for user/account name
 *
 *  If scope > CMSSAN_NONPRINT, the supplied string is also trim()'d
 *  Only scope CMSSAN_NONPRINT is liberal enough to suit processing a generic
 *  'description' or 'title' value. At least apply cms_installer\de_specialize() to those.
 *  Maybe cms_installer\execSpecialize() (N/A), nl2br(), striptags().
 * @param string $ex Optional extra non-alphanum char(s) for $scope CMSSAN_PUNCTX
 * @return string
 */
function sanitizeVal(string $str, int $scope = CMSSAN_PURE, string $ex = '') : string
{
    if ($scope & ~CMSSAN_NONPRINT) {
        $str = trim($str);
    }
    if ($str !== '') {
        // eliminate multi-backslashes other than valid backslash-pairs
        $l = strlen($str);
        $p = 0;
        while (($p = strpos($str, '\\', $p)) !== false) {
            if ($p + 3 < $l && $str[$p + 1] == '\\' && $str[$p + 2] == '\\') {
                switch ($str[$p + 3]) {
                    case '\\': // skip past '\\\\'
                        $p += 4;
                        break;
                    case "'": // omit '\\\' followed by ASCII char normally special-char'd
                    case '"':
                    case '<':
                    case '>':
                    case '&':
                         $str = substr($str, 0, $p) . substr($str, $p + 3);
                        $l -= 3;
                    break;
                    default: // omit '\\' followed by '\'
                        $str = substr($str, 0, $p) . substr($str, $p + 2);
                        $l -= 2;
                        break;
                }
            } elseif ($p + 1 < $l) {
                switch ($str[$p + 1]) {
                    case "'": // omit '\' followed by char normally special-char'd
                    case '"':
                    case '<':
                    case '>':
                    case '&':
                        $str = substr($str, 0, $p) . substr($str, $p + 1);
                        --$l;
                        break;
                    default:
                        ++$p;
                        break;
                }
            } elseif ($p + 1 == $l) {
                $str = substr($str, 0, $p);
                --$l;
                break;
            } else {
                ++$p;
            }
        }
    }
    switch ($scope) {
        case CMSSAN_ACCOUNT:
            if (is_email($str)) {
                return $str;
            }
        // in future, default e.g. to scope CMSSAN_PURE or CMSSAN_PURESPC, according to username policy
        //no break here
        case CMSSAN_NONPRINT:
            $patn = '/[\x00-\x1f\x7f]/';
            break;
        case CMSSAN_HIGH:
            $patn = '/[\x00-\x1f\x7f-\xff]/';
            break;
        case CMSSAN_NAME:
            $str = preg_replace('/([^a-zA-Z\d\x80-\xff])\1+/', '$1', $str);
            $patn = '/[\x00-\x1f\x7f]|[^\w \/+\-,.]/';
            break;
        case CMSSAN_PUNCT:
            $str = preg_replace('/([^a-zA-Z\d\x80-\xff])\1+/', '$1', $str);
            $patn = '/[\x00-\x1f"\';=?^`<>\x7f]/';
            break;
        case CMSSAN_PUNCTX:
            $patn = '~([^a-zA-Z\d\x80-\xff'.addcslashes($ex, '~').'])\1+~';
            $str = preg_replace($patn, '$1', $str);
            $patn = '/[\x00-\x1f"\';=?^`<>\x7f]/';
            break;
        case CMSSAN_FILE:
            $str = preg_replace('~\s+~', '_', $str);
            $patn = '~[\x00-\x1f*?\\/\x7f]~';
            break;
        case CMSSAN_PATH:
            $str = preg_replace(['~[\\/]+~', '~\s+~'], [DIRECTORY_SEPARATOR, '_'], $str);
            $patn = '/[\x00-\x1f*?\x7f]/';
            break;
        case CMSSAN_PURESPC:
            $patn = '/[^\w \-.\x80-\xff]/';
            break;
        default: // incl. CMSSAN_PURE
            $patn = '/[^\w\-.\x80-\xff]/';
            break;
    }
    return preg_replace($patn, '', $str);
}

//c.f. core::page functions CMSMS\specialize(), CMSMS\Database\Connection::escStr()
function specialize($val)
{
    if( $val === '' || $val === null ) {
        return '';
    }
    if( !is_array($val) ) {
        //lite XSS management, prob. irrelevant for content being displayed in installer
        $val = strtr($val,[';'=>'&#59;',':'=>'&#58;','+'=>'&#43;','-'=>'&#45;']);
        $val = preg_replace_callback_array([
         '/script/i' => function($matches) { return substr($matches[0],0,5).'&#'.ord($matches[0][5]).';'; },
         '/on/i' => function($matches) { return $matches[0][0].'&#'.ord($matches[0][1]).';'; },
         '/embed/i' => function($matches) { return substr($matches[0],0,4).'&#'.ord($matches[0][4]).';'; },
        ], $val);
        return \htmlspecialchars($val, ENT_QUOTES | ENT_SUBSTITUTE | ENT_XHTML, 'UTF-8', false);
    }
    specialize_array($val);
    return $val;
}

//c.f. core::page function CMSMS\specialize_array()
function specialize_array(array &$arr)
{
    foreach( $arr as &$val ) {
        if( is_string($val && !is_numeric($val)) ) {
            $val = specialize($val);
        } elseif( is_array($val) ) {
            specialize_array($val); // recurse
        }
    }
    unset($val);
}

//c.f. core::page function CMSMS\de_specialize()
function de_specialize($val)
{
    if( $val ) {
        if( !is_array($val) ) {
            $val = preg_replace_callback('/&#(\d+);/', function($matches) {
                return chr($matches[1]);
            }, $val);
            return \htmlspecialchars_decode($val, ENT_QUOTES | ENT_XHTML);
        }
//N/A	de_specialize_array($val);
    }
    return $val;
}

//N/A function de_specialize_array(&$arr) {}

/**
 *
 * @return string
 * @throws Exception
 */
function get_sys_tmpdir() : string
{
    if( function_exists('sys_get_temp_dir') ) {
        $tmp = rtrim(sys_get_temp_dir(),' \\/');
        if( $tmp && @is_dir($tmp) && @is_writable($tmp) ) return $tmp;
    }

    $vars = ['TMP','TMPDIR','TEMP'];
    foreach( $vars as $var ) {
        if( isset($_ENV[$var]) && $_ENV[$var] ) {
            $tmp = realpath($_ENV[$var]);
            if( $tmp && @is_dir($tmp) && @is_writable($tmp) ) return $tmp;
        }
    }

    $tmpdir = ini_get('upload_tmp_dir');
    if( $tmpdir && @is_dir($tmpdir) && @is_writable($tmpdir) ) return $tmpdir;

    if( ini_get('safe_mode') != '1' ) {
        // last ditch effort to find a place to write to.
        $tmp = @tempnam('','xxx');
        if( $tmp && is_file($tmp) ) {
            @unlink($tmp);
            return realpath(dirname($tmp));
        }
    }

    throw new Exception('Could not find a writable location for temporary files');
}

/**
 * Get filesystem permission 'modes' appropriate for the server to read and write
 * files and in directories. For the latter, access permission is included.
 * For read + write, the relevant 2 modes must be OR'd.
 * @since 2.99
 * @return array, 4 integer members
 * [0] file read
 * [1] file read+write
 * [2] dir read (+ access)
 * [3] dir read+write (+ access)
 * @throws Exception
 */
function get_server_permissions() : array
{
    static $modes = null;

    if( $modes === null ) {
        $fh = tmpfile();
        $fp = stream_get_meta_data($fh)['uri'];
        $chk = [0400, 0040, 0004];
        for( $i = 0; $i < 3; $i++ ) {
            @chmod($fp, $chk[$i]);
            if( is_readable($fp) ) {
                switch( $i ) {
                    case 0:
                        $modes = [0400, 0600, 0500, 0700];
                        break 2;
                    case 1:
                        $modes = [0040, 0060, 0050, 0070];
                        break 2;
                    case 2:
                        $modes = [0004, 0006, 0005, 0007];
                        break 2;
                }
            }
        }
        fclose($fh);
    }
    if( $modes ) {
        return $modes;
    }
    throw new Exception('Could not determine server permissions for file access');
}

/**
 * Recursively check whether a directory and all its contents are modifiable.
 *
 * @param  string  $path Start directory.
 * @param  bool    $ignore_specialfiles  Optionally ignore special system
 *  files in the scan. Such files include:
 *    files beginning with '.'
 *    php.ini files
 * @return bool
 */
function is_directory_writable( string $path, bool $ignore_specialfiles = true ) : bool
{
    global $_writable_error;

    if( $handle = @opendir( $path ) ) {
        if( !endswith($path,DIRECTORY_SEPARATOR) ) $path .= DIRECTORY_SEPARATOR;
        while( false !== ($file = readdir($handle)) ) {
            if( $file == '.' || $file == '..' ) continue;

            // ignore dotfiles, except .htaccess.
            if( $ignore_specialfiles ) {
                if( $file[0] == '.' && $file != '.htaccess' ) continue;
                if( $file == 'php.ini' ) continue;
            }

            $p = $path.$file;
            if( !@is_writable( $p ) ) {
                $_writable_error[] = $p;
                @closedir( $handle );
                return false;
            }

            if( @is_dir( $p ) ) {
                if( !is_directory_writable( $p, $ignore_specialfiles ) ) {
                    @closedir( $handle );
                    return false;
                }
            }
        }
        @closedir( $handle );
        return true;
    }
    $_writable_error[] = $path;
    return false;
}

/**
 * Recursive delete directory
 *
 * @param string $path filepath
 * @param bool $withtop Since 2.99 Optional flag whether to remove $path
 *  itself, as well as all its contents. Default true.
 */
function rrmdir($path, $withtop = true)
{
    if( is_dir($path) ) {
        $items = scandir($path);
        foreach( $items as $name ) {
            if( !($name == '.' || $name == '..') ) {
                if( filetype($path.DIRECTORY_SEPARATOR.$name) == 'dir' ) {
                    rrmdir($path.DIRECTORY_SEPARATOR.$name); //recurse
                }
                else {
                    @unlink($path.DIRECTORY_SEPARATOR.$name);
                }
            }
        }
        if( $withtop ) {
            if( is_link($path) ) {
                return @unlink($path);
            }
            elseif( is_dir($path) ) {
                return @rmdir($path);
            }
            else {
                return false;
            }
        }
        return true;
    }
}

/**
 * Recursive copy directory and all contents
 *
 * @param string $frompath filepath
 * @param string $topath filepath
 * @param bool $dummy whether to create empty 'index.html' file in each folder Default false
 */
function rcopy(string $frompath, string $topath, bool $dummy = false)
{
    $frompath = rtrim($frompath, '/\\');
    if( !is_dir($frompath) || !is_readable($frompath) ) return;
    $topath = rtrim($topath, '/\\');
    if( !is_dir($topath) || !is_writable($topath) ) return;

    $len = strlen($frompath);
    $iter = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(
            $frompath,
            FilesystemIterator::CURRENT_AS_PATHNAME |
            FilesystemIterator::SKIP_DOTS //|
//              FilesystemIterator::UNIX_PATHS //|
//              FilesystemIterator::FOLLOW_SYMLINKS too bad if links not relative !!
        ),
        RecursiveIteratorIterator::SELF_FIRST
    );

    $modes = get_server_permissions();
    $filemode = $modes[1]; // read + write OR just read in some cases?
    $dirmode = $modes[3]; // ditto + access

    foreach( $iter as $fp ) {
        $relpath = substr($fp, $len);
        $tp = $topath . DIRECTORY_SEPARATOR . $relpath;
        if( !is_link($fp) ) {
            if( !is_dir($fp)) {
                copy($fp, $tp);
                chmod($tp, $filemode);
            } else {
                mkdir($tp, $dirmode, true);
                if( $dummy ) { touch($tp . DIRECTORY_SEPARATOR . 'index.html'); }
            }
        } else {
            copy($fp, $tp);
            //TODO re-target the link
            if( !is_dir($fp) ) {
                chmod($tp, $filemode);
            } else {
                chmod($tp, $dirmode);
            }
        }
    }
}

/**
 *
 * @return array, maybe empty
 */
function get_writable_error() : array
{
    global $_writable_error;

    return $_writable_error ?? [];
}

/**
 * Get list of versions we can upgrade from.
 *
 * @return array
 * @throws Exception
 */
function get_upgrade_versions() : array
{
    $app_config = get_app()->get_config();
    $min_upgrade_version = $app_config['min_upgrade_version'];
    if( !$min_upgrade_version ) throw new Exception(lang('error_invalidconfig'));

    $dir = dirname(__DIR__).DIRECTORY_SEPARATOR.'upgrade';
    if( !is_dir($dir) ) throw new Exception(lang('error_internal','u100'));

    $dh = opendir($dir);
    if( !$dh ) throw new Exception(lang('error_internal','u102'));
    $versions = [];
    while( ($file = readdir($dh)) !== false ) {
        if( $file == '.' || $file == '..' ) continue;
        if( is_dir($dir.DIRECTORY_SEPARATOR.$file) &&
            (is_file("$dir/$file/MANIFEST.DAT.gz") || is_file("$dir/$file/MANIFEST.DAT") || is_file("$dir/$file/upgrade.php")) ) {
            if( version_compare($min_upgrade_version, $file) <= 0 ) $versions[] = $file;
        }
    }
    closedir($dh);
    if( $versions ) {
        usort($versions,'version_compare');
        return $versions;
    }
    return [];
}

/**
 * It is not an error to not have a changelog file
 * @param string $version
 * @return string
 * @throws Exception
 */
function get_upgrade_changelog(string $version) : string
{
    $dir = dirname(__DIR__).'/upgrade/'.$version;
    if( !is_dir($dir) ) throw new Exception(lang('error_internal','u103'));
    $files = ['CHANGELOG.txt','CHANGELOG.TXT','changelog.txt'];
    foreach( $files as $fn ) {
        if( is_file("$dir/$fn") ) {
            // convert text into some sort of html
            $tmp = @file_get_contents("$dir/$fn");
            $tmp = nl2br(wordwrap(htmlspecialchars($tmp),80));// NOT worth cms_installer\specialize
            return $tmp;
        }
    }
    return '';
}

/**
 * It is not an error to not have a readme file
 * @param type $version
 * @return string
 * @throws Exception
 */
function get_upgrade_readme(string $version) : string
{
    $dir = dirname(__DIR__).'/upgrade/'.$version;
    if( !is_dir($dir) ) throw new Exception(lang('error_internal','u104'));
    $files = ['README.HTML.INC','readme.html.inc','README.HTML','readme.html'];
    foreach( $files as $fn ) {
        if( is_file("$dir/$fn") ) return @file_get_contents("$dir/$fn");
    }
    if( is_file("$dir/readme.txt") ) {
        // convert text into some sort of html.
        $tmp = @file_get_contents("$dir/readme.txt");
        $tmp = nl2br(wordwrap(htmlspecialchars($tmp),80));// NOT worth cms_installer\specialize
        return $tmp;
    }
    return '';
}

/**
 * Unpack database connection parameters
 * @param string $creds encrypted db-credentials
 * @param string $pw optional P/W
 * @return array
 */
function decrypt_creds(string $creds, string $pw = '')
{
    $raw = base64_decode($creds);

    if( $pw === '' ) {
        $dir = dirname(__DIR__,2);
        $str = substr(__DIR__,strlen($dir));
        $pw = strtr($str, ['/'=>'','\\'=>'']); //TODO not site- or filesystem-specific
    }

//  replicates CMSMS\Crypto::decrypt_string($raw,$pw,'internal');
    $p = $lp = strlen($pw); //TODO handle php.ini setting mbstring.func_overload & 2 i.e. overloaded
    $lr = strlen($raw);
    $j = -1;

    for( $i = 0; $i < $lr; ++$i ) {
        $k = ord($raw[$i]) - $p;
        if( $k < 0) {
            $k += 256;
        }
        if( ++$j == $lp) { $j = 0; }
        $p = $k ^ ord($passwd[$j]);
        $raw[$i] = chr($p);
    }

    $arr = json_decode($raw,true);
    return $arr;
}

} //cms_installer namespace

namespace {

use cms_installer\wizard\wizard;

/**
 * Sanitization-type enumerator for sanitizeVal()
 */
if( !defined('CMSSAN_NONPRINT') ) {
    define('CMSSAN_NONPRINT',1);
    define('CMSSAN_HIGH',    2);
    define('CMSSAN_PUNCT',   4);
    define('CMSSAN_NAME',    8);
    define('CMSSAN_PUNCTX', 16);
    define('CMSSAN_PURE',   32);
    define('CMSSAN_PURESPC',64);
//  define('CMSSAN_', =  128); reserved
    define('CMSSAN_FILE',  512);
    define('CMSSAN_PATH', 1024);
    define('CMSSAN_ACCOUNT',2048);
//  define('CMSSAN_', 4096); reserved
}

// functions to generate GUI-installer messages

function verbose_msg(string $str)
{
    $obj = wizard::get_instance()->get_step();
    if( method_exists($obj,'verbose') ) $obj->verbose($str);
}

function status_msg(string $str)
{
    $obj = wizard::get_instance()->get_step();
    if( method_exists($obj,'message') ) $obj->message($str);
}

function error_msg(string $str)
{
    $obj = wizard::get_instance()->get_step();
    if( method_exists($obj,'error') ) $obj->error($str);
}

} //global namespace
