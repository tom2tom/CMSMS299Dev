<?php
/*
Filepicker module: utility-methods class
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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
namespace FilePicker; //the module-class

use CMSMS\AppParams;
use CMSMS\FolderControlOperations;
use CMSMS\FolderControls;
use CMSMS\FileTypeHelper;
use CMSMS\FSControlValue;
use CMSMS\NlsOperations;
use CMSMS\SingleItem;
use CMSMS\Utils as AppUtils;
use Collator;
use FilePicker;
use const CMS_ROOT_PATH;
use const CMSSAN_FILE;
use function cms_join_path;
use function cms_path_to_url;
use function CMSMS\sanitizeVal;
use function startswith;

/**
 * A class of static utility-methods for the FilePicker module.
 * Much of this is more for general file-management than for picking per se
 *
 * @package CMS
 * @license GPL
 * @since  2.0
 */
class Utils
{
    /**
     * @param string $extension file extension, or ''|'up'|'home' for a directory. Any case.
     * @param bool $isdir Optional flag indicating this is a directory. Default false.
     * @return string
     */
    public static function get_file_icon(string $extension, bool $isdir = false) : string
    {
        // static properties here >> SingleItem property|ies ?
        static $mod = null;
        if ($mod == null) {
            $mod = AppUtils::get_module('FilePicker');
        }
        $baseurl = $mod->GetModuleURLPath();
        //TODO $themeObject=;

        if ($isdir) {
            switch ($extension) {
                case 'up':
                    $lcext = 'dir-up';
                    break;
                case 'home':
                    $lcext = 'dir-home';
                    break;
                default:
                    $lcext = 'dir';
                    break;

            }
            //TODO return $themeObject->DisplayImage(fullpath-to-image,'directory','','','listicon',$attrs = []);
            return '<img src="'.$baseurl.'/images/types/'.$lcext.'.png" class="listicon" alt="directory" />';
        }

        if ($extension === '' || $extension === '.') {
            $lcext = $ext = '-'; // hardcode extension to something
        } else {
            if ($extension[0] !== '.') {
                $ext = $extension;
            } else {
                $ext = substr($extension, 1);
            }
            $lcext = strtolower($extension);
        }

        $path = cms_join_path(dirname(__DIR__),'images','types',$lcext.'.png');
        if (!is_file($path)) {
            static $getem = true;
            if ($getem) {
                require_once __DIR__.DIRECTORY_SEPARATOR.'typealias.php';
                $getem = false;
            }
            $lcext = $dups[$lcext] ?? '0';
        }
        //TODO return $themeObject->DisplayImage(fullpath-to-image,$ext.'-file','','','listicon',$attrs = []);
        return '<img src="'.$baseurl.'/images/types/'.$lcext.'.png" class="listicon" alt="'.$ext.'-file" />';
    }

