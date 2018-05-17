<?php
# CoreFileManager module action: defaultadmin
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

/*
This action was inspired by and somewhat derives from H3K Tiny File Manager
https://github.com/prasathmani/tinyfilemanager
*/

if (!isset($gCms)) exit;
if (!$this->CheckPermission('Modify Files')) exit;

$format = get_site_preference('defaultdateformat');
if ($format) {
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
    $format = str_replace('%', '', $format);
    $parts = explode(' ', $format);
    foreach ($parts as $i => $fmt) {
        if (array_key_exists($fmt, $strftokens)) {
            $parts[$i] = $strftokens[$fmt];
        } else {
            unset($parts[$i]);
        }
    }
    $format = implode(' ', $parts);
} else {
    $format = 'Y-m-d H:i';
}

$pdev = !empty($config['developer_mode']); //AND/OR $this->CheckPermission('Modify Sitecode')

global $FM_ROOT_PATH, $FM_IS_WIN, $FM_ICONV_INPUT_ENC, $FM_EXCLUDE_FOLDERS, $FM_FOLDER_URL, $FM_FOLDER_TITLE;

$FM_ROOT_PATH = ($pdev) ? CMS_ROOT_PATH : $config['uploads_path'];
$FM_PATH = $params['p'] ?? '';
$FM_IS_WIN = DIRECTORY_SEPARATOR == '\\';
$FM_ICONV_INPUT_ENC = CmsNlsOperations::get_encoding(); //'UTF-8';
$FM_READONLY = !($pdev || $this->CheckPermission('Modify Files'));
$FM_EXCLUDE_FOLDERS = []; //TODO
$FM_FOLDER_URL = $this->create_url($id, 'defaultadmin', $returnid, ['p'=>'']);
$FM_FOLDER_TITLE = $this->Lang('goto');
$FM_SHOW_HIDDEN = $this->GetPreference('showhiddenfiles', 0);
$FM_DATETIME_FORMAT = $format;
//$FM_TREEVIEW = true;

$smarty->assign('mod', $this);
$smarty->assign('actionid', $id);
$smarty->assign('form_start', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', ['p'=> rawurlencode($FM_PATH)]));
$smarty->assign('FM_IS_WIN', $FM_IS_WIN);
$smarty->assign('FM_READONLY', $FM_READONLY);

global $bytename, $kbname, $mbname, $gbname; //$tbname
$bytename = $this->Lang('bb');
$kbname = $this->Lang('kb');
$mbname = $this->Lang('mb');
$gbname = $this->Lang('gb');
//$tbname = $this->Lang('tb');

$smarty->assign('bytename', $bytename);

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

/* TODO toastifed notices
if (isset($_SESSION['message'])) {
    $t = $_SESSION['status'] ?? 'success';
    if ($t == 'success') {
        $this->ShowMessage($_SESSION['message']);
    } else {
        $this->ShowErrors($_SESSION['message']);
    }
    unset($_SESSION['message']);
    unset($_SESSION['status']);
}
*/

$pathnow = $FM_ROOT_PATH;
if ($FM_PATH) {
    $pathnow .= DIRECTORY_SEPARATOR . $FM_PATH;
}
if (!is_dir($pathnow)) { //CHECKME link to a dir ok?
    $pathnow = $FM_ROOT_PATH;
    $FM_PATH = '';
}

// breadcrumbs

if ($FM_PATH) {
    $u = $this->create_url($id, 'defaultadmin', $returnid, ['p'=>'']);
    //root
    $oneset = new stdClass();
    $oneset->name = $this->Lang('top');
    $oneset->url = $u;
    $items = [$oneset];
    //rest
    $t = '';
    $segs = explode(DIRECTORY_SEPARATOR, $FM_PATH);
    $c = count($segs);
    for ($i=0; $i<$c; ++$i) {
        $oneset = new stdClass();
        $oneset->name = fm_enc(fm_convert_win($segs[$i]));
        if ($i > 0) $t .= DIRECTORY_SEPARATOR;
        $t .= $segs[$i];
        $oneset->url = $u.rawurlencode($t);
        $items[] = $oneset;
    }
    $smarty->assign('crumbs', $items);
    $t = dirname($FM_PATH);
    if ($t == '.') {$t = '';} else {$t = rawurlencode($t);}
    $smarty->assign('parent_url', $u.$t);
}

// folders tree

$smarty->assign('pointer', '&rarr;'); //or '&larr;' for 'rtl'
$smarty->assign('crumbjoiner', 'if-angle-double-right'); //or 'if-angle-double-left' for 'rtl'
$smarty->assign('browse', $this->Lang('browse'));
//if($FM_TREEVIEW) {
    $t = fm_dir_tree($FM_ROOT_PATH, (($FM_PATH) ? $pathnow : ''));
    $smarty->assign('treeview', $t);
//}

// folders & files

$tz = (!empty($config['timezone'])) ? $config['timezone'] : 'UTC';
$dt = new DateTime(null, new DateTimeZone($tz));

$folders = [];
$files = [];
$skipped = 0;
$items = is_readable($pathnow) ? scandir($pathnow) : [];

if ($items) {
    foreach ($items as $file) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        if (in_array($file, $FM_EXCLUDE_FOLDERS)) {
            ++$skipped;
            continue;
        }
        if (!$FM_SHOW_HIDDEN && $file[0] === '.') {
            continue;
        }
        $fp = $pathnow . DIRECTORY_SEPARATOR . $file;
        if (is_file($fp)) {
            $files[] = $file;
        } elseif (is_dir($fp)) {
            $folders[] = $file;
        }
    }
}

