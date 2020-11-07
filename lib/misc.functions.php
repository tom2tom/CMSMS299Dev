<?php
/*
Non-system-dependent utility-methods available during every request
Copyright (C) 2004-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
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
 * Return $value if it's set and same basic type as $default.
 * Otherwise return $default. Note: trim's $value if it's not numeric.
 *
 * @ignore
 * @param mixed $value
 * @param mixed $default Optional default value to return. Default ''.
 * @param string $session_key Optional key for retrieving the default value from $_SESSION[]. Default ''
 * @deprecated
 * @return mixed
 */
function _get_value_with_default($value, $default = '', $session_key = '')
{
    if ($session_key != '') {
        if (isset($_SESSION['default_values'][$session_key])) $default = $_SESSION['default_values'][$session_key];
    }

    // set our return value to the default initially and overwrite with $value if we like it.
    $return_value = $default;

    if (isset($value)) {
        if (is_array($value)) {
            // $value is an array - validate each element.
            $return_value = [];
            foreach($value as $element) {
                $return_value[] = _get_value_with_default($element, $default);
            }
        } else {
            if (is_numeric($default)) {
                if (is_numeric($value)) {
                    $return_value = $value;
                }
            } else {
                $return_value = trim($value);
            }
        }
    }

    if ($session_key != '') $_SESSION['default_values'][$session_key] = $return_value;
    return $return_value;
}

/**
 * Retrieve a (scalar or array) value from the supplied $parameters array.
 * Returns $default or $_SESSION['parameter_values'][$session_key] if $key is not in $parameters.
 * Note: This function trims string values.
 *
 * @param array $parameters
 * @param string $key The wanted member of $parameters
 * @param mixed $default Optional default value to return. Default ''.
 * @param string $session_key Optional key for retrieving the default value from $_SESSION[]. Default ''
 * @return mixed
 */
function get_parameter_value(array $parameters, string $key, $default = '', string $session_key = '')
{
    if ($session_key != '') {
        if (isset($_SESSION['parameter_values'][$session_key])) $default = $_SESSION['parameter_values'][$session_key];
    }

    // set our return value to the default initially and overwrite with $parameters value if we like it.
    $return_value = $default;
    if (isset($parameters[$key])) {
        if (is_bool($default)) {
            // want a bool return_value
            if (isset($parameters[$key])) $return_value = cms_to_bool((string)$parameters[$key]);
        } elseif (is_numeric($default)) {
            // default value is a number, we only like $parameters[$key] if it's a number too.
            if (is_numeric($parameters[$key])) $return_value = $parameters[$key] + 0;
        } elseif (is_string($default)) {
            $return_value = trim($parameters[$key]);
        } elseif (is_array($parameters[$key])) {
            // $parameters[$key] is an array - validate each element.
            $return_value = [];
            foreach ($parameters[$key] as $element) {
                $return_value[] = _get_value_with_default($element, $default);
            }
        } else {
             $return_value = $parameters[$key];
        }
    }

    if ($session_key != '') $_SESSION['parameter_values'][$session_key] = $return_value;
    return $return_value;
}

/**
 * Check the permissions of a directory recursively to make sure that
 * we have write-permission for all files.
 *
 * @param  string  $path Start directory.
 * @return bool
 */