    /**
     * Save a thumbnail if possible
     * @param string $rootpath absolute filesystem path to be prepended to $path if the latter is relative
     * @param string $path absolute or root-relative filesystem-path of original image
     */
    public static function create_file_thumb(string $rootpath, string $path)
    {
        if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $path)) {
            // $path is relative
            if (!$rootpath) { $rootpath = CMS_ROOT_PATH; }
            $path = cms_join_path($rootpath, $path);
        }
        if (!startswith($path, CMS_ROOT_PATH)) {
            return;
        }
        $dirname = dirname($path);
        if (!is_dir($dirname) || !is_writable($dirname)) {
            return;
        }
        if (!function_exists('getimagesize')) {
            return; // no GD extension
        }
        $info = @getimagesize($path);
        if (!$info || $info[0] === 0 || $info[1] === 0) {
            return;
        }
        $width = (int) AppParams::get('thumbnail_width', 96);
        $height = (int) AppParams::get('thumbnail_height', 96);
        if ($width < 2 || $height < 2) {
            return;
        }

        $types = imagetypes(); //IMG_BMP | IMG_GIF | IMG_JPG | IMG_PNG | IMG_WBMP | IMG_XPM | IMG_WEBP (7.0.10)
        $ores = null;
        switch ($info[2]) {
            case IMAGETYPE_GIF:
                if ($types & IMG_GIF) {
                    $ores = imagecreatefromgif($path);
                }
                break;
            case IMAGETYPE_JPEG:
            case IMAGETYPE_JPEG2000:
                if ($types & IMG_JPG) {
                    $ores = imagecreatefromjpeg($path);
                }
                break;
            case IMAGETYPE_PNG:
                if ($types & IMG_PNG) {
                    $ores = imagecreatefrompng($path);
                }
                break;
            case IMAGETYPE_BMP:
            case IMAGETYPE_WBMP:
                if ($types & IMG_BMP) {
                    $ores = imagecreatefrombmp($path);
                }
                break;
            case IMAGETYPE_XBM:
                if (1) { //($types & ) {
                    $ores = imagecreatefromxbm($path);
                }
                break;
            case IMAGETYPE_WEBP:
                if ($types & IMG_WEBP) {
                    $ores = imagecreatefromwebp($path);
                }
                break;
/*          case IMAGETYPE_SWF:
            case IMAGETYPE_PSD:
            case IMAGETYPE_TIFF_II:
            case IMAGETYPE_TIFF_MM:
            case IMAGETYPE_IFF:
            case IMAGETYPE_JB2:
            case IMAGETYPE_JPC:
            case IMAGETYPE_JP2:
            case IMAGETYPE_JPX:
            case IMAGETYPE_SWC:
*/
            default:
                //TODO try to construct suitable image
                $ores = imagecreatefromstring(file_get_contents($path));
        }
        if ($ores) {
            // calc scaled height & width
            $rh = $height / $info[1];
            $rw = $width / $info[0];
            $ru = min($rh, $rw);
            $ih = (int) ($info[1] * $ru);
            $iw = (int) ($info[0] * $ru);
            if ($ih < $info[1] || $iw < $info[0]) {
                $nres = imagescale($ores, $iw, $ih, IMG_BILINEAR_FIXED);
                $fn = $dirname . DIRECTORY_SEPARATOR . 'thumb_' . basename($path);
                imagepng($nres, $fn);
            }
        }
    }

    /**
     * @param FilePicker $mod
     * @param int $mode
     * @param bool $isdir
     * @return string
     */
    public static function format_permissions(FilePicker $mod, int $mode, bool $isdir) : string
    {
        static $pr = null;
        static $pw, $px, $pxf;
        if ($pr == null) {
            $pr = $mod->Lang('perm_r');
            $pw = $mod->Lang('perm_w');
            $px = $mod->Lang('perm_x');
            $pxf = $mod->Lang('perm_xf');
        }
        $perms = [];
        if ($mode & 0x0100) {
            $perms[] = $pr;
        }
        if ($mode & 0x0080) {
            $perms[] = $pw;
        }
        if ($mode & 0x0040) {
            $perms[] = ($isdir) ? $pxf : $px;
        } //ignore static flag
        return implode('+', $perms);
    }

    /* *
     * @ignore
     */