if (count($files) > 1) {
    natcasesort($files); //TODO mb_ based sort
}
if (count($folders) > 1) {
    natcasesort($folders);
}

$total_size = 0;

$themeObject = cms_utils::get_theme_object();
$baseurl = $this->GetModuleURLPath();

$u = $this->create_url($id, 'open', $returnid, ['p'=>$FM_PATH, 'view'=>'XXX']);
$linkview = '<a href="'. $u .'" title="'. $this->Lang('view') .'">YYY</a>';

$t = ($FM_PATH) ? $FM_PATH.DIRECTORY_SEPARATOR : '';
$u = $this->create_url($id, 'defaultadmin', $returnid, ['p'=>$t.'XXX']);
$linkopen = '<a href="'. $u .'" title="'. $this->Lang('goto') .'">YYY</a>';

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'chmod'=>'XXX']);
$linkchmod = '<a href="'. $u .'" title="'. $this->Lang('changeperms') .'">YYY</a>';

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'del'=>'XXX']);
$icon = $themeObject->DisplayImage('icons/system/delete.gif', $this->Lang('delete'), '', '', 'systemicon');
$linkdel = '<a href="'. $u .'" onclick="cms_confirm_linkclick(this, \''. $this->Lang('del_confirm') . '\');return false;">'.$icon.'</a>'."\n";

$t = $this->Lang('rename');
$icon = '<img src="'.$baseurl.'/images/rename.png" class="systemicon" alt="'.$t.'" title="'.$t.'" />';
$linkren = '<a href="javascript:oneRename(\'' . $FM_PATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'copy'=>'XXX']);
$icon = $themeObject->DisplayImage('icons/system/copy.gif', $this->Lang('copytip'), '', '', 'systemicon');
$linkcopy = '<a href="javascript:oneCopy(\'' . $FM_PATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$t = $this->Lang('linktip');
$icon = '<img src="'.$baseurl.'/images/link.png" class="systemicon" alt="'.$t.'" title="'.$t.'" />';
$linklink = '<a href="javascript:oneLink(\'' . $FM_PATH .'\',\'XXX\',\'YYY\')">'.$icon.'</a>'."\n";

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'dl'=>'XXX']);
$icon = $themeObject->DisplayImage('icons/system/arrow-d.gif', $this->Lang('download'), '', '', 'systemicon');
$linkdown = '<a href="'. $u .'">'.$icon.'</a>'."\n";

