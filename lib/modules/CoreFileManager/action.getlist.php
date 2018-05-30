<?php
# CoreFileManager module action: getlist ajax processor and component of defaultadmin action
# Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
# This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
# You should have received a copy of the GNU General Public License
# along with this program. If not, see <https://www.gnu.org/licenses/>.

use CMSMS\FilePickerProfile;

if (!isset($gCms)) exit;
$pdev = $this->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
if (!($pdev || $this->CheckPermission('Modify Files'))) exit;

// variables used in included file
global $FM_ROOT_PATH, $FM_IS_WIN, $FM_ICONV_INPUT_ENC, $FM_EXCLUDE_FOLDERS, $FM_FOLDER_URL, $FM_FOLDER_TITLE, $helper;

$helper = null;
$FM_ROOT_PATH = ($pdev) ? CMS_ROOT_PATH : $config['uploads_path'];
$FM_PATH = $params['p'] ?? '';

$pathnow = $FM_ROOT_PATH;
if ($FM_PATH) {
    $pathnow .= DIRECTORY_SEPARATOR . $FM_PATH;
}
if (!is_dir($pathnow)) { //CHECKME link to a dir ok?
    $pathnow = $FM_ROOT_PATH;
    $FM_PATH = '';
}

$user_id = get_userid(false);
$mod = cms_utils::get_module('FilePicker');
$profile = $mod->get_default_profile($pathnow, $user_id);

$FM_IS_WIN = DIRECTORY_SEPARATOR == '\\';
$FM_ICONV_INPUT_ENC = CmsNlsOperations::get_encoding(); //'UTF-8';
$FM_READONLY = !($pdev || $this->CheckPermission('Modify Files'));

$FM_EXCLUDE_FOLDERS = []; //TODO per profile etc
$FM_FOLDER_URL = $this->create_url($id, 'defaultadmin', $returnid, ['p'=>'']);
$FM_FOLDER_TITLE = $this->Lang('goto');
$FM_SHOW_HIDDEN = $profile->show_hidden;
$FM_DATETIME_FORMAT = get_site_preference('defaultdateformat');
if ($FM_DATETIME_FORMAT) {
    $strftokens = [
    // Day - no strf eq : S
    'a' => 'D', 'A' => 'l', 'd' => 'd', 'e' => 'j', 'j' => 'z', 'u' => 'N', 'w' => 'w',
    // Week - no date eq : %U, %W
    'V' => 'W',
    // Month - no strf eq : n, t
    'b' => 'M', 'B' => 'F', 'm' => 'm',
    // Year - no strf eq : L; no date eq : %C, %g
    'G' => 'o', 'y' => 'y', 'Y' => 'Y',
    // Full Date / Time - no strf eq : c, r; no date eq : %c
    's' => 'U', 'D' => 'j/n/y', 'F' => 'Y-m-d', 'x' => 'j F Y'
    ];
    $FM_DATETIME_FORMAT = str_replace('%', '', $FM_DATETIME_FORMAT);
    $parts = explode(' ', $FM_DATETIME_FORMAT);
    foreach ($parts as $i => $fmt) {
        if (array_key_exists($fmt, $strftokens)) {
            $parts[$i] = $strftokens[$fmt];
        } else {
            unset($parts[$i]);
        }
    }
    $FM_DATETIME_FORMAT = implode(' ', $parts);
} else {
    $FM_DATETIME_FORMAT = 'Y-m-d H:i';
}

global $bytename, $kbname, $mbname, $gbname; //$tbname
$bytename = $this->Lang('bb');
$kbname = $this->Lang('kb');
$mbname = $this->Lang('mb');
$gbname = $this->Lang('gb');
//$tbname = $this->Lang('tb');

global $pr, $pw, $px, $pxf;
$pr = $this->Lang('perm_r');
$pw = $this->Lang('perm_w');
$px = $this->Lang('perm_x');
$pxf = $this->Lang('perm_xf');

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

$folders = [];
$files = [];
$skipped = 0;
$items = is_readable($pathnow) ? scandir($pathnow) : [];

if ($items) {
    foreach ($items as $name) {
        if ($name == '.' || $name == '..') {
            continue;
        }
        if (in_array($name, $FM_EXCLUDE_FOLDERS)) {
            ++$skipped;
            continue;
        }
        if (!$FM_SHOW_HIDDEN && $name[0] === '.') {
            ++$skipped;
            continue;
        }
        $fp = $pathnow . DIRECTORY_SEPARATOR . $name;
        if (is_file($fp)) {
            $files[] = $name;
        } elseif (is_dir($fp)) {
            $folders[] = $name;
        }
    }
}

$total_size = 0;