/*   private function file_details(string $filepath, array &$info) : string
    {
        if (!empty($info['image'])) {
            $imginfo = @getimagesize($filepath);
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
*/

    protected static function processpath($dirpath) : string
    {
        $config = SingleItem::Config();
        $devmode = $config['develop_mode'];
        if (!$devmode) {
            $userid = get_userid(false);
            $devmode = check_permission($userid, 'Modify Restricted Files');
        }
        $rootpath = ($devmode) ? CMS_ROOT_PATH : $config['uploads'];

        if (!$dirpath) {
            $dirpath = $rootpath;
        } elseif (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $dirpath)) {
            // $dirpath is relative
            $dirpath = cms_join_path($rootpath, $dirpath);
        } elseif (!startswith($dirpath, CMS_ROOT_PATH)) {
            return '';
        }
        if (is_dir($dirpath)) {
            return $dirpath;
        }
        return '';
    }

    /**
     * Return data for relevant files/sub-folders in folder $dirpath
     * @param mixed $profile Optional FolderControls object | name of one-such | falsy. Default null
     * @param string $dirpath Optional absolute or appropriate-root-relative
     *  filesystem-path of folder to be reported. Default '' (hence use relevant root)
     * @return array (maybe empty)
     */
    public static function get_file_list($profile = null, string $dirpath = '') : array
    {
        $dirpath = self::processpath($dirpath);
        if (!$dirpath) return [];
        // not a huge no. of items in website folders, no need for opendir/readdir/closedir
        $items = scandir($dirpath, SCANDIR_SORT_NONE);
        if (!$items) {
            return [];
        }

        if (!$profile || !($profile instanceof FolderControls)) {
            $profile = FolderControlOperations::get_profile($profile, $dirpath);
        }

        $config = SingleItem::Config();
        $devmode = $config['develop_mode'];
        if (!$devmode) {
            $userid = get_userid(false);
            $devmode = check_permission($userid, 'Modify Restricted Files');
        }
        $showhidden = $profile->show_hidden || $devmode;
        $showthumb = $profile->show_thumbs;
        $pex = $profile->exclude_prefix ?? '';
        $pin = $profile->match_prefix ?? '';

        $helper = new FileTypeHelper();
        $posix = function_exists('posix_getpwuid');
        if (!$posix) {
            $ownerna = $mod->Lang('na');
        }
        $rootpath = ($devmode) ? CMS_ROOT_PATH : $config['uploads'];
        $showup = ($dirpath != $rootpath);

        $result = [];
        for ($name = current($items); $name !== false; $name = next($items)) {
            if ($name == '.') {
                continue;
            }
            if ($name == '..') {
                // can we go up ?
                if (!$showup) {
                    continue;
                }
            } elseif ($name[0] == '.' || $name[0] == '_' || $name[0] == '~') {
                if (!$showhidden) {
                    continue;
                }
            }
            if ($pin !== '' && !startswith($name, $pin)) {
                continue;
            }
            if ($pex !== '' && startswith($name, $pex)) {
                continue;
            }

            $filepath = $dirpath.DIRECTORY_SEPARATOR.$name;
            if (!$showthumb && $helper->is_thumb($filepath)) {
                continue;
            }

            $info = ['fullpath' => $filepath, 'dir' => is_dir($filepath), 'name' => $name];
            if (!$info['dir']) {
                $info['ext'] = $helper->get_extension($name);
                $info['text'] = $helper->is_text($filepath);
                $info['image'] = $helper->is_image($filepath);
                $info['archive'] = !$info['text'] && !$info['image'] && $helper->is_archive($filepath);
                $info['mime'] = $helper->get_mime_type($filepath);
                $info['url'] = cms_path_to_url($filepath);
            }

            $statinfo = stat($filepath);
            $info['mode'] = $statinfo['mode'];
            $info['size'] = $statinfo['size'];
            $info['date'] = $statinfo['mtime']; //timestamp
            if ($posix) {
                $userinfo = @posix_getpwuid($statinfo['uid']);
                $info['fileowner'] = $userinfo['name'] ?? $mod->Lang('unknown');
            } else {
                $info['fileowner'] = $ownerna;
            }
            $info['writable'] = is_writable($filepath);

            $result[] = $info;
        }

        $sortby = $profile->sort;
        if ($sortby !== FSControlValue::NONE) {
            if (class_exists('Collator')) {
                $lang = NlsOperations::get_default_language();
                $col = new Collator($lang); // e.g. new Collator('pl_PL') TODO if.UTF-8 ?? ini 'output_encoding' ??
            } else {
                $col = false;
                // fallback ?? e.g. setlocale() then strcoll()
            }

            usort($result, function ($a, $b) use ($col, $sortby) {
                if ($a['name'] == '..') {
                    return -1;
                }
                if ($b['name'] == '..') {
                    return 1;
                }

                //dirs first
                if ($a['dir'] xor $b['dir']) {
                    // only one is a dir
                    return ($a['dir']) ? -1 : 1;
                }

                switch ($sortby) {
                    case 'name,d':
                    case 'name,desc':
                    case 'namedesc':
                        return ($col) ? $col->compare($b['name'], $a['name']) : strncmp($b['name'], $a['name'], strlen($b['name']));
                    case 'size':
                    case 'size,a':
                    case 'size,asc':
                    case 'sizeasc':
                        if (($a['dir'] && $b['dir']) || $a['size'] == $b['size']) {
                            break;
                        }
                        return ($a['size'] <=> $b['size']);
                    case 'size,d':
                    case 'size,desc':
                    case 'sizedesc':
                        if (($a['dir'] && $b['dir']) || $a['size'] == $b['size']) {
                            break;
                        }
                        return ($b['size'] <=> $a['size']);
                    case 'date':
                    case 'date,a':
                    case 'date,asc':
                    case 'dateasc':
                        if ($a['date'] == $b['date']) {
                            break;
                        }
                        return ($a['date'] <=> $b['date']);
                    case 'date,d':
                    case 'date,desc':
                    case 'datedesc':
                        if ($a['date'] == $b['date']) {
                            break;
                        }
                        return ($b['date'] <=> $a['date']);
                    default:
                        break;
                }
                return ($col) ? $col->compare($a['name'], $b['name']) : strncmp($a['name'], $b['name'], strlen($a['name']));
            });
        }
        return $result;
    }

    /**
     * Get the extension of the specified file
     * @param string $path Filesystem path, or at least the basename, of a file
     * @param bool $lower Optional flag, whether to lowercase the result. Default true.
     * @return string, lowercase if $lower is true or not set
     * The extensions we're interested in are all ASCII, but if otherwise
     * here, too bad about the lowercase !
     */
    public static function get_extension(string $path, bool $lower = true) : string
    {
        $p = strrpos($path, '.');
        if( !$p ) { return ''; } // none or at start
        $ext = substr($path, $p + 1);
        if( $lower) {
            return strtolower($ext);
        }
        return $ext;
    }

    /**
     * Get a variant of the supplied $path with definitely-lowercase filename extension
     * @param string $path Filesystem path, or at least the basename, of a file
     * @return string
     */
    public static function lower_extension(string $path) : string
    {
        $ext = self::get_extension($path);
        if ($ext !== '') {
            $p = strrpos($path, '.');
            return substr($path, 0, $p + 1) . $ext;
        }
        return $path;
    }

    /**
     * Get a variant of the supplied $path without any suspect chars in the
     *  last path-segment (normally a filename)
     * @param string $rootpath absolute filesystem path to be prepended to $path
     *  if the latter is relative, and to use for path validation
     * @param string $path absolute or root-relative filesystem-path of file or folder
     * @return string, the valid absolute filepath, or empty if there's a problem
     */
    public static function clean_path(string $rootpath, string $path) : string
    {
        if (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $path)) {
            // $path is relative
            $path = cms_join_path($rootpath, $path);
        }
        $dirpath = dirname($path);
        if (realpath($dirpath) === false) {
            return '';
        }
        if (!startswith($dirpath, CMS_ROOT_PATH)) {
            return '';
        }
        if (!startswith($dirpath, $rootpath)) {
            return '';
        }
        $fn = basename($path);
        $fn = sanitizeVal($fn, CMSSAN_FILE);
        if ($fn) {
            return $dirpath . DIRECTORY_SEPARATOR . $fn;
        }
        return '';
    }
} //class
