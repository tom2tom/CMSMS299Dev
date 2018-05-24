<?php
# Filepicker module: utility-methods class
# Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
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
use cms_utils;
use CMSMS\AdminUtils;
use CMSMS\FileTypeHelper;
use FilePicker;
use const CMS_ROOT_PATH;
use function cms_join_path;
use function get_userid;

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
     * @ignore
     */
    private function format_permissions(FilePicker &$mod, int $mode, bool $isdir) : string
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
        if ($mode & 0x0100) $perms[] = $pr;
        if ($mode & 0x0080) $perms[] = $pw;
        if ($mode & 0x0040) $perms[] = ($isdir) ? $pxf : $px; //ignore static flag
        return implode('+', $perms);
    }

    /**
     * @ignore
     */
    private function file_details(string $fullname, array &$info) : string
    {
        if (!empty($info['image'])) {
            $imginfo = @getimagesize($fullname);
            if ($imginfo) {
                $t = imginfo[0].' x '.$imginfo[1];
                if (isset($imginfo['bits'])) {
                    $t .= ' x '.$imginfo['bits'];
                }
                return $t;
            }
        }
        return '';
    }

    /**
     * @paran string $path Optional root-relative filesystem-path of directory
     *  to be reported. Default '' (use profile)
     * @return mixed array or false
     */
    public static function get_file_list(string $path = '')
    {
        $config = cms_config::get_instance();
        $mod = cms_utils::get_module('FilePicker');
		$advancedmode = $mod->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
        $rootpath = ($advancedmode) ? CMS_ROOT_PATH : $config['uploads_path'];

        if (!$path) {
            $path = $rootpath;
        } elseif (!preg_match('~^ *(?:\/|\\\\|\w:\\\\|\w:\/)~', $path)) {
            // $path is relative
            $path = cms_join_path($rootpath, $path);
        } elseif (!startswith($path, CMS_ROOT_PATH)) {
            return false;
		}

        if (!is_dir($path)) {
            return false;
        }
        $dh = @opendir($path);
        if (!$dh) {
            return false;
        }

        $user_id = get_userid(false);
        $profile = $mod->get_default_profile($path, $user_id); //CHECKME
        $showhidden = $profile->show_hidden || $advancedmode;
		$showthumb = $profile->show_thumbs;
		$pex = $profile->exclude_prefix;
		$pin = $profile->match_prefix;

        $typer = new FileTypeHelper($config);
        $posix = function_exists('posix_getpwuid');
        if (!$posix) {
            $ownerna = $mod->Lang('na');
        }
        $showup = ($path != $rootpath);

        $result = [];

        while ($file = readdir($dh)) {
            if ($file == '.') {
                continue;
            }
            if ($file == '..') {
                // can we go up
                if (!$showup) {
                    continue;
                }
            } elseif ($file[0] == '.' || $file[0] == '_' || $file[0] == '~') {
                if (!$showhidden) {
                    continue;
                }
            }
			if ($pin !== '' && !startswith($file, $pin) ) {
				continue;
			}
			if ($pex !== '' && startswith($file, $pex) ) {
				continue;
			}

            $fullname = $path.DIRECTORY_SEPARATOR.$file;
            if (!$showthumb && $typer->is_thumb($fullname)) {
                continue;
            }

            $statinfo = stat($fullname);

            $info = ['name' => $file];

            if (is_dir($fullname)) {
                $info['dir'] = true;
                $info['size'] = $statinfo['size'];
                $info['date'] = $statinfo['mtime']; //timestamp
            } else {
                $info['dir'] = false;
                $info['ext'] = $typer->get_extension($file);
                $info['image'] = $typer->is_image($fullname);
                $info['archive'] = !$info['image'] && $typer->is_archive($fullname);
                $info['mime'] = $typer->get_mime_type($fullname);
                $info['size'] = $statinfo['size'];
                $info['date'] = $statinfo['mtime']; //timestamp
                $info['url'] = AdminUtils::path_to_url($fullname);
            }
            if ($posix) {
                $userinfo = @posix_getpwuid($statinfo['uid']);
                $info['fileowner'] = $userinfo['name'] ?? $mod->Lang('unknown');
            } else {
                $info['fileowner'] = $ownerna;
            }

            $info['writable'] = is_writable($fullname);
            $info['permissions'] = self::format_permissions($mod, $statinfo['mode'], $info['dir']);
            $info['fileinfo'] = self::file_details($fullname, $info);

            $result[] = $info;
        }

        closedir($dh);

        $sortby = $profile->sort;
        if ($sortby != FilePickerProfile::FLAG_NO) {
            if (class_exists('Collator')) {
                $lang = \CmsNlsOperations::get_current_language();
                $col = new Collator($lang); //e.g. new Collator('pl_PL');
            } else {
                $col = false;
                // fallback ?? setlocale () + strcoll ()
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
                        return ($col) ? $coll->compare($b['name'], $a['name']) : strncmp($b['name'], $a['name'], strlen($b['name']));
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
                return ($col) ? $coll->compare($a['name'], $b['name']) : strncmp($a['name'], $b['name'], strlen($a['name']));
            });
        }
        return $result;
    }
} //class
