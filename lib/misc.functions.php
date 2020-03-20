<?php
#utility-methods available for every request
#Copyright (C) 2004-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
#Thanks to Ted Kulp and all other contributors from the CMSMS Development Team.
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\AppState;
use CMSMS\NlsOperations;

/**
 * Miscellaneous support functions
 *
 * @package CMS
 * @license GPL
 */

/**
 * Redirect to a relative URL on the current site.
 *
 * If headers have not been sent this method will use header based redirection.
 * Otherwise javascript redirection will be used.
 *
 * @author http://www.edoceo.com/
 * @since 0.1
 * @package CMS
 * @param string $to The url to redirect to
 */
function redirect(string $to)
{
    $app = CmsApp::get_instance();
    if ($app->is_cli()) die("ERROR: no redirect on cli based scripts ---\n");

    $_SERVER['PHP_SELF'] = null;
    //TODO generally support the websocket protocol
    $schema = ($app->is_https_request()) ? 'https' : 'http';

    $host = $_SERVER['HTTP_HOST'];
    $components = parse_url($to);
    if (count($components) > 0) {
        $to = (isset($components['scheme']) && startswith($components['scheme'], 'http') ? $components['scheme'] : $schema) . '://';
        $to .= $components['host'] ?? $host;
        $to .= isset($components['port']) ? ':' . $components['port'] : '';
        if (isset($components['path'])) {
            //support admin sub-domains
            $l = strpos($components['path'], '.php', 1);
            if ($l > 0 && substr_count($components['path'],'.', 1, $l-1) > 0) {
                $components['path'] = strtr(substr($components['path'], 0, $l), '.', '/') . substr($components['path'], $l);
            }
            if (in_array($components['path'][0],['\\','/'])) {
                //path is absolute, just append
                $to .= $components['path'];
            }
            //path is relative, append current directory first
            elseif (isset($_SERVER['PHP_SELF']) && !is_null($_SERVER['PHP_SELF'])) { //Apache
                $to .= (strlen(dirname($_SERVER['PHP_SELF'])) > 1 ?  dirname($_SERVER['PHP_SELF']).'/' : '/') . $components['path'];
            }
            elseif (isset($_SERVER['REQUEST_URI']) && !is_null($_SERVER['REQUEST_URI'])) { //Lighttpd
                if (endswith($_SERVER['REQUEST_URI'], '/')) {
                    $to .= (strlen($_SERVER['REQUEST_URI']) > 1 ? $_SERVER['REQUEST_URI'] : '/') . $components['path'];
                }
                else {
                    $dn = dirname($_SERVER['REQUEST_URI']);
                    if (!endswith($dn,'/')) $dn .= '/';
                    $to .= $dn . $components['path'];
                }
            }
        }
        $to .= isset($components['query']) ? '?' . $components['query'] : '';
        $to .= isset($components['fragment']) ? '#' . $components['fragment'] : '';
    }
    else {
        $to = $schema.'://'.$host.'/'.$to;
    }

    session_write_close();


    if (!AppState::test_state(AppState::STATE_INSTALL)) {
        $debug = constant('CMS_DEBUG');
    }
    else {
        $debug = false;
    }

    if (!$debug && headers_sent()) {
        // use javascript instead
        echo '<script type="text/javascript">
<!-- location.replace("'.$to.'"); // -->
</script>
<noscript>
<meta http-equiv="Refresh" content="0;URL='.$to.'">
</noscript>';
    }
    elseif ($debug) {
        echo 'Debug is on. Redirection is disabled... Please click this link to continue.<br />
<a accesskey="r" href="'.$to.'">'.$to.'</a><br />
<div id="DebugFooter">';
        foreach ($app->get_errors() as $error) {
            echo $error;
        }
        echo '</div> <!-- end DebugFooter -->';
    }
    else {
        header("Location: $to");
    }
    exit;
}

/**
 * Given a page ID or an alias, redirect to it.
 * Retrieves the URL of the specified page, and performs a redirect
 *
 * @param string $alias A page alias.
 */
function redirect_to_alias(string $alias)
{
    $hm = CmsApp::get_instance()->GetHierarchyManager();
    $node = $hm->find_by_tag('alias',$alias);
    if (!$node) {
        // put mention into the admin log
        cms_warning('Core: Attempt to redirect to invalid alias: '.$alias);
        return;
    }
    $contentobj = $node->getContent();
    if (!is_object($contentobj)) {
        cms_warning('Core: Attempt to redirect to invalid alias: '.$alias);
        return;
    }
    $url = $contentobj->GetURL();
    if ( $url ) {
        redirect($url);
    }
}

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
 * Return the url corresponding to a provided site-path
 *
 * @since 2.3
 * @param string $in The input path, absolute or relative
 * @param string $relative_to Optional absolute path which (relative) $in is relative to
 * @return string
 */
