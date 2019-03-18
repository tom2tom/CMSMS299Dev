<?php

namespace cms_installer;

use Exception;
use function cms_installer\endswith;
use function cms_installer\get_app;
use function cms_installer\lang;
use function cms_installer\startswith;

final class utils
{
    private static $_writable_error = [];

    private function __construct() {}
    private function __clone() {}

    /**
     *
     * @param string $to URL
     */
    public static function redirect(string $to)
    {
        $_SERVER['PHP_SELF'] = null;
        //TODO generally support the websocket protocol
        $schema = $_SERVER['SERVER_PORT'] == '443' ? 'https' : 'http';
        $host = strlen($_SERVER['HTTP_HOST'])?$_SERVER['HTTP_HOST']:$_SERVER['SERVER_NAME'];

        $components = parse_url($to);
        if (count($components) > 0) {
            $to =  (isset($components['scheme']) && startswith($components['scheme'], 'http') ? $components['scheme'] : $schema) . '://';
            $to .= isset($components['host']) ? $components['host'] : $host;
            $to .= isset($components['port']) ? ':' . $components['port'] : '';
            if(isset($components['path'])) {
                if(in_array(substr($components['path'],0,1),['\\','/'])) { //Path is absolute, just append.
                    $to .= $components['path'];
                }
                //Path is relative, append current directory first.
                else if (isset($_SERVER['PHP_SELF']) && !is_null($_SERVER['PHP_SELF'])) { //Apache
                    $to .= (strlen(dirname($_SERVER['PHP_SELF'])) > 1 ?  dirname($_SERVER['PHP_SELF']).'/' : '/') . $components['path'];
                }
                else if (isset($_SERVER['REQUEST_URI']) && !is_null($_SERVER['REQUEST_URI'])) { //Lighttpd
                    if (endswith($_SERVER['REQUEST_URI'], '/'))
                        $to .= (strlen($_SERVER['REQUEST_URI']) > 1 ? $_SERVER['REQUEST_URI'] : '/') . $components['path'];
                    else
                        $to .= (strlen(dirname($_SERVER['REQUEST_URI'])) > 1 ? dirname($_SERVER['REQUEST_URI']).'/' : '/') . $components['path'];
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
     *
     * @param mixed $in
     * @param bool $strict Default FALSE
     * @return mixed bool or null
     */
    public static function to_bool($in, bool $strict = FALSE)
    {
        $in = strtolower((string) $in);
        if( in_array($in,['1','y','yes','true','t','on']) ) return TRUE;
        if( in_array($in,['0','n','no','false','f','off']) ) return FALSE;
        if( $strict ) return NULL;
        return $in != FALSE;
    }

    /**
     *
     * @param string $str
     * @return bool
     */
    public static function is_email(string $str) : bool
    {
        return filter_var($str,FILTER_VALIDATE_EMAIL) !== FALSE;
    }

    /**
     *
     * @param mixed $val
     * @return mixed
     */
    // TODO filter_var()
    public static function clean_string($val)
    {
        if( !$val ) return $val;
        $val = (string) $val;
        $val = preg_replace('/\\$/', '$', $val);
        $val = preg_replace("/\r/", '', $val);
        $val = str_replace('!', '!', $val);
        $val = str_replace("'", "'", $val);
        return strip_tags($val);
    }

    /**
     *
     * @return string
     * @throws Exception
     */
    public static function get_sys_tmpdir() : string
    {
        if( function_exists('sys_get_temp_dir') ) {
            $tmp = rtrim(sys_get_temp_dir(),'\\/');
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
     * Check the permissions of a directory recursively to make sure that
     * we have write permission to all files and folders.
     *
     * @param  string  $path Start directory.
     * @param  bool    $ignore_specialfiles  Optionally ignore special system files in the check.  Special files include files beginning with ., and php.ini files.
     * @return bool
     */
    public static function is_directory_writable( string $path, bool $ignore_specialfiles = TRUE ) : bool
    {
        if( substr ( $path, strlen ( $path ) - 1 ) != '/' ) $path .= '/' ;

        $result = TRUE;
        if( $handle = @opendir( $path ) ) {
            while( false !== ( $file = readdir( $handle ) ) ) {
                if( $file == '.' || $file == '..' ) continue;

                // ignore dotfiles, except .htaccess.
                if( $ignore_specialfiles ) {
                    if( $file[0] == '.' && $file != '.htaccess' ) continue;
                    if( $file == 'php.ini' ) continue;
                }

                $p = $path.$file;
                if( !@is_writable( $p ) ) {
                    self::$_writable_error[] = $p;
                    @closedir( $handle );
                    return FALSE;
                }

                if( @is_dir( $p ) ) {
                    $result = self::is_directory_writable( $p, $ignore_specialfiles );
                    if( !$result ) {
                        self::$_writable_error[] = $p;
                        @closedir( $handle );
                        return FALSE;
                    }
                }
            }
            @closedir( $handle );
        }
        else {
            self::$_writable_error[] = $p;
            return FALSE;
        }

        return TRUE;
    }

    /**
     * Recursive delete directory
     *
     * @param string $dir filepath
     */
    public static function rrmdir($dir)
    {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != '.' && $object != '..') {
                    if (filetype($dir.'/'.$object) == 'dir') self::rrmdir($dir.'/'.$object); else unlink($dir.'/'.$object);
                }
            }
            reset($objects);
            if (is_link($dir)) {
                return unlink($dir);
            } elseif (is_dir($dir)) {
                return rmdir($dir);
            }
            return false;
        }
    }

    /**
     *
     * @return array
     */
    public static function get_writable_error() : array
    {
        return self::$_writable_error;
    }

    /**
     * Get list of versions we can upgrade from.
     *
     * @return array
     * @throws Exception
     */
    public static function get_upgrade_versions() : array
    {
        $app = get_app();
        $app_config = $app->get_config();
        $min_upgrade_version = $app_config['min_upgrade_version'];
        if( !$min_upgrade_version ) throw new Exception(lang('error_invalidconfig'));

        $dir = $app->get_assetsdir().'/upgrade';
        if( !is_dir($dir) ) throw new Exception(lang('error_internal','u100'));

        $dh = opendir($dir);
        $versions = [];
        if( !$dh ) throw new Exception(lang('error_internal',712));
        while( ($file = readdir($dh)) !== false ) {
            if( $file == '.' || $file == '..' ) continue;
            if( is_dir($dir.'/'.$file) &&
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
    public static function get_upgrade_changelog(string $version) : string
    {
        $app = get_app();
        $dir = $app->get_assetsdir().'/upgrade/'.$version;
        if( !is_dir($dir) ) throw new Exception(lang('error_internal','u100'));
        $files = ['CHANGELOG.txt','CHANGELOG.TXT','changelog.txt'];
        foreach( $files as $fn ) {
            if( is_file("$dir/$fn") ) {
                // convert text into some sort of html
                $tmp = @file_get_contents("$dir/$fn");
                $tmp = nl2br(wordwrap(htmlspecialchars($tmp),80));
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
    public static function get_upgrade_readme(string $version) : string
    {
        $app = get_app();
        $dir = $app->get_assetsdir().'/upgrade/'.$version;
        if( !is_dir($dir) ) throw new Exception(lang('error_internal','u100'));
        $files = ['README.HTML.INC','readme.html.inc','README.HTML','readme.html'];
        foreach( $files as $fn ) {
            if( is_file("$dir/$fn") ) return @file_get_contents("$dir/$fn");
        }
        if( is_file("$dir/readme.txt") ) {
            // convert text into some sort of html.
            $tmp = @file_get_contents("$dir/readme.txt");
            $tmp = nl2br(wordwrap(htmlspecialchars($tmp),80));
            return $tmp;
        }
        return '';
    }
} // class
