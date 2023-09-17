<?php
/*
FileManager module utilities class
Copyright (C) 2006-2018 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/
namespace FileManager;

use CMSMS\AppParams;
use CMSMS\FileTypeHelper;
use CMSMS\FolderControlOperations;
use CMSMS\Lone;
use CMSMS\UserParams;
use CMSMS\Utils as AppUtils;
use Exception;
use FilePicker\Utils as PickerUtils;
use finfo;
use const CMS_ROOT_PATH;
use const CMSSAN_PATH;
use function cms_join_path;
use function cms_relative_path;
use function CMSMS\sanitizeVal;
//use function endswith;
use function startswith;

final class Utils
{
    // static properties here >> Lone property|ies ?
    private static $_can_do_advanced = -1;

    private function __construct() {}

    private function __clone(): void {}

    public static function is_valid_dirname(string $filename): bool
    {
        $tmp = sanitizeVal($filename, CMSSAN_PATH);
        if( $tmp !== $filename ) return FALSE;
        if( ($p = strpos($filename,'..')) !== FALSE && (($c = $filename[$p+2]) == '/' || $c == '\\') ) return FALSE;
        $cset = FolderControlOperations::get_profile_for(dirname($filename));
        return FolderControlOperations::is_file_name_acceptable($cset, $filename);
    }

    /**
     * Check whether $filename may be used
     * @param string $filename filesystem path
     * @return bool
     */
    public static function is_valid_filename(string $filename): bool
    {
        $tmp = sanitizeVal($filename, CMSSAN_PATH);
        if( $tmp !== $filename ) return FALSE;
        if( ($p = strpos($filename,'..')) !== FALSE && (($c = $filename[$p+2]) == '/' || $c == '\\') ) return FALSE;
//      $helper = new FileTypeHelper();
//      $cleaned = $helper->clean_filepath($filename); //reconcile against 'known' extensions
        $cset = FolderControlOperations::get_profile_for(dirname($filename));
        if( !FolderControlOperations::is_file_name_acceptable($cset, $filename) ) return FALSE;

        $name = basename($filename);
        if( $name === '' ) return FALSE;
        // no browser-executable files TODO not a name-specific check
        $helper = new FileTypeHelper();
        return !$helper->is_executable($name);
    }

    public static function can_do_advanced(): bool
    {
        if (self::$_can_do_advanced < 0) {
            $filemod = AppUtils::get_module('FileManager');
            $config = Lone::get('Config');
            if (startswith($config['uploads_path'], CMS_ROOT_PATH) && $filemod->AdvancedAccessAllowed()) {
                self::$_can_do_advanced = 1;
            } else {
                self::$_can_do_advanced = 0;
            }
        }
        return self::$_can_do_advanced;
    }

    public static function check_advanced_mode(): bool
    {
        $filemod = AppUtils::get_module('FileManager');
        $a = self::can_do_advanced();
        $b = $filemod->GetPreference('advancedmode', 0);
        return ($a && $b);
    }

    public static function get_default_cwd(): string
    {
        $advancedmode = self::check_advanced_mode();
        if ($advancedmode) {
            $dir = CMS_ROOT_PATH;
        } else {
            $dir = Lone::get('Config')['uploads_path'];
            if (!startswith($dir, CMS_ROOT_PATH)) {
                $dir = cms_join_path(CMS_ROOT_PATH, 'uploads');
            }
        }

        $dir = cms_relative_path($dir, CMS_ROOT_PATH);
        return $dir;
    }

    public static function test_valid_path(string $path): bool
    {
        // returns false if invalid.
        $config = Lone::get('Config');
        $advancedmode = self::check_advanced_mode();

        $prefix = CMS_ROOT_PATH;
        if ($path === '/') {
            $path = '';
        }
        $path = cms_join_path($prefix, $path);
        $rpath = realpath($path);
        if ($rpath === false) {
            return false;
        }

        if (!$advancedmode) {
            // uploading in 'non advanced mode', path has to start with the upload dir.
            $uprp = realpath($config['uploads_path']);
            if (startswith($rpath, $uprp)) {
                return true;
            }
        } else {
            // advanced mode, path has to start with the root path.
            $rprp = realpath(CMS_ROOT_PATH);
            if (startswith($path, $rprp)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string A relative path BUT with leading DIRECTORY_SEPARATOR!
     */
    public static function get_cwd(): string
    {
        // check the path
        $path = UserParams::get('filemanager_cwd', self::get_default_cwd());
        if (!self::test_valid_path($path)) {
            $path = self::get_default_cwd();
        }
        if ($path == '') {
            $path = '/';
        }
        return $path;
    }

    //$path may be ''
    public static function set_cwd(string $path): void
    {
        if (startswith($path, CMS_ROOT_PATH)) {
            $path = cms_relative_path($path, CMS_ROOT_PATH);
        }
        $advancedmode = self::check_advanced_mode();

        // validate the path.
        $tmp = cms_join_path(CMS_ROOT_PATH, $path);
        $tmp = realpath($tmp);
        if (!$tmp || !is_dir($tmp)) {
            throw new Exception('Cannot set current working directory to an invalid path');
        }
        $newpath = cms_relative_path($tmp, CMS_ROOT_PATH);
        if (!self::test_valid_path($newpath)) {
            throw new Exception('Cannot set current working directory to an invalid path');
        }
        $newpath = str_replace('\\', '/', $newpath);
        UserParams::set('filemanager_cwd', $newpath);
    }

    /**
     * @deprecated since 1.7 use cms_join_path();
     */
    public static function join_path(...$args): string
    {
        return cms_join_path($args);
    }

    public static function get_full_cwd(): string
    {
        $path = self::get_cwd();
        if (!self::test_valid_path($path)) {
            $path = self::get_default_cwd();
        }
        return cms_join_path(CMS_ROOT_PATH, $path);
    }

    public static function get_cwd_url(): string
    {
        $path = self::get_cwd();
        if (!self::test_valid_path($path)) {
            $path = self::get_default_cwd();
        }
        $url = Lone::get('Config')['root_url'].'/'. str_replace('\\', '/', $path);
        return $url;
    }

    public static function is_image_file(string $file): bool
    {
        $helper = new FileTypeHelper();
        return $helper->is_image($file);
/*      // it'd be nice to check mime type here.
        $ext = substr(strrchr($file, '.'), 1);
        if (!$ext) {
            return false;
        }

        $tmp = ['gif', 'jpg', 'jpeg', 'png'];
        if (in_array(strtolower($ext), $tmp)) {
            return true;
        }
        return false;
*/
    }

    public static function is_archive_file(string $file): bool
    {
        $helper = new FileTypeHelper();
        return $helper->is_archive($file);
/*      $tmp = ['.tar.gz', '.tar.bz2', '.zip', '.tgz'];
        foreach ($tmp as $t2) {
            if (endswith(strtolower($file), $t2)) {
                return true;
            }
        }
        return false;
*/
    }

    /**
     * @deprecated since 1.7 use FilePicker\Utils::get_file_list() instead
     */
    public static function get_file_list($path = '')
    {
        $pickmod = AppUtils::get_module('FilePicker');
        return PickerUtils::get_file_list($pickmod, null, $path);
    }

    /**
     * @since 1.7
     */
    public static function get_file_details(array $data): string
    {
        if (!empty($data['image'])) {
            $imginfo = @getimagesize($data['fullpath']);
            if ($imginfo) {
                $t = $imginfo[0].' x '.$imginfo[1];
                if (isset($imginfo['bits'])) {
                    $t .= ' x '.$imginfo['bits'];
                }
                return $t;
            }
        }
        return '';
    }

    public static function mime_content_type(string $filename): string
    {
        if (class_exists('finfo')) {
            $finfo = new finfo(FILEINFO_MIME);
            if ($finfo) {
                $mime_type = finfo_file($finfo, $filename);
                return $mime_type;
            }
        }
        // Revert to check some file-extensions (c.f. FileTypeHelper class)
        $mime_types = [
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
/* TODO UnifiedArchive class can handle
'7z'
'arj'
'bz2','tar.bz2'
'cab'
'deb'
'dmg'
'efi'
'gpt'
'gz','tar.gz'
'iso'
'jar'
'mbr'
'msi'
'rar'
'rpm'
'tar'
'tar.z'
'tbz2'
'tgz'
'txz'
'udf'
'xz','tar.xz'
'zip'
*/
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',

            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        ];

        $ext = explode('.', $filename);
        $ext = strtolower(end($ext));
        return $mime_types[$ext] ?? ''; // empty instead of "application/octet-stream"
    }

    // get post max size and give a portion of it to smarty for max chunk size.
    public static function str_to_bytes($val): int
    {
        if (is_string($val) && $val) {
            $val = trim($val);
            $last = strtolower($val[strlen($val) - 1]);
            if ($last < '<' || $last > 9) {
                $val = substr($val, 0, -1);
            }
            $val = (int) $val;
            switch ($last) {
            case 'g':
                $val *= 1024;
                // no break
            case 'm':
                $val *= 1024;
                // no break
            case 'k':
                $val *= 1024;
            }
        }

        return (int) $val;
    }

    public static function get_dirlist(): array
    {
        $config = Lone::get('Config');
        $mod = AppUtils::get_module('FileManager');
//        $showhiddenfiles = $mod->GetPreference('showhiddenfiles');
        $advancedmode = self::check_advanced_mode();
        if ($advancedmode) {
            $startdir = CMS_ROOT_PATH;
        } else {
            $startdir = $config['uploads_path'];
        }

        // now get a simple list of all of the directories we have 'write' access to.
        $output = self::get_dirs($startdir, DIRECTORY_SEPARATOR);
        if ($output) {
            ksort($output);
            $tmp = [];
            if ($advancedmode) {
                $tmp[DIRECTORY_SEPARATOR] = DIRECTORY_SEPARATOR.basename($startdir).' ('.$mod->Lang('site_root').')';
            } else {
                $tmp[DIRECTORY_SEPARATOR] = DIRECTORY_SEPARATOR.basename($startdir).' ('.$mod->Lang('top').')';
            }
            $output = array_merge($tmp, $output);
        }
        return $output;
    }

    public static function create_thumbnail(string $src, string $dest = ''): bool
    {
        if (!file_exists($src) || is_dir($src)) {
            return false;
        }
        if ($dest) {
            $dn = dirname($dest);
            if (!is_dir($dn)) {
                @mkdir($dn, 0771, true);
            }
        } else {
            $bn = basename($src);
            $config = Lone::get('Config');
            $dest = $config['image_uploads_path'];
            $dn = AppParams::get('content_thumbnailfield_path');
            if ($dn) {
                $dest = cms_join_path($dest, $dn);
            }
            if (!is_dir($dest)) {
                @mkdir($dest, 0771, true);
            }
            $dest .= DIRECTORY_SEPARATOR.'thumb_'.$bn;
        }
        if (file_exists($dest)) {
            if (!is_writable($dest) || is_dir($dest)) {
                return false;
            }
        }

        //TODO also support svg images
        $info = getimagesize($src);
        if (!$info || !isset($info['mime'])) {
            return false;
        }

        $i_src = imagecreatefromstring(file_get_contents($src));
        $color = imagecolorallocatealpha($i_src, 255, 255, 255, 127);
        $width = AppParams::get('thumbnail_width', 96);
        $height = AppParams::get('thumbnail_height', 96);
        $i_dest = imagecreatetruecolor($width, $height);
        imagealphablending($i_dest, false);
        imagecolortransparent($i_dest, $color);
        imagefill($i_dest, 0, 0, $color);
        imagesavealpha($i_dest, true);
        imagecopyresampled($i_dest, $i_src, 0, 0, 0, 0, $width, $height, imagesx($i_src), imagesy($i_src));

        switch ($info['mime']) {
        case 'image/gif':
            return imagegif($i_dest, $dest);
        case 'image/png':
            return imagepng($i_dest, $dest, 9);
        case 'image/jpeg':
            return imagejpeg($i_dest, $dest, 100);
        }
        return false;
    }

    public static function format_filesize(/*mixed */$_size): array
    {
        $mod = AppUtils::get_module('FileManager');
        $unit = $mod->Lang('bytes');
        $size = $_size;

        if ($size > 10000 && $size < 1048576) { //1024*1024
            $size = round($size / 1024);
            $unit = $mod->Lang('kb');
        }

        if ($size > 1048576) {
            $size = round($size / 1048576, 1);
            $unit = $mod->Lang('mb');
        }

        $lcc = localeconv();
        $size = number_format($size, 0, $lcc['decimal_point'], $lcc['thousands_sep']);

        $result = [];
        $result['size'] = $size;
        $result['unit'] = $unit;
        return $result;
    }

    public static function format_permissions(int $mode, string $style = 'xxx')
    {
        switch ($style) {
        case 'xxx':
            $owner = 0;
            if ($mode & 0400) {
                $owner += 4;
            }
            if ($mode & 0200) {
                $owner += 2;
            }
            if ($mode & 0100) {
                ++$owner;
            }
            $group = 0;
            if ($mode & 0040) {
                $group += 4;
            }
            if ($mode & 0020) {
                $group += 2;
            }
            if ($mode & 0010) {
                ++$group;
            }
            $others = 0;
            if ($mode & 0004) {
                $others += 4;
            }
            if ($mode & 0002) {
                $others += 2;
            }
            if ($mode & 0001) {
                ++$others;
            }
            return $owner.$group.$others;

        case 'xxxxxxxxx':
            $owner = '';
            if ($mode & 0400) {
                $owner .= 'r';
            } else {
                $owner .= '-';
            }
            if ($mode & 0200) {
                $owner .= 'w';
            } else {
                $owner .= '-';
            }
            if ($mode & 0100) {
                $owner .= 'x';
            } else {
                $owner .= '-';
            }
            $group = '';
            if ($mode & 0040) {
                $group .= 'r';
            } else {
                $group .= '-';
            }
            if ($mode & 0020) {
                $group .= 'w';
            } else {
                $group .= '-';
            }
            if ($mode & 0010) {
                $group .= 'x';
            } else {
                $group .= '-';
            }
            $others = '';
            if ($mode & 0004) {
                $others .= 'r';
            } else {
                $others .= '-';
            }
            if ($mode & 0002) {
                $others .= 'w';
            } else {
                $others .= '-';
            }
            if ($mode & 0001) {
                $others .= 'x';
            } else {
                $others .= '-';
            }
            return $owner.$group.$others;
        }
    }

    /**
     * Autoloader for archive-processor classes
     * @since 1.7.0
     * @param string $classname
     */
    public static function ArchAutoloader(string $classname)
    {
        $p = strpos($classname, 'wapmorgan\UnifiedArchive\\');
        if ($p === 0 || ($p == 1 && $classname[0] == '\\')) {
            $parts = explode('\\', $classname);
            if ($p == 1) {
                unset($parts[0]);
            }
            unset($parts[$p], $parts[$p + 1]);
            $fp = cms_join_path(__DIR__, 'UnifiedArchive', ...$parts) . '.php';
            if (is_readable($fp)) {
                include_once $fp;
            }
        }
    }

    private static function get_dirs(string $startdir, string $prefix = DIRECTORY_SEPARATOR): array
    {
        if (!is_dir($startdir)) {
            return [];
        }

        $res = [];
        $dh = @opendir($startdir);
        if ($dh) {
            global $showhiddenfiles;
            while (($entry = readdir($dh)) !== false) {
                if ($entry == '.') {
                    continue;
                } //CHECKME keep '..' entry?
                $full = cms_join_path($startdir, $entry);
                if (!is_dir($full)) {
                    continue;
                }
                if (!$showhiddenfiles && ($entry[0] == '.' || $entry[0] == '_')) {
                    continue;
                }

                if ($entry == '.svn' || $entry == '.git') {
                    continue;
                }
                $res[$prefix.$entry] = $prefix.$entry;
                $tmp = self::get_dirs($full, $prefix.$entry.DIRECTORY_SEPARATOR);
                if ($tmp) {
                    $res = array_merge($res, $tmp);
                }
            }
            closedir($dh);
        }
        return $res;
    }
} // class