function cms_path_to_url(string $in, string $relative_to = '') : string
{
    $in = trim($in);
    if ($relative_to) {
        $in = realpath(cms_join_path($relative_to, $in));
        return str_replace([CMS_ROOT_PATH, DIRECTORY_SEPARATOR], [CMS_ROOT_URL, '/'], $in);
    } elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $in)) {
        // $in is absolute
        $in = realpath($in);
        return str_replace([CMS_ROOT_PATH, DIRECTORY_SEPARATOR], [CMS_ROOT_URL, '/'], $in);
    } else {
        return strtr($in, DIRECTORY_SEPARATOR, '/');
    }
}

/**
 * Return the relative portion of a path
 *
 * @since 2.2
 * @author Robert Campbell
 * @param string $in The input path or file specification
 * @param string $relative_to The optional path to compute relative to.  If not supplied the cmsms root path will be used.
 * @return string The relative portion of the input string.
 */
function cms_relative_path(string $in, string $relative_to = null) : string
{
    $in = realpath(trim($in));
    if (!$relative_to) $relative_to = CMS_ROOT_PATH;
    $to = realpath(trim($relative_to));

    if ($in && $to && startswith($in, $to)) {
        return substr($in, strlen($to));
    }
    return '';
}

/**
 * Get PHP flag corresponding to the configured 'content_language' i.e. the
 * preferred language/syntax for page-content
 *
 * @since 2.3
 * @return PHP flag
 */
function cms_preferred_lang() : int
{
    $config = CmsApp::get_instance()->GetConfig();
    $val = str_toupper($config['content_language']);
    switch ($val) {
        case 'HTML5';
            return ENT_HTML5;
        case 'HTML':
            return ENT_HTML401; //a.k.a. 0
        case 'NONE':
            return 0;
        default:
            return ENT_XHTML;
    }
}

static $deflang = 0;
static $defenc = '';

/**
 * Perform HTML entity conversion on a string.
 *
 * @see htmlentities
 *
 * @param mixed  $val     The input string, or maybe null
 * @param int    $param   Optional flag(s) indicating how htmlentities() should handle quotes etc. Default 0, hence ENT_QUOTES | cms_preferred_lang().
 * @param string $charset Optional character set of $val. Default 'UTF-8'. If empty the system setting will be used.
 * @param bool   $convert_single_quotes Optional flag indicating whether single quotes should be converted to entities. Default false.
 *
 * @return string the converted string
 */
function cms_htmlentities($val, int $param = 0, string $charset = 'UTF-8', bool $convert_single_quotes = false) : string
{
    if ($val === '' || $val === null) {
        return '';
    }

    global $deflang, $defenc;

    if ($param === 0) {
        $param = ($convert_single_quotes) ? ENT_QUOTES : ENT_COMPAT;
    }
    if ($param & (ENT_HTML5 | ENT_XHTML | ENT_HTML401) == 0) {
        if ($deflang === 0) {
            $deflang = cms_preferred_lang();
        }
        $param |= $deflang;
    }
    if ($convert_single_quotes) {
        $param &= ~(ENT_COMPAT | ENT_NOQUOTES);
    }

    if (!$charset) {
        if ($defenc === '') {
            $defenc = NlsOperations::get_encoding();
        }
        $charset = $defenc;
    }
    return htmlentities($val, $param, $charset, false);
}

/**
 * Perform HTML entity conversion on a string.
 *
 * @see html_entity_decode
 *
 * @param string $val     The input string
 * @param int    $param   Optional flag(s) indicating how html_entity_decode() should handle quotes etc. Default 0, hence ENT_QUOTES | cms_preferred_lang().
 * @param string $charset Optional character set of $val. Default 'UTF-8'. If empty the system setting will be used.
 *
 * @return string the converted string
 */
function cms_html_entity_decode(string $val, int $param = 0, string $charset = 'UTF-8') : string
{
    if ($val === '') {
        return '';
    }

    global $deflang, $defenc;

    if ($param === 0) {
        $param = ENT_QUOTES;
    }
    if ($param & (ENT_HTML5 | ENT_XHTML | ENT_HTML401) == 0) {
        if ($deflang === 0) {
            $deflang = cms_preferred_lang();
        }
        $param |= $deflang;
    }

    if (!$charset) {
        if ($defenc === '') {
            $defenc = NlsOperations::get_encoding();
        }
        $charset = $defenc;
    }

    return html_entity_decode($val, $param, $charset);
}

/**
 * Display (echo) stack trace as human-readable lines
 *
 * This method uses echo.
 * @param string $title since 2.3 Optional title for (verbatim) display
 */
