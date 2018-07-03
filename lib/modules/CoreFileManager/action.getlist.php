<?php
/*
CoreFileManager module action: getlist ajax processor and component of defaultadmin action
Copyright (C) 2018 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program. If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\FilePickerProfile;

if (!isset($gCms)) exit;
$pdev = $this->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
if (!($pdev || $this->CheckPermission('Modify Files'))) exit;

// variables used in included file
global $CFM_ROOTPATH, $CFM_IS_WIN, $CFM_ICONV_INPUT_ENC, $CFM_EXCLUDE_FOLDERS, $CFM_FOLDER_URL, $CFM_FOLDER_TITLE, $helper;

$helper = new \CMSMS\FileTypeHelper($config);
$CFM_ROOTPATH = ($pdev) ? CMS_ROOT_PATH : $config['uploads_path'];
$CFM_RELPATH = $params['p'] ?? '';

$pathnow = $CFM_ROOTPATH;
if ($CFM_RELPATH) {
    $pathnow .= DIRECTORY_SEPARATOR . $CFM_RELPATH;
}
if (!is_dir($pathnow)) { //CHECKME link to a dir ok?
    $pathnow = $CFM_ROOTPATH;
    $CFM_RELPATH = '';
}

$user_id = get_userid(false);
$mod = cms_utils::get_module('FilePicker');
$profile = $mod->get_default_profile($pathnow, $user_id);

$CFM_IS_WIN = DIRECTORY_SEPARATOR == '\\';
$CFM_ICONV_INPUT_ENC = CmsNlsOperations::get_encoding(); //'UTF-8';
$CFM_READONLY = !($pdev || $this->CheckPermission('Modify Files'));

$CFM_EXCLUDE_FOLDERS = []; //TODO per profile etc
$CFM_FOLDER_URL = $this->create_url($id, 'defaultadmin', $returnid, ['p'=>'']);
$CFM_FOLDER_TITLE = $this->Lang('goto');
$CFM_SHOW_HIDDEN = $profile->show_hidden;
$CFM_DATETIME_FORMAT = cms_siteprefs::get('defaultdateformat');
if ($CFM_DATETIME_FORMAT) {
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
    $CFM_DATETIME_FORMAT = str_replace('%', '', $CFM_DATETIME_FORMAT);
    $parts = explode(' ', $CFM_DATETIME_FORMAT);
    foreach ($parts as $i => $fmt) {
        if (array_key_exists($fmt, $strftokens)) {
            $parts[$i] = $strftokens[$fmt];
        } else {
            unset($parts[$i]);
        }
    }
    $CFM_DATETIME_FORMAT = implode(' ', $parts);
} else {
    $CFM_DATETIME_FORMAT = 'Y-m-d H:i';
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
        if (in_array($name, $CFM_EXCLUDE_FOLDERS)) {
            ++$skipped;
            continue;
        }
        if (!$CFM_SHOW_HIDDEN && $name[0] === '.') {
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

$u = $this->create_url($id, 'open', $returnid, ['p'=>$CFM_RELPATH, 'view'=>'XXX']);
$linkview = '<a href="'. $u .'" title="'. $this->Lang('view') .'">YYY</a>';

if ($pdev) {
    $u = $this->create_url($id, 'open', $returnid, ['p'=>$CFM_RELPATH, 'edit'=>'XXX']);
    $icon = '<i class="if-edit" title="'.$this->Lang('edit').'"></i>';
    $linkedit = '<a href="'. $u .'" title="'. $this->Lang('edit') .'">'.$icon.'</a>'."\n";
}

$t = ($CFM_RELPATH) ? $CFM_RELPATH.DIRECTORY_SEPARATOR : '';
$u = $this->create_url($id, 'defaultadmin', $returnid, ['p'=>$t.'XXX']);
$t = $this->Lang('goto');
$linkopen = '<a href="'. $u .'" alt="'.$t.'" title="'.$t.'">YYY</a>';

$linkchmod = '<a href="javascript:oneChmod(\''.$CFM_RELPATH .'\',\'%s\',\'%s\',%d,%d)" title="'. $this->Lang('changepermstip') .'">%s</a>'."\n";

if ($profile->can_delete) {
    $t = $this->Lang('delete');
    $icon = '<i class="if-trash-empty red" alt="'.$t.'" title="'.$t.'"></i>';
    $linkdel = '<a href="javascript:oneDelete(\'' . $CFM_RELPATH .'\',\'XXX\')">'.$icon.'</a>'."\n";
}

$t = $this->Lang('rename');
$icon = '<i class="if-rename" alt="'.$t.'" title="'.$t.'"></i>';
$linkren = '<a href="javascript:oneRename(\'' . $CFM_RELPATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$CFM_RELPATH, 'copy'=>'XXX']);
$t = $this->Lang('copytip');
$icon = '<i class="if-docs" alt="'.$t.'" title="'.$t.'"></i>';
$linkcopy = '<a href="javascript:oneCopy(\'' . $CFM_RELPATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$t = $this->Lang('linktip');
$icon = '<i class="if-link" alt="'.$t.'" title="'.$t.'"></i>';
$linklink = '<a href="javascript:oneLink(\'' . $CFM_RELPATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$CFM_RELPATH, 'dl'=>'XXX']);
$t = $this->Lang('download');
$icon = '<i class="if-download" alt="'.$t.'" title="'.$t.'"></i>';
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
    $df = cfm_enc($name);

    $is_link = is_link($fp);
    $oneset->is_link = $is_link;
    $oneset->realpath = $is_link ? readlink($fp) : null;
    $oneset->icon = $is_link ? 'if-folder' : 'if-folder'; //TODO icon-link_folder

    $oneset->path = rawurlencode(trim($CFM_RELPATH . DIRECTORY_SEPARATOR . $name, DIRECTORY_SEPARATOR)); //relative path
    if (is_readable($fp)) {
        $oneset->link = str_replace(['XXX', 'YYY'], [$encf, cfm_convert_win($name)], $linkopen);
    } else {
        $oneset->link = cfm_convert_win($name);
    }
    $oneset->name = $name;

    $oneset->rawsize = 0;
    $oneset->size = ''; //no size-display for a folder

    $st = filemtime($fp);
    $oneset->rawtime = $st;
    $dt->setTimestamp($st);
    $oneset->modat = $dt->format($CFM_DATETIME_FORMAT);

    if (!$CFM_IS_WIN) {
        $m = fileperms($fp);
        $t = cfm_get_fileperms($m, true);
        if (!$CFM_READONLY) {
            $oneset->perms = sprintf($linkchmod, $name, $df, 1, ($m & 07777), $t);
        } else {
            $oneset->perms = $t;
        }
    }

    if ($CFM_READONLY) {
        $acts = '';
    } else {
        if ($profile->can_delete) {
             $acts = str_replace('XXX', $name, $linkdel);
        } else {
            $acts = '';
        }
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
    $df = cfm_enc($name);

    $is_link = is_link($fp);
    $oneset->is_link = $is_link;
    $oneset->realpath = $is_link ? readlink($fp) : null;
    $oneset->icon = $is_link ? 'if-doc-text' : cfm_get_file_icon_class($fp);

    $oneset->path = rawurlencode(trim($CFM_RELPATH . DIRECTORY_SEPARATOR . $name, DIRECTORY_SEPARATOR)); //TODO
    if (is_readable($fp)) {
        $oneset->link = str_replace(['XXX','YYY'], [$encf, cfm_convert_win($name)], $linkview);
    } else {
        $oneset->link = cfm_convert_win($name);
    }
    $oneset->name = $name;

    $st = filemtime($fp);
    $oneset->rawtime = $st;
    $dt->setTimestamp($st);
    $oneset->modat = $dt->format($CFM_DATETIME_FORMAT);

    $filesize_raw = filesize($fp);
    $total_size += $filesize_raw;
    $oneset->rawsize = $filesize_raw;
    $oneset->size = cfm_get_filesize($filesize_raw);

    if (!$CFM_IS_WIN) {
        $m = fileperms($fp);
        $t = cfm_get_fileperms($m);
        if (!$CFM_READONLY) {
            $oneset->perms = sprintf($linkchmod, $name, $df, 0, ($m & 07777), $t);
        } else {
            $oneset->perms = $t;
        }
    }

    if ($CFM_READONLY) {
        $acts = '';
    } else {
        if ($profile->can_delete) {
            $acts = str_replace('XXX', $name, $linkdel);
        } else {
            $acts = '';
        }
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
    $t = cfm_get_filesize($total_size);
    $s = $this->Lang('summary', $c2, $c, $t);
} elseif ($skipped > 0) {
    $s = $this->Lang('noitemshow');
} else {
    $s = $this->Lang('noitems');
}

$smarty->assign([
    'mod' => $this,
    'actionid' => $id,
    'CFM_IS_WIN' => $CFM_IS_WIN,
    'CFM_READONLY' => $CFM_READONLY,
    'pointer' => '&rarr;', //TODO or '&larr;' for 'rtl'
    'bytename' => $bytename,
    'items' => $items,
    'summary' => $s,
]);

if (!empty($params['ajax'])) {
    echo $this->ProcessTemplate('getlist.tpl');
    exit;
}