$u = $this->create_url($id, 'open', $returnid, ['p'=>$FM_PATH, 'view'=>'XXX']);
$linkview = '<a href="'. $u .'" title="'. $this->Lang('view') .'">YYY</a>';

if ($pdev) {
    $u = $this->create_url($id, 'open', $returnid, ['p'=>$FM_PATH, 'edit'=>'XXX']);
    $icon = '<i class="if-edit" title="'.$this->Lang('edit').'"></i>';
    $linkedit = '<a href="'. $u .'" title="'. $this->Lang('edit') .'">'.$icon.'</a>'."\n";
}

$t = ($FM_PATH) ? $FM_PATH.DIRECTORY_SEPARATOR : '';
$u = $this->create_url($id, 'defaultadmin', $returnid, ['p'=>$t.'XXX']);
$linkopen = '<a href="'. $u .'" title="'. $this->Lang('goto') .'">YYY</a>';

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'chmod'=>'XXX']);
$linkchmod = '<a href="'. $u .'" title="'. $this->Lang('changeperms') .'">YYY</a>';

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'del'=>'XXX']);
$icon = '<i class="if-trash-empty red" title="'.$this->Lang('delete').'"></i>';
$linkdel = '<a href="'. $u .'" onclick="cms_confirm_linkclick(this, \''. $this->Lang('del_confirm') . '\');return false;">'.$icon.'</a>'."\n";

$t = $this->Lang('rename');
$icon = '<i class="if-rename" alt="'.$t.'" title="'.$t.'"></i>';
$linkren = '<a href="javascript:oneRename(\'' . $FM_PATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'copy'=>'XXX']);
$icon = '<i class="if-docs" title="'.$this->Lang('copytip').'"></i>';
$linkcopy = '<a href="javascript:oneCopy(\'' . $FM_PATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$t = $this->Lang('linktip');
$icon = '<i class="if-link" alt="'.$t.'" title="'.$t.'"></i>';
$linklink = '<a href="javascript:oneLink(\'' . $FM_PATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'dl'=>'XXX']);
$icon = '<i class="if-download" title="'.$this->Lang('download').'"></i>';
$linkdown = '<a href="'. $u .'">'.$icon.'</a>'."\n";

$tz = (!empty($config['timezone'])) ? $config['timezone'] : 'UTC';
$dt = new DateTime(null, new DateTimeZone($tz));

$items = [];
$c = 0;
foreach ($folders as $name) {
    $oneset = new stdClass();
    $oneset->dir = true;

    $fp = $pathnow . DIRECTORY_SEPARATOR . $name;
    $encf = rawurlencode($name);

    $is_link = is_link($fp);
    $oneset->is_link = $is_link;
    $oneset->realpath = $is_link ? readlink($fp) : null;
    $oneset->icon = $is_link ? 'icon-link_folder' : 'if-folder'; //TODO icon-link_folder

    $oneset->path = rawurlencode(trim($FM_PATH . DIRECTORY_SEPARATOR . $name, DIRECTORY_SEPARATOR)); //relative path
    if (is_readable($fp)) {
        $oneset->link = str_replace(['XXX', 'YYY'], [$encf, fm_convert_win($name)], $linkopen);
    } else {
        $oneset->link = fm_convert_win($name);
    }
    $oneset->name = $name;

    $oneset->rawsize = 0;
    $oneset->size = ''; //no size-display for a folder

    $st = filemtime($fp);
    $oneset->rawtime = $st;
    $dt->setTimestamp($st);
    $oneset->modat = $dt->format($FM_DATETIME_FORMAT);

    if (!$FM_IS_WIN) {
        $t = fm_get_fileperms(fileperms($fp), true);
        if (!$FM_READONLY) {
            $oneset->perms = str_replace(['XXX', 'YYY'], [$encf, $t], $linkchmod);
        } else {
            $oneset->perms = $t;
        }
    }

    if ($FM_READONLY) {
        $acts = '';
    } else {
        $df = fm_enc($name);
        $acts = str_replace('XXX', $name, $linkdel);
        $acts .= str_replace(['XXX','YYY'], [$name, $df], $linkren);
        $acts .= str_replace(['XXX','YYY'], [$name, $df], $linkcopy);
        if ($pdev) {
            $acts .= '<span class="actionspacer"></span>';
        }
        $acts .= str_replace(['XXX','YYY'], [$name, $df], $linklink);

        $oneset->sel = $encf;
    }
    $acts .= str_replace('XXX', $encf, $linkdown);
    $oneset->acts = $acts;

    $items[] = $oneset;
    ++$c;
}