function stack_trace(string $title = '')
{
    if ($title) echo $title . "\n";

    $bt = debug_backtrace();
    foreach ($bt as $elem) {
        if ($elem['function'] == 'stack_trace') continue;
        if (isset($elem['file'])) {
            echo $elem['file'].':'.$elem['line'].' - '.$elem['function'].'<br />';
        }
        else {
            echo ' - '.$elem['function'].'<br />';
        }
    }
}

/**
 * Output a backtrace into the generated log file.
 *
 * @see debug_to_log, debug_bt
 * Rolf: Looks like not used
 */
function debug_bt_to_log()
{
    if (CmsApp::get_instance()->config['debug_to_log'] || (function_exists('get_userid') && get_userid(false))) {
        $bt = debug_backtrace();
        $file = $bt[0]['file'];
        $line = $bt[0]['line'];

        $out = ["Backtrace in $file on line $line"];

        $bt = array_reverse($bt);
        foreach($bt as $trace) {
            if ($trace['function'] == 'debug_bt_to_log') continue;

            $file = '';
            $line = '';
            if (isset($trace['file'])) {
                $file = $trace['file'];
                $line = $trace['line'];
            }
            $function = $trace['function'];
            $str = "$function";
            if ($file) $str .= " at $file:$line";
            $out[] = $str;
        }

        $filename = TMP_CACHE_LOCATION . DIRECTORY_SEPARATOR. 'debug.log';
        foreach ($out as $txt) {
            error_log($txt . "\n", 3, $filename);
        }
    }
}

/**
 * Generate a backtrace in a readable format.
 *
 * This function does not return but echoes output.
 */
function debug_bt()
{
    $bt = debug_backtrace();
    $file = $bt[0]['file'];
    $line = $bt[0]['line'];

    echo "\n\n<p><b>Backtrace in $file on line $line</b></p>\n";

    $bt = array_reverse($bt);
    echo "<pre><dl>\n";
    foreach($bt as $trace) {
        $file = $trace['file'];
        $line = $trace['line'];
        $function = $trace['function'];
        $args = implode(',', $trace['args']);
        echo "
        <dt><b>$function</b>($args) </dt>
        <dd>$file on line $line</dd>
        ";
    }
    echo "</dl></pre>\n";
}

/**
* Debug function to display $var nicely in html.
*
* @param mixed $var The data to display
* @param string $title (optional) title for the output.  If null memory information is output.
* @param bool $echo_to_screen (optional) Flag indicating whether the output should be echoed to the screen or returned.
* @param bool $use_html (optional) flag indicating whether html or text should be used in the output.
* @param bool $showtitle (optional) flag indicating whether the title field should be displayed in the output.
* @return string
*/
function debug_display($var, string $title = '', bool $echo_to_screen = true, bool $use_html = true, bool $showtitle = true) : string
{
    global $starttime, $orig_memory;
    if (!$starttime) $starttime = microtime();

    ob_start();

    if ($showtitle) {
        $titleText = microtime_diff($starttime,microtime()) . ' S since request-start';
        if (function_exists('memory_get_usage')) {
            $net = memory_get_usage() - $orig_memory;
            $titleText .= ', memory usage: net '.$net;
        }
        else {
            $net = false;
        }

        $memory_peak = (function_exists('memory_get_peak_usage')?memory_get_peak_usage():'');
        if ($memory_peak) {
            if ($net === false) {
                $titleText .= ', memory usage: peak '.$memory_peak;
            }
            else {
                $titleText .= ', peak '.$memory_peak;
            }
        }

        if ($use_html) {
            echo "<div><b>$titleText</b>\n";
        }
        else {
            echo "$titleText\n";
        }
    }

    if ($title || $var || is_numeric($var)) {
        if ($use_html) echo '<pre>';
        if ($title) echo $title . "\n";
        if (is_array($var)) {
            echo 'Number of elements: ' . count($var) . "\n";
            print_r($var);
        }
        elseif (is_object($var)) {
            print_r($var);
        }
        elseif (is_string($var)) {
            if ($use_html) {
                print_r(htmlentities(str_replace("\t", '  ', $var)));
            }
            else {
                print_r($var);
            }
        }
        elseif (is_bool($var)) {
            echo ($var) ? 'true' : 'false';
        }
        elseif ($var || is_numeric($var)) {
            print_r($var);
        }
        if ($use_html) echo '</pre>';
    }
    if ($use_html) echo "</div>\n";

    $out = ob_get_contents();
    ob_end_clean();

    if ($echo_to_screen) echo $out;
    return $out;
}

/**
 * Display $var nicely only if $config["debug"] is set.
 *
 * @param mixed $var
 * @param string $title
 */
