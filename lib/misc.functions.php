<?php
/*
Non-system-dependent utility-methods available during every request
Copyright (C) 2004-2021 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

/**
 * Miscellaneous support functions which are independent of CMSMS
 * specifics i.e. no system settings, defines, classes etc
 * Which means that when processing a request, this file may be included
 * at an early stage, to facilitate further setup.
 *
 * @package CMS
 * @license GPL
 */
namespace {
/**
 * Calculate the difference in seconds between two microtime() values.
 *
 * @since 0.3
 * @param string $a Earlier microtime value (i.e. like 'sec-fraction sec-whole')
 * @param string $b Later microtime value
 * @return float The difference, in seconds.
 */
function microtime_diff(string $a, string $b) : float
{
    list($a_dec, $a_sec) = explode(' ', $a);
    list($b_dec, $b_sec) = explode(' ', $b);
    return $b_sec - $a_sec + $b_dec - $a_dec;
}

/**
 * Join path-segments together using the platform-specific directory separator.
 *
 * This method should NOT be used for building URLS.
 *
 * This method accepts a variable number of string arguments
 * e.g. $out = cms_join_path($dir1,$dir2,$dir3,$filename)
 * or $out = cms_join_path($dir1,$dir2,$filename)
 * or $out = cms_join_path([$dir1,$dir2,$filename])
 *
 * @since 0.14
 * @return string
 */
function cms_join_path(...$args) : string
{
    if (is_array($args[0])) {
        $args = $args[0];
    }
    $path = implode(DIRECTORY_SEPARATOR, $args);
    return str_replace(['\\', DIRECTORY_SEPARATOR.DIRECTORY_SEPARATOR],
        [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
}

/**
 * Check the permissions of a directory recursively to check whether the
 * server has write-permission for each directory.
 *
 * @param  string  $path Start directory.
 * @return bool
 */
function is_directory_writable(string $path)
{
    if (!is_dir($path)) {
        return false;
    }

    if ($handle = opendir($path)) {
        if (!endswith($path, DIRECTORY_SEPARATOR)) {
            $path .= DIRECTORY_SEPARATOR;
        }
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $p = $path.$file;
            if (!@is_writable($p)) {
                closedir($handle);
                return false;
            }

            if (@is_dir($p)) {
                if (!is_directory_writable($p)) { //recurse
                    closedir($handle);
                    return false;
                }
            }
        }
        closedir($handle);
        return true;
    }
    return false;
}

/**
 * Return an array containing a list of files in a directory
 * performs a non recursive search.
 *
 * @internal
 * @param string $dir path to search
 * @param string $extensions include only files matching these extensions. case insensitive, comma delimited
 * @param bool   $excludedot
 * @param bool   $excludedir
 * @param string $fileprefix
 * @param bool   $excludefiles
 * @return mixed bool or array
 * Rolf: only used in this file
 */
function get_matching_files(string $dir, string $extensions = '', bool $excludedot = true, bool $excludedir = true, string $fileprefix = '', bool $excludefiles = true)
{
    if (!is_dir($dir)) {
        return false;
    }
    $dh = opendir($dir);
    if (!$dh) {
        return false;
    }

    if (!empty($extensions)) {
        $extensions = explode(',', strtolower($extensions));
    }
    $results = [];
    while (false !== ($file = readdir($dh))) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        if (startswith($file, '.') && $excludedot) {
            continue;
        }
        if (is_dir(cms_join_path($dir, $file)) && $excludedir) {
            continue;
        }
        if (!empty($fileprefix)) {
            if ($excludefiles && startswith($file, $fileprefix)) {
                continue;
            }
            if (!$excludefiles && !startswith($file, $fileprefix)) {
                continue;
            }
        }

        $ext = strtolower(substr($file, strrpos($file, '.') + 1));
        if (is_array($extensions) && count($extensions) && !in_array($ext, $extensions)) {
            continue;
        }

        $results[] = $file;
    }
    closedir($dh);
    if (!count($results)) {
        return false;
    }
    return $results;
}

/**
 * Get sorted list of paths of files and/or directories in, and descendant from, the
 * specified directory
 *
 * @since 2.99, reported directories do not have a trailing separator
 * @param  string  $path     start path
 * @param  array   $excludes Optional array of regular-expressions indicating (path)names
 *  of files to exclude. Default []
 *  '.' and '..' are automatically excluded.
 * @param  int     $maxdepth Optional max. depth to browse (-1=unlimited). Default -1
 * @param  string  $mode     Optional "FULL"|"DIRS"|"FILES". Default "FULL"
 * @return array
 * @throws UnexpectedValueException upon filesystem access error
 */
function get_recursive_file_list(string $path, array $excludes = [], int $maxdepth = -1, string $mode = 'FULL') : array
{
    $fn = function(string $name, array $excludes) : bool {
        foreach ($excludes as $excl) {
            if (@preg_match('/'.$excl.'/i', $name)) {
                return true;
            }
        }
        return false;
    };

    $results = [];
    if (is_dir($path)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::KEY_AS_FILENAME |
                FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST);
        if ($maxdepth >= 0) {
            $iter->setMaxDepth($maxdepth);
        }
        foreach ($iter as $name => $p) {
            if (!($excludes && $fn($p, $excludes))) {
                if ($iter->getInnerIterator()->isDir()) {
                    if ($mode != 'FILES') {
                        $results[] = $p;
                    }
                } elseif ($mode != 'DIRS') {
                    $results[] = $p;
                }
            }
        }
        natcasesort($results);
    }
    return $results;
}

