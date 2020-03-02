<?php
# Filepicker module: utility-methods class
# Copyright (C) 2018-2020 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

namespace FilePicker;

use cms_config;
use cms_siteprefs;
use cms_utils;
use CMSMS\FilePickerProfile;
use CMSMS\FileTypeHelper;
use CMSMS\NlsOperations;
use Collator;
use FilePicker;
use const CMS_ROOT_PATH;
use function cms_join_path;
use function cms_path_to_url;
use function get_userid;
use function startswith;

/**
 * A class of utility-methods for the FilePicker module
 *
 * @package CMS
 * @license GPL
 * @since  2.3
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
        static $mod = null;
        if ($mod == null) {
            $mod = cms_utils::get_module('FilePicker');
        }
        $baseurl = $mod->GetModuleURLPath();

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
                require_once cms_join_path(dirname(__DIR__),'images','types','typealias.php');
                $getem = false;
            }
            $lcext = $dups[$lcext] ?? '0';
        }
        return '<img src="'.$baseurl.'/images/types/'.$lcext.'.png" class="listicon" alt="'.$ext.'-file" />';
    }

    /**
     * @param FilePicker $mod
     * @param int $mode
     * @param bool $isdir
     * @return string
     */
    public static function format_permissions(FilePicker &$mod, int $mode, bool $isdir) : string
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
    /*    private function file_details(string $filepath, array &$info) : string
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
    /**
     * Return data for relevant files/sub-folders in folder $path
     * @param string $path Optional absolute or root-relative filesystem-path
     *  of folder to be reported. Default '' (hence use relevant root)
     * @return array (maybe empty)
     */
    public static function get_file_list(string $path = '') : array
    {
        $config = cms_config::get_instance();
        $mod = cms_utils::get_module('FilePicker');
        $devmode = $mod->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
        $rootpath = ($devmode) ? CMS_ROOT_PATH : $config['uploads_path'];

        if (!$path) {
            $path = $rootpath;
        } elseif (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $path)) {
            // $path is relative
            $path = cms_join_path($rootpath, $path);
        } elseif (!startswith($path, CMS_ROOT_PATH)) {
            return [];
        }
        if (!is_dir($path)) {
            return [];
        }

        // not a huge no. of items in website folders, no need for opendir/readdir/closedir
        $items = scandir($path, SCANDIR_SORT_NONE);
        if (!$items) {
            return [];
        }

        $user_id = get_userid(false);
        $profile = $mod->get_default_profile($path, $user_id); //CHECKME
        $showhidden = $profile->show_hidden || $devmode;
        $showthumb = $profile->show_thumbs;
        $pex = $profile->exclude_prefix ?? '';
        $pin = $profile->match_prefix ?? '';

        $typer = new FileTypeHelper($config);
        $posix = function_exists('posix_getpwuid');
        if (!$posix) {
            $ownerna = $mod->Lang('na');
        }
        $showup = ($path != $rootpath);

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

            $filepath = $path.DIRECTORY_SEPARATOR.$name;
            if (!$showthumb && $typer->is_thumb($filepath)) {
                continue;
            }

            $info = ['fullpath' => $filepath, 'dir' => is_dir($filepath), 'name' => $name];
            if (!$info['dir']) {
                $info['ext'] = $typer->get_extension($name);
                $info['text'] = $typer->is_text($filepath);
                $info['image'] = $typer->is_image($filepath);
                $info['archive'] = !$info['text'] && !$info['image'] && $typer->is_archive($filepath);
                $info['mime'] = $typer->get_mime_type($filepath);
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
        if ($sortby !== FilePickerProfile::FLAG_NO) {
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
     * Save a thumbnail if possible
     * @param string $rootpath absolute filesystem path to be prepended if $path is relative
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
        $width = (int) cms_siteprefs::get('thumbnail_width', 96);
        $height = (int) cms_siteprefs::get('thumbnail_height', 96);
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
} //class