$pr = $this->Lang('perm_r');
$pw = $this->Lang('perm_w');
$px = $this->Lang('perm_x');
$pxf = $this->Lang('perm_xf');

$items = [];
$c = 0;
foreach ($folders as $f) {
    $oneset = new stdClass();
    $oneset->dir = true;

    $fp = $pathnow . DIRECTORY_SEPARATOR . $f;
    $encf = rawurlencode($f);

    $is_link = is_link($fp);
    $oneset->is_link = $is_link;
    $oneset->realpath = $is_link ? readlink($fp) : null;
    $oneset->icon = $is_link ? 'icon-link_folder' : 'if-folder-empty'; //TODO icon-link_folder

    $oneset->path = rawurlencode(trim($FM_PATH . DIRECTORY_SEPARATOR . $f, DIRECTORY_SEPARATOR)); //relative path
    if (is_readable($fp)) {
        $oneset->link = str_replace(['XXX', 'YYY'], [$encf, fm_convert_win($f)], $linkopen);
    } else {
        $oneset->link = fm_convert_win($f);
    }
    $oneset->name = $f;

    $oneset->rawsize = 0;
    $oneset->size = ''; //no size-display for a folder

    $st = filemtime($fp);
    $oneset->rawtime = $st;
    $dt->setTimestamp($st);
    $oneset->modat = $dt->format($FM_DATETIME_FORMAT);

    if (!$FM_IS_WIN) {
        $t = fileperms($fp);
        $perms = [];
        if ($t & 0x0100) $perms[] = $pr;
        if ($t & 0x0080) $perms[] = $pw;
        if ($t & 0x0040) $perms[] = $pxf; //ignore static flag
        $perms = implode('+',$perms);
        if (!$FM_READONLY) {
            $oneset->perms = str_replace(['XXX', 'YYY'], [$encf, $perms], $linkchmod);
        } else {
            $oneset->perms = $perms;
        }
    }

    if ($FM_READONLY) {
        $acts = '';
    } else {
        $df = fm_enc($f);
        $acts = str_replace('XXX', $f, $linkdel);
        $acts .= str_replace(['XXX','YYY'], [$f, $df], $linkren);
        $acts .= str_replace(['XXX','YYY'], [$f, $df], $linkcopy);
        $acts .= str_replace(['XXX','YYY'], [$f, $df], $linklink);
    }

    $oneset->acts = $acts;

    if (!$FM_READONLY) {
        $oneset->sel = $encf;
    }
    $items[] = $oneset;
    ++$c;
}

$smarty->assign('folderscount', $c);
$c = 0;

foreach ($files as $f) {
    $oneset = new stdClass();
    $oneset->dir = false;
    $fp = $pathnow . DIRECTORY_SEPARATOR . $f;
    $encf = rawurlencode($f);

    $is_link = is_link($fp);
    $oneset->is_link = $is_link;
    $oneset->realpath = $is_link ? readlink($fp) : null;
    $oneset->icon = $is_link ? 'if-doc-text' : fm_get_file_icon_class($fp);

    $oneset->path = rawurlencode(trim($FM_PATH . DIRECTORY_SEPARATOR . $f, DIRECTORY_SEPARATOR)); //TODO
    $oneset->link = str_replace(['XXX','YYY'], [$encf, fm_convert_win($f)], $linkview);
    $oneset->name = $f;

    $st = filemtime($fp);
    $oneset->rawtime = $st;
    $dt->setTimestamp($st);
    $oneset->modat = $dt->format($FM_DATETIME_FORMAT);

    $filesize_raw = filesize($fp);
    $total_size += $filesize_raw;
    $oneset->rawsize = $filesize_raw;
    $oneset->size = fm_get_filesize($filesize_raw);

    if (!$FM_IS_WIN) {
        $t = fileperms($fp);
        $perms = [];
        if ($t & 0x0100) $perms[] = $pr;
        if ($t & 0x0080) $perms[] = $pw;
        if ($t & 0x0040) $perms[] = $px; //ignore static flag
        $perms = implode('+',$perms);
        if (!$FM_READONLY) {
            $oneset->perms = str_replace(['XXX','YYY'], [$encf, $perms], $linkchmod);
        } else {
            $oneset->perms = $perms;
        }
    }

    if ($FM_READONLY) {
        $acts = '';
    } else {
        $df = fm_enc($f);
        $acts = str_replace('XXX', $f, $linkdel);
        $acts .= str_replace(['XXX','YYY'], [$f, $df], $linkren);
        $acts .= str_replace(['XXX','YYY'], [$f, $df], $linkcopy);
        $acts .= str_replace(['XXX','YYY'], [$f, $df], $linklink);
    }
    $acts .= str_replace('XXX', $encf, $linkdown);

    $oneset->acts = $acts;

    if (!$FM_READONLY) {
        $oneset->sel = $encf;
    }
    $items[] = $oneset;
    ++$c;
}