/**
 * Delete directory $path, and all files and folders in it. Equivalent to command rm -r.
 *
 * @param string $path The directory filepath
 * @param bool $withtop Since 2.99 Optional flag whether to remove the
 *  topmost (first-nominated) folder (or just clear it). Default true.
 * @return bool indicating complete success
 */
function recursive_delete(string $path, bool $withtop = true) : bool
{
    if (is_dir($path)) {
        $res = true;
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS
          ), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iter as $p) {
            if (is_dir($p)) {
                if (!@rmdir($p)) {
                    $res = false;
                }
            } elseif (!@unlink($p)) {
                $res = false;
            }
        }
        if ($res && $withtop) {
            $res = @rmdir($path);
        }
        return $res;
    }
    return false;
}

/**
 * Get filesystem permission 'modes' appropriate for the server to read and/or
 * write files and inside directories. For the latter, access permission is included.
 * @since 2.99
 * @return array, 4 integer members
 * [0] file read
 * [1] file read+write
 * [2] dir read (+ access)
 * [3] dir read+write (+ access)
 * @throws Exception if no permissions can be determined
 */
function get_server_permissions() : array
{
    static $modes = null;
    if ($modes === null) {
        $modes = [];
        $fh = tmpfile();
        $fp = stream_get_meta_data($fh)['uri'];
        $modes = []; //4 members: item-read, item-read/write, item-read/access, item-read/write/access (access == exec for files)
        $chk = [0400, 0040, 0004];
        for ($i = 0; $i < 3; ++$i) {
            @chmod($fp, $chk[$i]);
            if (is_readable($fp)) {
                switch ($i) {
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
    if ($modes) {
        return $modes;
    }
    throw new Exception('Could not determine server permissions for file access');
}

/**
 * Chmod $path, and if it's a directory, all files and folders in it and descendants.
 * Links are not changed.
 * @since 2.99
 * @since ? as deprecated chmod_r
 * @see chmod
 *
 * @param string $path The start location
 * @param int   $dirmode Optional permissions for dirs and dir-links. Default 0, hence read+write from get_server_permissions()
 * @param int   $filemode since 2.99 Optional permissions for non-dirs. Default 0, hence read+write from get_server_permissions()
 * @return bool indicating complete success
 */
function recursive_chmod(string $path, int $dirmode = 0, int $filemode = 0) : bool
{
    $res = true;
    if ($dirmode == 0) {
        $modes = get_server_permissions();
        $dirmode = $modes[3];
    }
    if ($filemode == 0) {
        if (!isset($modes)) {
            $modes = get_server_permissions();
        }
        $filemode = $modes[1];
    }
    if (is_dir($path)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS
          ), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iter as $p) {
            if (!(is_link($p) || @chmod($p, $dirmode))) {
                $res = false;
            }
        }
    }
    if (!is_link($path)) {
        return @chmod($path, $filemode) && $res;
    }
    return $res;
}

/**
 * Test whether one string starts with another
 * @see also PHP8+ str_starts_with() which is always case-sensitive
 *
 * @param string $str The string to test against
 * @param string $sub The search string
 * @param bool   $exact since 2.99 optional flag whether to do case-sensitive check. Default true.
 * @return bool
 */
function startswith(string $str, string $sub, $exact = true) : bool
{
    $o = strlen($sub);
    if ($o > 0) {
        return ($exact) ? strncmp($str, $sub, $o) === 0 : strncasecmp($str, $sub, $o) === 0;
    }
    return false;
}

/**
 * Test whether one string ends with another
 * @see also PHP8+ str_ends_with() which is always case-sensitive
 *
 * @param string $str The string to test against
 * @param string $sub The search string
 * @param bool   $exact since 2.99 optional flag whether to do case-sensitive check. Default true.
 * @return bool
 */
function endswith(string $str, string $sub, $exact = true) : bool
{
    $o = strlen($sub);
    if ($o > 0) {
        return substr_compare($str, $sub, -$o, $o, !$exact) === 0;
    }
    return false;
}

/**
 * Get a modified form of the supplied string, suitable for page-aliases
 * (hence pretty-URLs, partial page-URLs, routes), whole- or partial-
 * filenames, etc
 * PHP regex word-chars (incl. unichars), '-', '.', and if requested '/',
 * are retained.
 * Spaces become '-'.
 * This is more limited than an actual URL c.f. CMSMS\urlencode()  and
 * more limited than a filename c.f. CMSMS\sanitizeVal( ,CMSSAN_FILE)
 *
 * @param mixed $str String to convert, or null
 * @param bool  $tolower Optional flag whether output string should be converted to lower case. Default false
 * @param bool  $withslash Optional flag whether slashes should be retained in the output. Default false
 * @return string
 */
function munge_string_to_url($str, bool $tolower = false, bool $withslash = false) : string
{
    if (!($str || is_numeric($str))) {
        return '';
    }
    // NOTE any protocol-change here also requires that all recorded page-aliases, routes etc be updated accordingly
    // handle embedded spaces, maybe slashes
    $tmp = ($withslash) ?
        strtr(trim($str), ' ', '-') :
        strtr(trim($str), ['/' => '', ' ' => '-']);

    if ($tolower) {
        if (preg_match('/\x80-\xff/', $tmp) && function_exists('mb_strtolower')) {
            $str = mb_strtolower($tmp);
        } else {
            $str = strtolower($tmp); //TODO unichars unchanged if mb_string N/A
        }
    } else {
        $str = $tmp;
    }
    // remove other unwanted chars and multiple dashes
    $tmp = preg_replace(['/[^\w\pL\-.]/u', '/(\-)\1+/'], ['', '-'], $str);
    return $tmp;
}

/**
 * Return a bool value corresponding to a given php ini bool key.
 *
 * @param string $str The php ini key
 * @return bool
 */
function ini_get_boolean(string $str) : bool
{
    return cms_to_bool(ini_get($str));
}

/**
 * Test whether an IP address matches a list of expressions.
 * Credits to J.Adams <jna@retins.net>
 *
 * Matches:
 * xxx.xxx.xxx.xxx       (exact)
 * xxx.xxx.xxx.[yyy-zzz] (range)
 * xxx.xxx.xxx.xxx/nn   (nn = # bits, cisco style -- i.e. /24 = class C)
 *
 * Does not match:
 * xxx.xxx.xxx.xx[yyy-zzz] (range, partial octets nnnnnot supported)
 *
 * @param string $ip IP address to test
 * @param mixed $checklist Array or comma-separated string of match expressions
 * @return bool
 * Rolf: only used in lib/content.functions.php
 */
function cms_ipmatches(string $ip, $checklist) : bool
{
    $_testip = function($range, $ip) {
        $result = 1;

        $regs = [];
        if (preg_match('/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)\/([0-9]+)/', $range, $regs)) {
            // perform a mask match
            $ipl = ip2long($ip);
            $rangel = ip2long($regs[1] . '.' . $regs[2] . '.' . $regs[3] . '.' . $regs[4]);

            $maskl = 0;

            for ($i = 0; $i < 31; ++$i) {
                if ($i < $regs[5] - 1) {
                    $maskl = $maskl + pow(2, (30 - $i));
                }
            }

            return ($maskl & $rangel) == ($maskl & $ipl);
        } else {
            // range based
            $maskocts = explode('.', $range);
            $ipocts = explode('.', $ip);

            if (count($maskocts) != count($ipocts) && count($maskocts) != 4) {
                return 0;
            }

            // perform a range match
            for ($i = 0; $i < 4; ++$i) {
                if (preg_match('/\[([0-9]+)\-([0-9]+)\]/', $maskocts[$i], $regs)) {
                    if (($ipocts[$i] > $regs[2]) || ($ipocts[$i] < $regs[1])) {
                        $result = 0;
                    }
                } elseif ($maskocts[$i] <> $ipocts[$i]) {
                    $result = 0;
                }
            }
        }
        return $result;
    }; // _testip

    if (!is_array($checklist)) {
        $checklist = explode(',', $checklist);
    }
    foreach ($checklist as $one) {
        if ($_testip(trim($one), $ip)) {
            return true;
        }
    }
    return false;
}

/**
 * Test whether the provided string is (potentially) a valid email address.
 * NOTE: this test is more tolerant than for SMTP-transferred emails
 * @see RFC5321
 *
 * @param mixed string | null $email
 * @param bool $checkDNS Optional flag, whether to check (if possible) the address-domain. Default false
 * @return mixed string (trim()'d $email) | false
 */
function is_email($email, bool $checkDNS = false)
{
    //PHP's FILTER_VALIDATE_EMAIL mechanism is not entirely reliable - see notes at https://www.php.net/manual/en/function.filter-var.php
    $email = trim('' . $email);
    if (!preg_match('/\S+.*@[\w.\-\x80-\xff]+$/', $email)) {
        return false;
    }
    if ($checkDNS && function_exists('checkdnsrr')) {
        list($user, $domain) = explode('@', $email, 2);
        if (!(checkdnsrr($domain, 'A') || checkdnsrr($domain, 'MX'))) {
            return false;
        } // Domain doesn't actually exist
    }
    return $email;
}

/**
 * Convert the supplied value to a corresponding bool.
 * Accepts number != 0, 'y','yes','true','on' as true (case insensitive) all other values represent false.
 *
 * @param mixed $val string|bool|null Input to test.
 */
function cms_to_bool($val) : bool
{
    if (is_bool($val)) {
        return $val;
    }
    if (is_numeric($val)) {
        return $val + 0 != 0;
    }

    switch (strtolower($val)) {
        case 'y':
        case 'yes':
        case 'true':
        case 'on':
            return true;
        default:
            return false;
    }
}

/**
 * Cache a callable for use immediately before the current session terminates
 * @ignore
 * @since 2.99
 */
function register_endsession_function(callable $handler)
{
    if (!isset($_SESSION['end_handlers'])) {
        $_SESSION['end_handlers'] = [];
    }
    $_SESSION['end_handlers'][] = json_encode($handler);
}

/**
 * Test whether a string is (potentially) base64-encoded
 *
 * @since 2.2
 * @param string $s The string to check
 * @return bool
 */
function is_base64(string $s) : bool
{
    return (bool) preg_match('~^[a-zA-Z0-9+/\r\n]+={0,2}$~', $s);
}

/**
 * Sanitization-type enumerator for sanitizeVal()
 */
const CMSSAN_NONPRINT = 1;
const CMSSAN_HIGH     = 2;
const CMSSAN_PUNCT    = 4;
const CMSSAN_NAME     = 8;
const CMSSAN_PUNCTX   = 16;
const CMSSAN_PURE     = 32;
const CMSSAN_PURESPC  = 64;
//const CMSSAN_ = 128;
const CMSSAN_FILE     = 512;
const CMSSAN_PATH     = 1024;
const CMSSAN_ACCOUNT  = 2048;
//const CMSSAN_ = 4096;

} // global namespace

namespace CMSMS {

use const CMSSAN_NONPRINT;
use const CMSSAN_HIGH;
use const CMSSAN_PUNCT;
use const CMSSAN_NAME;
use const CMSSAN_PUNCTX;
use const CMSSAN_PURE;
use const CMSSAN_PURESPC;
use const CMSSAN_FILE;
use const CMSSAN_PATH;
use const CMSSAN_ACCOUNT;
use function CMSMS\de_entitize;
use function CMSMS\entitize;

/**
 * Scrub inappropriate chars from the supplied string.
 * This is a cousin of PHP's filter_var(FILTER_SANITIZE...).
 * @internal
 * @since 2.99
 *
 * @param string $str String to be cleaned
 * @param int $scope Optional enumerator
 * CMSSAN_NONPRINT
 *  remove non-printable chars < 0x80 (e.g. for a password, anything else minimally-restricted)
 * CMSSAN_HIGH
 *  as for CMSSAN_NONPRINT, but also remove chars >= 0x80
 *    NOTE html allows element props/attributes (e.g. name, id) to
 *    include meta-characters !"#$%&'()*+,./:;<=>?@[\]^`{|}~ but any
 *    such must sometimes be escaped e.g. in a jQuery selector
 * CMSSAN_PUNCT
 *  remove non-printable chars < 0x80 plus these: " ' ; = ? ^ ` < >
 *    plus repeats of non-alphanum chars
 *    (e.g. for a 'normal' html-element attribute value)
 *    Hence allowed are non-repeats of non-word-chars other than the above e.g. ~ & @ % : [ ] ( ) { } \ / space
 *    but removals include the 2nd from valid pairs e.g. \\ :: __
 * CMSSAN_NAME
 *  as for CMSSAN_PUNCT, but allow non-repeats of these: _ / + - , . space
 *    which are allowed by AdminUtils::is_valid_itemname()
 *    NOTE '/' and space i.e. not for possibly-file-stored items
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
 *  email address accepted verbatim, otherwise treated as scope CMSSAN_NONPRINT (in future, maybe CMSSAN_PURE or CMSSAN_PURESPC per username policy)
 *    for user/account name
 *
 *  If scope > CMSSAN_NONPRINT, the supplied string is also trim()'d
 *  Only scope CMSSAN_NONPRINT is liberal enough to suit processing a generic
 *  'description' or 'title' value. At least apply CMSMS\de_specialize() to those.
 *  Maybe CMSMS\execSpecialize(), nl2br(), striptags().
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

/**
 * Encode relevant chars in the supplied URL string, and strip non-printable chars < 0x80
 * @since 2.99
 * @param string $str
 *
 * @param string $keeps Optional valid verbatim chars.
 *  Default chars per rfc 3986 for URL query and fragment i.e. any of
 *   unreserved | pct-encoded | sub-delims | ":" | "@" | "/" | "?" | "%" (the latter an extra for already-encoded)
 *   But '?' and '&' are not verbatim, so any of those which is a query-separator
 *   must be handled independently
 * @return string
 */
function urlencode(string $str, string $keeps = '\w.~!$&\'()*\-+,/:;=%') : string
{
    $patn = '/[^' . addcslashes($keeps, '/') . ']/';
    return preg_replace_callback_array([
        '/\x00-\x1f\x7f/' => function() { return ''; },
        $patn => function($matches) { return rawurlencode($matches[0]); }
    ], $str);
}

/**
 * Munge risky content of the supplied string.
 * Intended for application to relevant untrusted values prior to their display in a page.
 * Handles php-start tags, script tags, js executables, '`' chars which would
 * be a problem in pages, templates, but TODO some might be ok in UDT content
 * in a textarea element?
 * Entitized content is interpreted, but not (url-, rawurl-, base64-) encoded content.
 * Does not deal with image-file content. Inline <svg/> will be handled anyway.
 * @internal
 * @since 2.99
 * @see https://portswigger.net/web-security/cross-site-scripting/cheat-sheet
 * @see https://owasp.org/www-community/xss-filter-evasion-cheatsheet
 *
 * @param string $val input value
 * @return string
 */
function execSpecialize(string $val) : string
{
    $tmp = de_entitize($val);
    if ($tmp === $val) {
        $revert = false;
    } else {
        $revert = true;
        $val = $tmp;
    }
    // munge PHP tags
    $val = preg_replace(['/<\?php/i', '/<\?=/', '/<\?(\s|\n)/'], ['&#60;&#63;php', '&#60;&#63;=', '&#60;&#63; '], $val);
    //TODO maybe disable SmartyBC-supported {php}{/php} in $val
    //$val = preg_replace('~\{/?php\}~', '', $val); but with current smarty delim's
    $val = str_replace('`', '&#96;', $val);
    $val = preg_replace_callback_array([
         // script tags like <script or <script> or <script X> X = e.g. 'defer'
        '/<\s*(scrip)t([^>]*)(>?)/i' => function($matches) {
            return '&#60;'.$matches[1].'&#116;'.($matches[2] ? ' '.trim($matches[2]) : '').($matches[3] ? '&#62;' : '');
        },
        // explicit script
        '/jav(.+)(scrip)t\s*:\s*(.+)?/i' => function($matches) {
            if ($matches[3]) {
                return 'ja&#118;'.trim($matches[1]).$matches[2].'&#116;&#58;'.strtr($matches[3], ['(' => '&#40;', ')' => '&#41;']);
            }
            return $matches[0];
        },
        // inline scripts like on*="dostuff" or on*=dostuff (TODO others e.g. FSCommand(), seekSegmentTime() @ http://help.dottoro.com)
        '/(on\w+)\s*=\s*(["\']?.+["\']?)/i' => function($matches) {
            return $matches[1].'&#61;'.strtr($matches[2], ['"' => '&#34;', "'" => '&#39;', '(' => '&#40;', ')' => '&#41;']);
        },
        // embeds
        '/(embe)(d)/i' => function($matches) {
            return $matches[1].'&#'.ord($matches[2]).';';
        },
        ], $val);

    return ($revert) ? entitize($val) : $val;
}

/**
 * Create an almost-certainly-unique identifier.
 *
 * @since 2.99
 * @return string 32 random hexits
 */
function create_guid() : string
{
    return bin2hex(random_bytes(16));
}

/**
 * Sort array of strings which include, or may do so, non-ASCII-encoded char(s)
 * @param array $arr data to be sorted
 * @param bool $preserve Optional flag whether to preserve key-value associations during the sort Default false
 * @since 2.99
 * @return sorted array
*/
function utf8_sort(array $arr, bool $preserve = false) : array
{
    $enc = null; //TODO something relevant to site e.g. func($config), func(ini_get()), func(LOCALE), some Nls func
    $collator = new Collator($enc);
    if ($preserve) {
        $collator->asort($arr);
    } else {
        $collator->sort($arr);
    }
    return $arr;
}

} // namespace CMSMS