$c2 = 0;
foreach ($files as $name) {
    $oneset = new stdClass();
    $oneset->dir = false;
    $fp = $pathnow . DIRECTORY_SEPARATOR . $name;
    $encf = rawurlencode($name);

    $is_link = is_link($fp);
    $oneset->is_link = $is_link;
    $oneset->realpath = $is_link ? readlink($fp) : null;
    $oneset->icon = $is_link ? 'if-doc-text' : fm_get_file_icon_class($fp);

    $oneset->path = rawurlencode(trim($FM_PATH . DIRECTORY_SEPARATOR . $name, DIRECTORY_SEPARATOR)); //TODO
    if (is_readable($fp)) {
        $oneset->link = str_replace(['XXX','YYY'], [$encf, fm_convert_win($name)], $linkview);
    } else {
        $oneset->link = fm_convert_win($name);
    }
    $oneset->name = $name;

    $st = filemtime($fp);
    $oneset->rawtime = $st;
    $dt->setTimestamp($st);
    $oneset->modat = $dt->format($FM_DATETIME_FORMAT);

    $filesize_raw = filesize($fp);
    $total_size += $filesize_raw;
    $oneset->rawsize = $filesize_raw;
    $oneset->size = fm_get_filesize($filesize_raw);

    if (!$FM_IS_WIN) {
        $t = fm_get_fileperms(fileperms($fp));
        if (!$FM_READONLY) {
            $oneset->perms = str_replace(['XXX','YYY'], [$encf, $t], $linkchmod);
        } else {
            $oneset->perms = $t;
        }
    }

    if ($FM_READONLY) {
        $acts = '';
    } else {
        $df = fm_enc($name);
        $acts = str_replace('XXX', $name, $linkdel);
        $acts .= str_replace(['XXX','YYY'], [$name, $df], $linkren);
        $acts .= str_replace(['XXX','YYY'], [$name, $df], $linkcopy);
        if ($pdev) {
            if ($helper->is_text($fp)) {
                $acts .= str_replace('XXX', $name, $linkedit);
            } else {
                $acts .= '<span class="actionspacer"></span>';
            }
        }
        $acts .= str_replace(['XXX','YYY'], [$name, $df], $linklink);

        $oneset->sel = $encf;
    }
    $acts .= str_replace('XXX', $encf, $linkdown);
    $oneset->acts = $acts;

    $items[] = $oneset;
    ++$c2;
}

if (count($items) > 1) {
    $sortby = $profile->sort;
    if ($sortby !== FilePickerProfile::FLAG_NO) {
        if (class_exists('Collator')) {
            $lang = CmsNlsOperations::get_default_language();
            $col = new Collator($lang); // e.g. new Collator('pl_PL') TODO if.UTF-8 ?? ini 'output_encoding' ??
        } else {
            $col = false;
            // fallback ?? e.g. setlocale() then strcoll()
        }
        usort($items, function ($a, $b) use ($sortby, $col)
        {
            if ($a->dir xor $b->dir) {
                // one is a dir, first
                return ($a->dir) ? -1 : 1;
            }

            switch ($sortby) {
                case 'name,d':
                case 'name,desc':
                case 'namedesc':
                    return ($col) ? $col->compare($b->name, $a->name) : strnatcmp($b->name, $a->name);
                case 'size':
                case 'size,a':
                case 'size,asc':
                case 'sizeasc':
                    if (($a->dir && $b->dir) || $a->rawsize == $b->rawsize) {
                        break;
                    }
                    return ($a->rawsize <=> $b->rawsize);
                case 'size,d':
                case 'size,desc':
                case 'sizedesc':
                    if (($a->dir && $b->dir) || $a->rawsize == $b->rawsize) {
                        break;
                    }
                    return ($b->rawsize <=> $a->rawsize);
                case 'date':
                case 'date,a':
                case 'date,asc':
                case 'dateasc':
                    if ($a->rawtime == $b->rawtime) {
                        break;
                    }
                    return ($a->rawtime <=> $b->rawtime);
                case 'date,d':
                case 'date,desc':
                case 'datedesc':
                    if ($a->rawtime == $b->rawtime) {
                        break;
                    }
                    return ($b->rawtime <=> $a->rawtime);
                default:
                    break;
            }
            return ($col) ? $col->compare($a->name, $b->name) : strnatcmp($a->name, $b->name);
        });
    }
}

if ($items) {
    $t = fm_get_filesize($total_size);
    $s = $this->Lang('summary', $c2, $c, $t);
} elseif ($skipped > 0) {
    $s = $this->Lang('noitemshow');
} else {
    $s = $this->Lang('noitems');
}

$smarty->assign([
    'mod' => $this,
    'actionid' => $id,
    'FM_IS_WIN' => $FM_IS_WIN,
    'FM_READONLY' => $FM_READONLY,
    'bytename' => $bytename,
    'items' => $items,
    'summary' => $s,
]);

if (!empty($params['ajax'])) {
    echo $this->ProcessTemplate('getlist.tpl');
    exit;
}