function is_directory_writable(string $path)
{
    if (!is_dir($path)) return false;

    if ($handle = opendir($path)) {
        if (!endswith($path,DIRECTORY_SEPARATOR)) $path .= DIRECTORY_SEPARATOR;
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..') continue;

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
function get_matching_files(string $dir,string $extensions = '',bool $excludedot = true,bool $excludedir = true, string $fileprefix = '',bool $excludefiles = true)
{
    if (!is_dir($dir)) return false;
    $dh = opendir($dir);
    if (!$dh) return false;

    if (!empty($extensions)) $extensions = explode(',',strtolower($extensions));
    $results = [];
    while (false !== ($file = readdir($dh))) {
        if ($file == '.' || $file == '..') continue;
        if (startswith($file,'.') && $excludedot) continue;
        if (is_dir(cms_join_path($dir,$file)) && $excludedir) continue;
        if (!empty($fileprefix)) {
            if ($excludefiles && startswith($file,$fileprefix)) continue;
            if (!$excludefiles && !startswith($file,$fileprefix)) continue;
        }

        $ext = strtolower(substr($file,strrpos($file,'.')+1));
        if (is_array($extensions) && count($extensions) && !in_array($ext,$extensions)) continue;

        $results[] = $file;
    }
    closedir($dh);
    if (!count($results)) return false;
    return $results;
}

/**
 * Get sorted list of paths of files and/or directories in, and descendant from, the
 * specified directory
 *
 * @since 2.9, reported directories do not have a trailing separator
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
    $fn = function (string $name, array $excludes) : bool
    {
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
        foreach ($iter as $name=>$p) {
            if (!($excludes && $fn($p, $excludes))) {
                if ($iter->getInnerIterator()->isDir()) {
                    if ($mode != 'FILES') { $results[] = $p; }
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
 * @param bool $withtop Since 2.9 Optional flag whether to remove the
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
 * Recursively chmod $path, and if it's a directory, all files and folders in it.
 * Links are not changed.
 *  Rolf: only used in admin/listmodules.php
 *
 * @see chmod
 *
 * @param string $path The start location
 * @param int   $mode The octal mode
 * @return bool indicating complete success
 */
function chmod_r(string $path, $mode) : bool
{
    $res = true;
    if (is_dir($path)) {
        $iter = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path,
                FilesystemIterator::CURRENT_AS_PATHNAME |
                FilesystemIterator::SKIP_DOTS
          ), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($iter as $p) {
            if (!(is_link($p) || @chmod($p, $mode))) {
                $res = false;
            }
        }
    }
    if (!is_link($path)) {
        return @chmod($path, $mode) && $res;
    }
    return $res;
}

/**
 * Test whether one string starts with another.
 *
 * e.g. startswith('The Quick Brown Fox','The');
 *
 * @param string $str The string to test against
 * @param string $sub The search string
 * @return bool
 */
function startswith(string $str, string $sub) : bool
{
    $o = strlen($sub);
    if ($o > 0) {
        return strncmp($str, $sub, $o) === 0;
    }
    return false;
}

/**
 * Similar to the startswith method, test whether string A ends with string B.
 *
 * e.g. endswith('The Quick Brown Fox','Fox');
 *
 * @param string $str The string to test against
 * @param string $sub The search string
 * @return bool
 */
function endswith(string $str, string $sub) : bool
{
    $o = strlen($sub);
    if ($o > 0) {
        return substr_compare($str, $sub, -$o, $o) === 0;
    }
    return false;
}

/**
 * Get an URL-usable representation of a human-readable string.
 *
 * @param mixed $alias String to convert, or null
 * @param bool   $tolower Indicates whether output string should be converted to lower case
 * @param bool   $withslash Indicates whether slashes should be allowed in the input.
 * @return string
 */
function munge_string_to_url($alias, bool $tolower = false, bool $withslash = false) : string
{
    if (!$alias) {
        return '';
    }
    if ($tolower) {
        if (function_exists('mb_strtolower')) {
            $alias = mb_strtolower($alias);
        } else {
            $alias = strtolower($alias); //TODO if mb_string N/A
        }
    }

    // remove invalid chars
    if ($withslash) { $patn = '/[^\p{L}_\.\-\ \d\/]/u'; }
    else { $patn = '/[^\p{L}_\-\.\ \d]/u'; }
    $tmp = trim(preg_replace($patn, '', $alias));

    // remove spaces and extra dashes
    $tmp = strtr($tmp, [
      ' ' => '-',
      '---' => '-',
      '--' => '-']);
    return trim($tmp);
}

/**
 * Scrub inappropriate chars from the supplied string
 * If $scope > 0, the supplied string is also trim()'d
 * Unless $scope = 0, PHP7+ tag-starters <?php, <?=, <? are munged to
 * corresponding html chars (&#nn;), if not removed
 * @internal
 * @since 2.9
 *
 * @param string $str String to be cleaned
 * @param int $scope Optional enumerator
 *  0 remove non-printable chars < 0x80 (e.g. for a password)
 *  1 remove non-printable chars < 0x80 plus these: " ' : ; = ? ^ ` plus repeats of non-alphanum chars
 *    (e.g. for an html-element attribute value BUT stet some punctuation e.g. '& < > ( ) { }')
 *  2 (default) remove non-'word' chars < 0x80, other than these: - .
 *    (e.g. for a 'sensible' 1-word name)
 *  3 remove invalid filesystem-path chars
 * @return string
 */
function cleanString(string $str, int $scope = 2) : string
{
    switch ($scope) {
        case 0:
            $patn = '/[\x00-\x1f\x7f]/';
            break;
        case 1:
            $str = preg_replace(
              ['/([^a-zA-Z\d\x80-\xff])\1+/','/<\?php/i','/<\?=/','/<\?\s/'],
              ['$1','&#60;&#63;php','&#60;&#63;&#61;','&#60;&#63; '],
              $str);
            $patn = '/[\x00-\x1f"\':;=?^`\x7f]/';
            break;
        case 3:
            $patn = '/[\x00-\x1f*?\x7f]/';
            break;
        default:
            $patn = '/[^\w\-.\x80-\xff]/';
            break;
    }
    if ($scope > 0) { $str = trim($str); }
    return preg_replace($patn, '', $str);
}

/**
 * Sanitize a value (if it's a string or array of such) to support verbatim
 * inclusion of the value inside [x]html tags, and importantly, also to
 * prevent XSS and other nasty stuff. In effect, a custom version of
 * htmlspecialchars().
 * NOTE: in many contexts this should be applied to data to be
 * displayed/output, but not necessarily (or at least not immediately)
 * to received/input from an un-trusted source.
 * This function does nothing for SQL-injection mitigation.
 *
 * @internal
 * @param mixed $val input value
 * @return mixed
 */
function cleanValue($val)
{
/*
// Taken from cakephp (http://cakephp.org)
// Licensed under the MIT License
  if (!$val) return $val;
  //Replace odd spaces with safe ones
  $val = str_replace(" ", " ", $val);
  $val = str_replace(chr(0xCA), "", $val);
  //Encode any HTML to entities (including \n --> <br />)
  $_cleanHtml = function($string,$remove = false) {
    if ($remove) {
      $string = strip_tags($string);
    } else {
      $patterns = array("/\&/", "/%/", "/</", "/>/", '/"/', "/'/", "/\(/", "/\)/", "/\+/", "/-/");
      $replacements = array("&amp;", "&#37;", "&lt;", "&gt;", "&quot;", "&#39;", "&#40;", "&#41;", "&#43;", "&#45;");
      $string = preg_replace($patterns, $replacements, $string);
    }
    return $string;
  };
  $val = $_cleanHtml($val);
  //Double-check special chars and remove carriage returns
  //For increased SQL security
  $val = preg_replace("/\\\$/", "$", $val);
  $val = preg_replace("/\r/", "", $val);
  $val = str_replace("!", "!", $val);
  $val = str_replace("'", "'", $val);
  //Allow unicode (?)
  $val = preg_replace("/&amp;#([0-9]+);/s", "&#\\1;", $val);
  //Add slashes for SQL
  //$val = $this->sql($val);
  //Swap user-inputted backslashes (?)
  $val = preg_replace("/\\\(?!&amp;#|\?#)/", "\\", $val);
*/
    if (is_array($val)) {
        return cleanArray($val); // iterate
    }
    if (is_string($val) && $val !== '') {
        // eliminate multi-backslashes other than valid backslash-pairs
        $l = strlen($val);
        $p = 0;
        while (($p = strpos($val, '\\', $p)) !== false) {
            if ($p+3 < $l && $val[$p+1] == '\\' && $val[$p+2] == '\\') {
                switch ($val[$p+3]) {
                    case '\\': // skip past '\\\\'
                        $p += 4;
                        break;
                    case '`': // omit '\\\' followed by ASCII char to be encoded
                    case "'":
                    case '"':
                    case '<':
                    case '>':
                        $val = substr($val, 0,  $p) . substr($val, $p+3);
                        $l -= 3;
                    break;
                    default: // omit '\\' followed by '\'
                        $val = substr($val, 0,  $p) . substr($val, $p+2);
                        $l -= 2;
                        break;
                }
            } elseif ($p+1 < $l) {
                switch ($val[$p+1]) {
                    case '`': // omit '\' followed by char to be encoded
                    case "'":
                    case '"':
                    case '<':
                    case '>':
                        $val = substr($val, 0,  $p) . substr($val, $p+1);
                        --$l;
                        break;
                    default:
                        ++$p;
                        break;
                }
            } elseif ($p+1 == $l) {
                $val = substr($val, 0,  $p);
                --$l;
                break;
            } else {
                ++$p;
            }
        }

        // munge risky tags
        $val = preg_replace(['/<\?php/i','/<\?=/','/<\?\s/'], ['&#60;&#63;php','&#60;&#63;&#61;','&#60;&#63; '], $val);
        $val = '' . preg_replace_callback('/(javascript\s*):\s*(.+)?/i', function($matches) {
            if ($matches[2]) {
                return 'javascrip&#116;&#58;'.strtr($matches[2], ['('=>'&#40;', ')'=>'&#41;']);
            }
            return $matches[0];
        }, $val);

        // munge | remove 'special' chars < 0x80 (non-printable and <>`'")
        $val = '' . preg_replace_callback('/[\x00-\x1f`\'"<>\x7f]/', function($matches) {
            $n = ord($matches[0]);
            return ($n > 31 && $n <  127) ? "&#{$n};" : '';
        }, $val);
    }
    return $val;
}

/**
 * Sanitize (in-place) an array of values to prevent XSS and other nasty stuff.
 * Used (almost entirely) on $_SERVER[] and/or $_GET[]
 *
 * @internal
 * @param array $array reference to array of values
 */
function cleanArray(array &$array)
{
    foreach ($array as &$val) {
        $val = cleanValue($val);
    }
    unset($val);
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
    $_testip = function($range,$ip) {
        $result = 1;

        $regs = [];
        if (preg_match("/([0-9]+)\.([0-9]+)\.([0-9]+)\.([0-9]+)\/([0-9]+)/",$range,$regs)) {
      // perform a mask match
      $ipl = ip2long($ip);
      $rangel = ip2long($regs[1] . '.' . $regs[2] . '.' . $regs[3] . '.' . $regs[4]);

      $maskl = 0;

      for ($i = 0; $i< 31; $i++) {
         if ($i < $regs[5]-1) $maskl = $maskl + pow(2,(30-$i));
      }

      return ($maskl & $rangel) == ($maskl & $ipl);
    } else {
      // range based
      $maskocts = explode('.',$range);
      $ipocts = explode('.',$ip);

      if (count($maskocts) != count($ipocts) && count($maskocts) != 4) return 0;

      // perform a range match
      for ($i = 0; $i < 4; $i++) {
        if (preg_match("/\[([0-9]+)\-([0-9]+)\]/",$maskocts[$i],$regs)) {
          if (($ipocts[$i] > $regs[2]) || ($ipocts[$i] < $regs[1])) $result = 0;
        } elseif ($maskocts[$i] <> $ipocts[$i]) $result = 0;
      }
    }
        return $result;
    }; // _testip

    if (!is_array($checklist)) $checklist = explode(',',$checklist);
    foreach ($checklist as $one) {
        if ($_testip(trim($one),$ip)) return true;
    }
    return false;
}

/**
 * Test whether the string provided is (potentially) a valid email address.
 * NOTE: this test is more tolerant than for SMTP-transferred emails
 * @see RFC5321
 *
 * @param mixed string | null $email
 * @param bool $checkDNS Optional flag, whether to check (if possible) the address-domain. Default false
 * @return mixed string (trim()'d $email) | false
 */
function is_email ($email, bool $checkDNS = false)
{
    //PHP's FILTER_VALIDATE_EMAIL mechanism is not entirely reliable - see notes at https://www.php.net/manual/en/function.filter-var.php
    $email = trim('' . $email);
    if (!preg_match('/\S+.*@[\w.\-\x80-\xff]+$/', $email)) return false;
    if ($checkDNS && function_exists('checkdnsrr')) {
        list($user,$domain) = explode('@',$email,2);
        if (!(checkdnsrr($domain, 'A') || checkdnsrr($domain, 'MX'))) return false; // Domain doesn't actually exist
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
    if (is_bool($val)) return $val;
    if (is_numeric($val)) return $val + 0 != 0;

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
 * @ignore
 * @since 2.9
 */
function register_endsession_function(callable $handler)
{
    //TODO store $handler in $_SESSION[]
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
 * Create an almost-certainly-unique identifier.
 *
 * @since 2.9
 * @return string 32 random hexits
 */
function cms_create_guid() : string
{
//  return chr(mt_rand(97, 122)) . base_convert(bin2hex(random_bytes(20)), 16, 36); //32 alphanum bytes starting with letter
    return bin2hex(random_bytes(16));
}

/**
 * Sort array of strings which include, or may do so, non-ASCII-encoded char(s)
 * @param array $arr data to be sorted
 * @param bool $preserve Optional flag whether to preserve key-value associations during the sort Default false
 * @since 2.9
 * @return sorted array
*/
function cms_utf8_sort(array $arr, bool $preserve = false) : array
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