function debug_output($var, string $title='')
{
    $config = cms_config::get_instance();
    if ($config['debug']) debug_display($var, $title, true);
}

/**
 * Debug function to output debug information about a variable in a formatted matter
 * to a debug file.
 *
 * @param mixed $var    data to display
 * @param string $title optional title.
 * @param string $filename optional output filename
 */
function debug_to_log($var, string $title='',string $filename = '')
{
    $config = cms_config::get_instance();
    if ($config['debug_to_log'] || (function_exists('get_userid') && get_userid(false))) {
        if ($filename == '') {
            $filename = TMP_CACHE_LOCATION . '/debug.log';
            $x = (is_file($filename)) ? @filemtime($filename) : time();
            if ($x !== false && $x < (time() - 24 * 3600)) unlink($filename);
        }
        $errlines = explode("\n",debug_display($var, $title, false, false, true));
        foreach ($errlines as $txt) {
            error_log($txt . "\n", 3, $filename);
        }
    }
}

/**
 * Display $var nicely to the CmsApp::get_instance()->errors array if $config['debug'] is set.
 *
 * @param mixed $var
 * @param string $title
 */
function debug_buffer($var, string $title='')
{
    if (constant('CMS_DEBUG')) {
        CmsApp::get_instance()->add_error(debug_display($var, $title, false, true));
    }
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
        }
        else {
            if (is_numeric($default)) {
                if (is_numeric($value)) {
                    $return_value = $value;
                }
            }
            else {
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
        }
        elseif (is_numeric($default)) {
            // default value is a number, we only like $parameters[$key] if it's a number too.
            if (is_numeric($parameters[$key])) $return_value = $parameters[$key] + 0;
        }
        elseif (is_string($default)) {
            $return_value = trim($parameters[$key]);
        }
        elseif (is_array($parameters[$key])) {
            // $parameters[$key] is an array - validate each element.
            $return_value = [];
            foreach ($parameters[$key] as $element) {
                $return_value[] = _get_value_with_default($element, $default);
            }
        }
        else {
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
    if (substr($path, strlen ($path) - 1) != '/') $path .= '/' ;

    if (!is_dir($path)) return false;
    $result = true;
    if ($handle = opendir($path)) {
        while (false !== ($file = readdir($handle))) {
            if ($file == '.' || $file == '..') continue;

            $p = $path.$file;
            if (!@is_writable($p)) return false;

            if (@is_dir($p)) {
                $result = is_directory_writable($p);
                if (!$result) return false;
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
 * @since 2.3, reported directories do not have a trailing separator
 * @param  string  $path     start path
 * @param  array   $excludes Optional array of regular expressions indicating files to exclude. Default []
 *  '.' and '..' are automatically excluded.
 * @param  int     $maxdepth Optional max. depth to browse (-1=unlimited). Default -1
 * @param  string  $mode     Optional "FULL"|"DIRS"|"FILES". Default "FULL"
 * @return array
**/
function get_recursive_file_list(string $path, array $excludes = [], int $maxdepth = -1, string $mode = 'FULL') : array
{
    $fn = function (string $name, array $excludes) : bool
    {
        foreach ($excludes as $excl) {
            if (@preg_match('/'.$excl.'/i', $name)) return true;
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
            if (!($excludes && $fn($name, $excludes))) {
                if ($iter->getInnerIterator()->isDir()) {
                    if ($mode != 'FILES') $results[] = $p;
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
 * @return bool indicating complete success
 */
function recursive_delete(string $path) : bool
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
        if ($res) {
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
    if ($tolower) $alias = mb_strtolower($alias); //TODO if mb_string N/A?

    // remove invalid chars
    $expr = '/[^\p{L}_\-\.\ \d]/u';
    if ($withslash) $expr = '/[^\p{L}_\.\-\ \d\/]/u';
    $tmp = trim(preg_replace($expr,'',$alias));

    // remove extra dashes and spaces.
    $tmp = str_replace(' ','-',$tmp);
    $tmp = str_replace('---','-',$tmp);
    $tmp = str_replace('--','-',$tmp);

    return trim($tmp);
}

/**
 * Get an URL query-string corresponding to the supplied value, which is
 * probably non-scalar.
 * This allows (among other things) generation of URL content that
 * replicates parameter arrays like $_POST'd parameter values, for
 * passing around and re-use without [de]serialization.
 * It behaves better than PHP http_build_query(), but only interprets 1-D arrays.
 * @since 2.3
 *
 * @param string $key parameter name/key
 * @param mixed  $val Generally an array, but may be some other non-scalar or a scalar
 * @param string $sep Optional array-item-separator. Default '$amp;'
 * @param bool   $encode  Optional flag whether to rawurlenccode the output. Default true.
 * @return string (No leading $sep for arrays)
 */
function cms_build_query(string $key, $val, string $sep = '&amp;', $encode = true) : string
{
    $multi = false;
    $eq = ($encode) ? '~~~' : '=';
    $sp = ($encode) ? '___' : $sep;
    if (is_array($val)) {
        $out = '';
        $first = true;
        foreach ($val as $k => $v) {
            if ($first) {
                $out .= $key.'['.$k.']'.$eq;
                $first = false;
            } else {
                $out .= $sp.$key.'['.$k.']'.$eq;
                $multi = true;
            }
            if (!is_scalar($v)) {
                try {
                    $v = json_encode($v);
                } catch (Throwable $t) {
                    $v = 'UNKNOWNOBJECT';
                }
            }
            $out .= $v;
        }
    } elseif (!is_scalar($val)) {
        try {
            $val = json_encode($val);
        } catch (Throwable $t) {
            $val = 'UNKNOWNOBJECT';
        }
        $out = $key.$eq.$val;
    } else { //just in case, also handle scalars
        $out = $key.$eq.$val;
    }

    if ($encode) {
        $out = str_replace($eq, '=', rawurlencode($out));
        if ($multi) {
            $out = str_replace($sp, $sep, $out);
        }
    }
    return $out;
}

/**
 * Return the secure param query-string used in all admin links.
 *
 * @internal
 * @access private
 * @return string
 */
function get_secure_param() : string
{
    $out = '?';
    if (!ini_get_boolean('session.use_cookies')) {
        //PHP constant SID is unreliable, we recreate it
        $out .= rawurlencode(session_name()).'='.rawurlencode(session_id()).'&amp;';
    }
    $out .= CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];
    return $out;
}

/**
 * Return the secure params in a form-friendly format.
 *
 * @internal
 * @access private
 * @return array
 */
function get_secure_param_array() : array
{
    $out = [CMS_SECURE_PARAM_NAME => $_SESSION[CMS_USER_KEY]];
    if (!ini_get_boolean('session.use_cookies')) {
        $out[session_name()] = session_id();
    }
    return $out;
}

/**
 * Get $str scrubbed of most non-alphanumeric chars CHECKME allow [_-.] etc ?
 * @param string $str String to clean
 * @return string
 */
function sanitize(string $str) : string
{
    return preg_replace('/[^[:alnum:]_\-\.]/u', '', $str);
}

/**
 * Sanitize input to prevent against XSS and other nasty stuff.
 * Used (almost entirely) on member(s) of $_POST[] or $_GET[]
 *
 * @internal
 * @param mixed $val input value
 * @return string
 */
function cleanValue($val) : string
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
    if (!is_string($val) || $val === '') {
        return $val;
    }
    return filter_var($val, FILTER_SANITIZE_SPECIAL_CHARS, 0);
}

/**
 * Sanitize input to prevent against XSS and other nasty stuff.
 * Used (almost entirely) on $_SERVER[] and/or $_GET[]
 *
 * @internal
 * @param array $array reference to array of input values
 */
function cleanArray(array &$array)
{
    foreach ($array as &$val) {
        if (is_string($val) && $val !== '') {
            $val = filter_var($val, FILTER_SANITIZE_STRING,
                    FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_BACKTICK);
        }
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
 * A wrapper around move_uploaded_file that attempts to ensure permissions on uploaded
 * files are set correctly.
 *
 * @param string $tmpfile The temporary file specification
 * @param string $destination The destination file specification
 * @return bool.
 */
function cms_move_uploaded_file(string $tmpfile, string $destination) : bool
{
    $config = CmsApp::get_instance()->GetConfig();

    if (!@move_uploaded_file($tmpfile, $destination)) return false;
    @chmod($destination,octdec($config['default_upload_permission']));
    return true;
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
      for ($i=0; $i<4; $i++) {
        if (preg_match("/\[([0-9]+)\-([0-9]+)\]/",$maskocts[$i],$regs)) {
          if (($ipocts[$i] > $regs[2]) || ($ipocts[$i] < $regs[1])) $result = 0;
        }
        elseif ($maskocts[$i] <> $ipocts[$i]) $result = 0;
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
 * Test whether the string provided is a valid email address.
 *
 * @param string  $email
 * @param bool $checkDNS
 * @return bool
 */
function is_email (string $email, bool $checkDNS=false)
{
    if (!filter_var($email,FILTER_VALIDATE_EMAIL)) return false;
    if ($checkDNS && function_exists('checkdnsrr')) {
        list($user,$domain) = explode('@',$email,2);
        if (!$domain) return false;
        if (!(checkdnsrr($domain, 'A') || checkdnsrr($domain, 'MX'))) return false; // Domain doesn't actually exist
    }

   return true;
}

/**
 * Return a UNIX UTC timestamp corresponding to the supplied (typically
 * database datetime formatted and timezoned) date/time string.
 * The supplied parameter is not validated, apart from ignoring a falsy value.
 * @since 2.3
 *
 * @param mixed $datetime normally a string reported by a query on a database datetime field
 * @param bool  $is_utc Optional flag whether $datetime is for the UTC timezone. Default false.
 * @return int Default 1 (not false)
 */
function cms_to_stamp($datetime, bool $is_utc = false) : int
{
    static $dt = null;
    static $offs = null;

    if ($datetime) {
        if ($dt === null) {
            $dt = new DateTime('@0', null);
        }
        if (!$is_utc) {
            if ($offs === null) {
                $config = cms_config::get_instance();
                $dtz = new DateTimeZone($config['timezone']);
                $offs = timezone_offset_get($dtz, $dt);
            }
        }
        try {
            $dt->modify($datetime);
            if (!$is_utc) {
                return $dt->getTimestamp() - $offs;
            }
            return $dt->getTimestamp();
        } catch (Throwable $t) {
            // nothing here
        }
    }
    return 1; // anything not falsy
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
    if (is_numeric($val)) return (int)$val !== 0;

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
 * Identify the, or the highest-versioned, installed jquery scripts and/or css
 * @since 2.3
 * @return array of filepaths, keys per params: 'jqcore','jqmigrate','jqui','jquicss'
 */
function cms_installed_jquery(bool $core = true, bool $migrate = false, bool $ui = true, bool $uicss = true) : array
{
    $found = [];
    $allfiles = false;

    if ($core) {
        $fp = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery';
        $allfiles = scandir($fp);
        //the 'core' jquery files are named like jquery-*min.js
        $m = preg_grep('~^jquery\-\d[\d\.]+\d(\.min)?\.js$~', $allfiles);
        //find highest version
        $best = '0';
        $use = reset($m);
        foreach ($m as $file) {
            preg_match('~(\d[\d\.]+\d)~', $file, $matches);
            if (version_compare($best, $matches[1]) < 0) {
                $best = $matches[1];
                $use = $file;
            }
        }
        $found['jqcore'] = $fp.DIRECTORY_SEPARATOR.$use;
    }

    if ($migrate) {
        if (!$allfiles) {
            $fp = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery';
            $allfiles = scandir($fp);
        }
        $m = preg_grep('~^jquery\-migrate\-\d[\d\.]+\d(\.min)?\.js$~', $allfiles);
        $best = '0';
        $use = reset($m);
        foreach ($m as $file) {
            preg_match('~(\d[\d\.]+\d)~', $file, $matches);
            if (version_compare($best, $matches[1]) < 0) {
                $best = $matches[1];
                $use = $file;
            }
        }
        $found['jqmigrate'] = $fp.DIRECTORY_SEPARATOR.$use;
    }

    $allfiles = false;

    if ($ui) {
        $fp = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui';
        $allfiles = scandir($fp);
        $m = preg_grep('~^jquery\-ui\-\d[\d\.]+\d([\.\-]custom)?(\.min)?\.js$~', $allfiles);
        $best = '0';
        $use = reset($m);
        foreach ($m as $file) {
            preg_match('~(\d[\d\.]+\d)~', $file, $matches);
            if (version_compare($best, $matches[1]) < 0) {
                $best = $matches[1];
                $use = $file;
            }
        }
        $found['jqui'] = $fp.DIRECTORY_SEPARATOR.$use;
    }

    if ($uicss) {
        if (!$allfiles) {
            $fp = CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui';
            $allfiles = scandir($fp);
        }
        $m = preg_grep('~^jquery\-ui\-\d[\d\.]+\d([\.\-]custom)?(\.min)?\.css$~', $allfiles);
        $best = '0';
        $use = reset($m);
        foreach ($m as $file) {
            preg_match('~(\d[\d\.]+\d)~', $file, $matches);
            if (version_compare($best, $matches[1]) < 0) {
                $best = $matches[1];
                $use = $file;
            }
        }
        $found['jquicss'] = $fp.DIRECTORY_SEPARATOR.$use;
    }

    return $found;
}

/**
 * Return content which will include wanted js (jQuery etc) and css in a
 * displayed page.
 * @since 1.10
 * @deprecated since 2.3
 * Instead, relevant content can be gathered via functions added to hook
 * 'AdminHeaderSetup' and/or 'AdminBottomSetup', or a corresponding tag
 *  e.g. {gather_content list='AdminHeaderSetup'}.
 * See also the ScriptOperations class, for consolidating scripts into a single
 * download.
 */
function cms_get_jquery(string $exclude = '',bool $ssl = false,bool $cdn = false,string $append = '',string $custom_root = '',bool $include_css = true)
{
    $incs = cms_installed_jquery(true, false, true, $include_css);
    if ($include_css) {
        $url1 = cms_path_to_url($incs['jquicss']);
        $s1 = <<<EOS
<link rel="stylesheet" type="text/css" href="{$url1}" />

EOS;
    } else {
        $s1 = '';
    }
    $url2 = cms_path_to_url($incs['jqcore']);
    $url3 = cms_path_to_url($incs['jqui']);
    $out = <<<EOS
<!-- default page inclusions -->{$s1}
<script type="text/javascript" src="{$url2}"></script>
<script type="text/javascript" src="{$url3}"></script>

EOS;
    return $out;
}

/**
 * @since 2.3
 * @ignore
 */
function get_best_file($places, $target, $ext, $as_url)
{
    if (($p = stripos($target, 'min')) !== false) {
        $base = substr($target, 0, $p-1); //strip [.-]min & type-suffix
    } elseif (($p = stripos($target, '.'.$ext)) !== false) {
        $base = substr($target, 0, $p); //strip type-suffix
    }
    $base = strtr($base, ['.'=>'\\.', '-'=>'\\-']);

    $patn = '~^'.$base.'([.-](\d[\d\.]*))?([.-]min)?\.'.$ext.'$~i';
    foreach ($places as $base_path) {
        $allfiles = scandir($base_path);
        if ($allfiles) {
            $files = preg_grep($patn, $allfiles);
            if ($files) {
                if (count($files) > 1) {
//                    $best = ''
                    foreach ($files as $target) {
                        preg_match($patn, $target, $matches);
                        if (!empty($matches[2])) {
                            break; //use the min TODO check versions too
                        } elseif (!empty($matches[1])) {
                            //TODO a candidate, but try for later-version/min
                        } else {
                            //TODO a candidate, but try for min
                        }
                    }
//                    $target = $best;
                } else {
                    $target = reset($files);
                }
                $out = $base_path.DIRECTORY_SEPARATOR.$target;
                if ($as_url) {
                    return cms_path_to_url($out);
                }
                return $out;
            }
        }
    }
    return '';
}

/**
 * Return the filepath or URL of a wanted script file, if found in any of the
 * standard locations for such files (or any other provided location).
 * Intended mainly for non-jQuery scripts, but it will try to find those those too.
 * @since 2.3
 *
 * @param string $filename absolute or relative filepath or (base)name of the
 *  wanted file, optionally including [.-]min before the .js extension
 *  If the name includes a version, that will be taken into account.
 *  Otherwise, the first-found version will be used. Min-format preferred over non-min.
 * @param bool $as_url optional flag, whether to return URL or filepath. Default true.
 * @param mixed $custompaths string | string[] optional 'non-standard' directory-path(s) to include (first) in the search
 * @return mixed string absolute filepath | URL | null
 */
function cms_get_script(string $filename, bool $as_url = true, $custompaths = '')
{
    $target = basename($filename);
    if ($target == $filename) {
        $places = [
         CMS_SCRIPTS_PATH,
         CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'js',
         CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'js',
         CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery',
         CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui',
        ];
    } elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $filename)) {
        // $filename is absolute
        $places = [dirname($filename)];
    } else {
        // $filename is relative, try to find it
        //TODO if relevant, support somewhere module-relative
        //TODO partial path-intersection too, any separators
        $config = cms_config::get_instance();
        $base_path = ltrim(dirname($filename),' \\/');
        $places = [
         $base_path,
         CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'js',
         CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'assets',
         $config['uploads_path'],
         CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'js',
         CMS_ROOT_PATH,
        ];
    }

    if ($custompaths) {
        if (is_array($custompaths)) {
            $places = array_merge($custompaths, $places);
        } else {
            array_unshift($places, $custompaths);
        }
        $places = array_unique($places);
    }

    return get_best_file($places, $target, 'js', $as_url);
}

/**
 * Return the filepath or URL of a wanted css file, if found in any of the
 * standard locations for such files (or any other provided location).
 * Intended mainly for non-jQuery styles, but it will try to find those those too.
 * @since 2.3
 *
 * @param string $filename absolute or relative filepath or (base)name of the
 *  wanted file, optionally including [.-]min before the .css extension
 *  If the name includes a version, that will be taken into account.
 *  Otherwise, the first-found version will be used. Min-format preferred over non-min.
 * @param bool $as_url optional flag, whether to return URL or filepath. Default true.
 * @param mixed $custompaths string | string[] optional 'non-standard' directory-path(s) to include (first) in the search
 * @return mixed string absolute filepath | URL | null
 */
function cms_get_css(string $filename, bool $as_url = true, $custompaths = '')
{
    $target = basename($filename);
    if ($target == $filename) {
        $places = [
         CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'css',
         CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css',
         CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery',
         CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery-ui',
        ];
    } elseif (preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $filename)) {
        // $filename is absolute
        $places = [dirname($filename)];
    } else {
        // $filename is relative, try to find it
        //TODO if relevant, support somewhere module-relative
        //TODO partial path-intersection too, any separators
        $config = cms_config::get_instance();
        $base_path = ltrim(dirname($filename),' \\/');
        $places = [
         $base_path,
         CMS_ASSETS_PATH.DIRECTORY_SEPARATOR.'css',
         CMS_ROOT_PATH.DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'assets',
         $config['uploads_path'],
         CMS_ADMIN_PATH.DIRECTORY_SEPARATOR.'themes'.DIRECTORY_SEPARATOR.'assets'.DIRECTORY_SEPARATOR.'css',
         CMS_ROOT_PATH,
        ];
    }

    if ($custompaths) {
        if (is_array($custompaths)) {
            $places = array_merge($custompaths, $places);
        } else {
            array_unshift($places, $custompaths);
        }
        $places = array_unique($places);
    }

    return get_best_file($places, $target, 'css', $as_url);
}

/**
 * @ignore
 * @since 2.0.2
 */
function setup_session(bool $cachable = false)
{
    static $_setup_already = false;

    if ($_setup_already) {
        //TODO maybe session_regenerate_id(), if so, rename cache-group accordingly
        return;
    }

    $_f = $_l = null;
    if (headers_sent($_f, $_l)) throw new LogicException("Attempt to set headers, but headers were already sent at: $_f::$_l");

    if ($cachable) {
        if ($_SERVER['REQUEST_METHOD'] != 'GET' ||
        AppState::test_any_state(AppState::STATE_ADMIN_PAGE | AppState::STATE_INSTALL)) {
            $cachable = false;
        }
    }
    if ($cachable) {
        $cachable = (int) cms_siteprefs::get('allow_browser_cache',0);
    }
    if (!$cachable) {
        // admin pages can't be cached... period, at all.. never.
        @session_cache_limiter('nocache');
    }
    else {
        // frontend request
        $expiry = (int)max(0,cms_siteprefs::get('browser_cache_expiry',60));
        session_cache_expire($expiry);
        session_cache_limiter('public');
        @header_remove('Last-Modified');
    }

    // setup session with different (constant) id and start it
    $session_name = 'CMSSESSID'.cms_utils::hash_string(CMS_ROOT_PATH.CMS_VERSION);
    if (!AppState::test_state(AppState::STATE_INSTALL)) {
        @session_name($session_name);
        @ini_set('url_rewriter.tags', '');
        @ini_set('session.use_trans_sid', 0);
    }

    if (isset($_COOKIE[$session_name])) {
        // validate the content of the cookie
        if (!preg_match('/^[a-zA-Z0-9,\-]{22,40}$/', $_COOKIE[$session_name])) {
            session_id(uniqid());
            session_start();
            session_regenerate_id(); //TODO rename cache-group accordingly
        }
    }
    if (!@session_id()) session_start();

/* TODO session-shutdown function(s) processing, from handler(s) recorded in
    session_set_save_handler(
        callable1, ... callableN
    );
    session_register_shutdown();
*/
    $_setup_already = true;
}

/**
 * @ignore
 * @since 2.3
 */
function register_endsession_function(callable $handler)
{
    //TODO store $handler in $_SESSION[]
}

/**
 * Test whether a string is base64-encoded
 *
 * @since 2.2
 * @param string $s The string to check
 * @return bool
 */
function is_base64(string $s) : bool
{
    return (bool) preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s);
}

/**
 * Create an almost-certainly-unique identifier.
 *
 * @since 2.3
 * @return string
 */
function cms_create_guid() : string
{
    if (function_exists('com_create_guid')) {
        return trim(com_create_guid(), '{}'); //windows
    }
    return random_bytes(32);
}

/**
 * Sort array of strings which include, or may do so, non-ASCII-encoded char(s)
 * @param array $arr data to be sorted
 * @param bool $preserve Optional flag whether to preserve key-value associations during the sort Default false
 * @since 2.3
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

/**
 * Return the permissions (names) which always require explicit authorization
 *  i.e. even for super-admins (user 1 | group 1)
 * @since 2.3
 * @return array
 */
function restricted_cms_permissions() : array
{
    $val = cms_siteprefs::get('ultraroles');
    if ($val) {
        $out = json_decode($val);
        if ($out) {
            if (is_array($out)) {
                return $out;
            }
            if (is_scalar($out)) {
                return [$out];
            }
            return (array)$out;
        }
        //return TODO [defaults] upon error
    }
    return [];
}