$smarty->assign('filescount', $c);
$smarty->assign('totalcount', $total_size);
$smarty->assign('items', $items);

// compression UI

$items = [];
//TODO also check phar-extension availability for some of these
if (class_exists('ZipArchive')) $items['zz'] = ['label' => $this->Lang('arch_zz')];
if (function_exists('gzwrite')) $items['gz'] = ['label' => $this->Lang('arch_gz')];
if (function_exists('bzcompress')) $items['bz'] = ['label' => $this->Lang('arch_bz')];
if (function_exists('xzopen')) $items['xz'] = ['label' => $this->Lang('arch_xz')];
if ($FM_IS_WIN) {
  if (isset($items['zz'])) {
    $items['zz']['check'] = 1;
  }
} else {
  foreach(['bz','gz','zz','xz'] as $t) {
      if (isset($items[$t])) {
          $items[$t]['check'] = 1;
          break;
      }
  }
}
$smarty->assign('archtypes', $items);
if (count($items) > 1) {
    $t = $this->Lang('compress_sel');
}else {
    $t = $this->Lang('compress_typed', reset($items)['label']);
}
$smarty->assign('title_compress', $t);


// page infrastructure

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'upload'=>1]);
$upload_url = rawurldecode(str_replace('&amp;', '&', $u).'&cmsjobtype=1');
//TODO $FM_ROOT_PATH
$here = $FM_PATH;

//<link rel="stylesheet" href="{$baseurl}/lib/css/jquery.dm-uploader.css">
$css = <<<EOS
<link rel="stylesheet" href="{$baseurl}/lib/css/filemanager.css">

EOS;
$this->AdminHeaderContent($css);

$js = <<<EOS
<script src="{$baseurl}/lib/js/jquery.SSsort+metadata.min.js"></script>
<script src="{$baseurl}/lib/js/jquery.treemenu.min.js"></script>
<script src="{$baseurl}/lib/js/jquery.easysearch.js"></script>
<script src="{$baseurl}/lib/js/jquery.dm-uploader.js"></script>
<script>
//<![CDATA[

EOS;
$t = file_get_contents(cms_join_path(__DIR__, 'lib', 'js', 'defaultadmin.inc.js'));
// included js may include variables enclosed in markers '~%' and '%~'.
// like $varname or lang|key or lang|key,param[,param2 ...] Such $varname's must all be 'used' here
$js .= preg_replace_callback('/~%(.+?)%~/', function ($match) use ($id, $upload_url, $here)
{
 $name = $match[1];
 if ($name[0] == '$') {
    $name = substr($name, 1);
    $adbg = $$name;
    return $$name;
 } elseif (strncmp($name,'lang|',5) == 0) {
    $name = substr($name, 5);
    if (strpos($name,',') === false) {
       return $this->Lang($name);
    } else {
       $parts = explode(',',$name);
       return $this->Lang(...$parts);
    }
 } else {
    return '';
 }
}, $t);

$js .= <<<EOS
//]]>
</script>

EOS;
$this->AdminBottomContent($js);

echo $this->ProcessTemplate('defaultadmin.tpl');
