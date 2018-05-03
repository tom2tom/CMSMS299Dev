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
This action applies a CMSMS UI to H3K | Tiny File Manager
See https://github.com/prasathmani/tinyfilemanager
License: GPL3
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

/* TODO get/use these properties
$use_highlightjs = $pdev && $this->GetPreference('syntaxhighlight', 1);
$highlightjs_style = $this->GetPreference('highlightstyle', 'Vs');
$upload_extensions = ($pdev) ? '' : //everything
    'svg,png,gif,txt,pdf,htm,html' ;
*/
/* tinyfilemanager parameters - not necessarily relevant here */

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

global $bytename, $kbname, $mbname, $gbname; //$tbname
$bytename = $this->Lang('bb');
$kbname = $this->Lang('kb');
$mbname = $this->Lang('mb');
$gbname = $this->Lang('gb');
//$tbname = $this->Lang('tb');

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

//TODO
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

/*
echo '<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">';
if (isset($_GET['view']) && $FM_USE_HIGHLIGHTJS) {
 echo '<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.2.0/styles/';
 echo $FM_HIGHLIGHTJS_STYLE ;
 echo '.min.css">';
}

//popup dialogs
echo '
<div id="wrapper">
<div id="createNewItem" class="modalDialog"><div class="model-wrapper"><a href="#close" title="Close" class="close">X</a>
<h2>Create New Item</h2>
<p>
 <label for="newfile">Item Type &nbsp; : </label><input type="radio" name="newfile" id="newfile" value="file">File <input type="radio" name="newfile" value="folder" checked> Folder<br><label for="newfilename">Item Name : </label><input type="text" name="newfilename" id="newfilename" value=""><br>
 <input type="submit" name="submit" class="group-btn" value="Create New" onclick="newfolder(';
  echo fm_enc($FM_PATH) ; echo ');return false;">
</p>
</div></div>
<div id="searchResult" class="modalDialog">
<div class="model-wrapper"><a href="#close" title="Close" class="close">X</a>
<input type="search" name="search" value="" placeholder="Find item in current folder...">
<h2>Search Results</h2>
<div id="searchresultWrapper"></div>
</div>
</div>
';
*/

$smarty->assign('mod', $this);
$smarty->assign('actionid', $id);
$smarty->assign('form_start', $this->CreateFormStart($id, 'fileaction', $returnid, 'post', '', false, '', ['p'=> rawurlencode($FM_PATH)]));
$smarty->assign('FM_IS_WIN', $FM_IS_WIN);
$smarty->assign('FM_READONLY', $FM_READONLY);

$smarty->assign('pointer', '&rarr;'); //or '&larr;' for 'rtl'
$smarty->assign('crumbjoiner', 'if-angle-double-right'); //or 'if-angle-double-left' for 'rtl'
$smarty->assign('browse', $this->Lang('browse'));
//if($FM_TREEVIEW) {
    $t = fm_dir_tree($FM_ROOT_PATH, (($FM_PATH) ? $pathnow : ''));
    $smarty->assign('treeview', $t);
//}

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

$posix = function_exists('posix_getpwuid') && function_exists('posix_getgrgid');
$themeObject = cms_utils::get_theme_object();
$baseurl = $this->GetModuleURLPath();

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'view'=>'XXX']);
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
$linkren = '<a href="javascript:rename(\'' . fm_enc($FM_PATH) .'\',\'XXX\')">'.$icon.'</a>'."\n";

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'copy'=>'XXX']);
$icon = $themeObject->DisplayImage('icons/system/copy.gif', $this->Lang('copytip'), '', '', 'systemicon');
$linkcopy = '<a href="'. $u .'">'.$icon.'</a>'."\n";

$t = $this->Lang('linktip');
$icon = '<img src="'.$baseurl.'/images/link.png" class="systemicon" alt="'.$t.'" title="'.$t.'" />';
$linklink = '<a href="XXX" target="_blank">'.$icon.'</a>'."\n";

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'dl'=>'XXX']);
$icon = $themeObject->DisplayImage('icons/system/arrow-d.gif', $this->Lang('download'), '', '', 'systemicon');
$linkdown = '<a href="'. $u .'">'.$icon.'</a>'."\n";

$items = [];
$c = 0;
foreach ($folders as $f) {
    $oneset = new stdClass();
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
    $oneset->name = null;

    $oneset->size = ''; //no size-display for a folder
    $dt->setTimestamp(filemtime($fp));
    $oneset->modat = $dt->format($FM_DATETIME_FORMAT);

    if (!$FM_IS_WIN) {
        $perms = substr(decoct(fileperms($fp)), -4);
        if (!$FM_READONLY) {
            $oneset->perms = str_replace(['XXX', 'YYY'], [$encf, $perms], $linkchmod);
        } else {
            $oneset->perms = $perms;
        }

        if ($posix) {
            $owner = posix_getpwuid(fileowner($fp));
            $group = posix_getgrgid(filegroup($fp));
            $oneset->owner = fm_enc($owner['name'] . ':' . $group['name']);
        } else {
            $oneset->owner = '?';
        }
    }

    if ($FM_READONLY) {
        $acts = '';
    } else {
        $acts = str_replace('XXX', $encf, $linkdel);
        $acts .= str_replace('XXX', fm_enc($f), $linkren);
        $acts .= str_replace('XXX', rawurlencode(trim($FM_PATH . DIRECTORY_SEPARATOR . $f, DIRECTORY_SEPARATOR)), $linkcopy);
    }
    $acts .= str_replace('XXX', fm_enc($FM_ROOT_PATH . ($FM_PATH ? DIRECTORY_SEPARATOR . $FM_PATH : '') . DIRECTORY_SEPARATOR . $f), $linklink);

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
    $fp = $pathnow . DIRECTORY_SEPARATOR . $f;
    $encf = rawurlencode($f);

    $is_link = is_link($fp);
    $oneset->is_link = $is_link;
    $oneset->realpath = $is_link ? readlink($fp) : null;
    $oneset->icon = $is_link ? 'if-doc-text' : fm_get_file_icon_class($fp);

    $oneset->path = rawurlencode(trim($FM_PATH . DIRECTORY_SEPARATOR . $f, DIRECTORY_SEPARATOR)); //TODO
    $oneset->link = str_replace(['XXX','YYY'], [$encf, fm_convert_win($f)], $linkview);
    $oneset->name = null;

    $dt->setTimestamp(filemtime($fp));
    $oneset->modat = $dt->format($FM_DATETIME_FORMAT);

    $filesize_raw = filesize($fp);
    $total_size += $filesize_raw;
    $oneset->rawsize = $filesize_raw;
    $oneset->size = fm_get_filesize($filesize_raw);

    if (!$FM_IS_WIN) {
        $perms = substr(decoct(fileperms($fp)), -4);
        if (!$FM_READONLY) {
            $oneset->perms = str_replace(['XXX','YYY'], [$encf, $perms], $linkchmod);
        } else {
            $oneset->perms = $perms;
        }

        if ($posix) {
            $owner = posix_getpwuid(fileowner($fp));
            $group = posix_getgrgid(filegroup($fp));
            $oneset->owner = fm_enc($owner['name'] . ':' . $group['name']);
        } else {
            $oneset->owner = '?';
        }
    }

    if ($FM_READONLY) {
        $acts = '';
    } else {
        $acts = str_replace('XXX', $encf, $linkdel);
        $acts .= str_replace('XXX', fm_enc($f), $linkren);
        $acts .= str_replace('XXX', rawurlencode(trim($FM_PATH . DIRECTORY_SEPARATOR . $f, DIRECTORY_SEPARATOR)), $linkcopy);
    }
    $acts .= str_replace('XXX', fm_enc($FM_ROOT_PATH . ($FM_PATH ? DIRECTORY_SEPARATOR . $FM_PATH : '') . DIRECTORY_SEPARATOR . $f), $linklink);
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
$smarty->assign('bytename', $bytename);

$u = $this->create_url($id, 'fileaction', $returnid, ['p'=>$FM_PATH, 'upload'=>1]);
$upload_url = rawurldecode(str_replace('&amp;', '&', $u));

/*
$this->AdminHeaderContent('<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">');
$baseurl = $this->GetModuleURLPath().'/lib/css/';
$css = <<<EOS
<link rel="stylesheet" href="{$baseurl}font-awesome.min.css">
<link rel="stylesheet" href="{$baseurl}tinyfilemanager.css">

EOS;
*/
//<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
//<link rel="stylesheet" href="{$baseurl}fontawesome-4.7.css">
//$baseurl .= '/lib/css/';
//merged <link rel="stylesheet" href="{$baseurl}/lib/css/jquery.treemenu.css">
$css = <<<EOS
<link rel="stylesheet" href="{$baseurl}/lib/css/filemanager.css">
<link rel="stylesheet" href="{$baseurl}/lib/css/jquery.dm-uploader.css">

EOS;
$this->AdminHeaderContent($css);

$js = '';

if (isset($_GET['view']) && $FM_USE_HIGHLIGHTJS) {
    $js .= <<<'EOS'
<script src="//cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
<script>hljs.initHighlightingOnLoad();</script>
EOS;
}

if (isset($_GET['edit']) && isset($_GET['env']) && $FM_EDIT_FILE) {
    $js .= <<<'EOS'
<script src="//cdnjs.cloudflare.com/ajax/libs/ace/1.2.9/ace.js"></script>
<script>
//<![CDATA[
var editor = ace.edit("editor");
editor.getSession().setMode("ace/mode/javascript");
//]]>
</script>
EOS;
}

$js .= <<<EOS
<script src="{$baseurl}/lib/js/jquery.treemenu.js"></script>
<script src="{$baseurl}/lib/js/jquery.dm-uploader.js"></script>
<script>
//<![CDATA[
$(document).ready(function() {
 $('.fm-tree').treemenu({
  delay: 300,
  closeOther: true,
  activeSelector: 'active',
  openActive: true
 });
});

EOS;

/*
//if ($FM_TREEVIEW) {
    $js .= <<<'EOS'
function init_php_file_tree() {
  if(document.getElementsByTagName) {
    for(var e = document.getElementsByTagName("LI"), t = 0; t < e.length; t++) {
      var n = e[t].className;
      if(n.indexOf("pft-directory") > -1) {
        for(var a = e[t].childNodes, o = 0; o < a.length; o++) {
          if("A" == a[o].tagName) {
            a[o].onclick = function() {
              for(var e = this.nextSibling;;) {
                if(null === e) return !1;
                if("UL" == e.tagName) {
                  var t = "none" == e.style.display;
                  return e.style.display = t ? "block" : "none", this.className = t ? "open" : "closed", !1;
                }
                e = e.nextSibling;
              }
              return !1;
            };
            a[o].className = n.indexOf("open") > -1 ? "open" : "closed"
          }
          if("UL" == a[o].tagName) a[o].style.display = n.indexOf("open") > -1 ? "block" : "none";
        }
      }
    }
    return !1;
  }
}
window.onload = init_php_file_tree;
if(document.getElementById("file-tree-view")) {
  var tableViewHt = document.getElementById("main-table").offsetHeight - 2;
  document.getElementById("file-tree-view").setAttribute("style", "height:" + tableViewHt + "px");
}

EOS;
//}
*/

$js .= <<<EOS
function newfolder(e) {
  var t = document.getElementById("newfilename").value,
    n = document.querySelector('input[name="newfile"]:checked').value;
  if(null !== t && '' !== t && n) {
    window.location.hash = "#";
    window.location.search = "p=" + encodeURIComponent(e) + "&new=" + encodeURIComponent(t) + "&type=" + encodeURIComponent(n);
  }
}
function rename(e, t) {
  cms_prompt('{$this->Lang('newname')}', t).done(function(n) {
    if(null !== n && "" !== n && n != t) {
      window.location.search = "p=" + encodeURIComponent(e) + "&ren=" + encodeURIComponent(t) + "&to=" + encodeURIComponent(n);
    }
  });
}
function compressclick(el) {
  if (any_check()) {
    //TODO full dialog with compression-types radio
    cms_confirm_btnclick(el, '{$this->Lang('zip_confirm')}');
  }
  return false;
}
function deleteclick(el) {
  if (any_check()) {
    cms_confirm_btnclick(el, '{$this->Lang('delete_confirm')}');
  }
  return false;
}
function any_check() {
  var e = get_checkboxes();
  for(var n = e.length - 1; n >= 0; n--) {
    if (e[n].checked) return true;
  }
  return false;
}
function change_checkboxes(e, t) {
  for(var n = e.length - 1; n >= 0; n--) e[n].checked = "boolean" == typeof t ? t : !e[n].checked;
}
function get_checkboxes() {
  for(var e = document.getElementsByName("{$id}file[]"), t = [], n = e.length - 1; n >= 0; n--)(e[n].type = "checkbox") && t.push(e[n]);
  return t;
}
function checkall_toggle(btn) {
  change_checkboxes(get_checkboxes(), btn.checked);
}
function checkbox_toggle() {
  var e = get_checkboxes();
  e.push(this), change_checkboxes(e);
}
function select_all() {
  change_checkboxes(get_checkboxes(), !0);
  var btn = document.getElementById("checkall");
  btn.checked = !0;
}
function unselect_all() {
  change_checkboxes(get_checkboxes(), !1);
  var btn = document.getElementById("checkall");
  btn.checked = !1;
}
function invert_all() {
  change_checkboxes(get_checkboxes());
}
function backup(e, t) {
  var n = new XMLHttpRequest(),
    a = "path=" + e + "&file=" + t + "&type=backup&ajax=true";
  return n.open("POST", "", !0), n.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), n.onreadystatechange = function() {
    if(4 == n.readyState && 200 == n.status) alert(n.responseText);
  }, n.send(a), !1;
}
function edit_save(e, t) {
  var n = "ace" == t ? editor.getSession().getValue() : document.getElementById("normal-editor").value;
  if(n) {
    var a = document.createElement("form");
    a.setAttribute("method", "POST"), a.setAttribute("action", "");
    var o = document.createElement("textarea");
    o.setAttribute("type", "textarea"), o.setAttribute("name", "savedata");
    var c = document.createTextNode(n);
    o.appendChild(c), a.appendChild(o), document.body.appendChild(a), a.submit();
  }
}
function doUpload(url) {
  var e = $('#upload_dlg');
  //onetime only ...
  e.dmUploader({
    url: '{$upload_url}'
/*    maxFileSize: 3000000, // 3 Megs
    onInit: function() {
      // Plugin is ready to use
     console.log('Callback: Plugin initialized');
    },
    onComplete: function() {
      // All files in the queue are processed (success or error)
    },
    onBeforeUpload: function(id) {
      // about tho start uploading a file
    },
    onUploadCanceled: function(id) {
      // Happens when a file is directly canceled by the user.
    },
    onUploadProgress: function(id, percent) {
      // Updating file progress
    },
    onUploadSuccess: function(id, data) {
      // A file was successfully uploaded
    },
    onUploadError: function(id, xhr, status, message) {
      // A file upload failed
    },
    onFileSizeError: function(file) {
      // When the file is too big
    },
    onFallbackMode: function() {
      // When the browser doesn't support this plugin
    }
*/
  });
  cms_dialog(e, {
    open: function(ev, ui) {
      cms_equalWidth($('#upload_dlg label.boxchild'));
    },
    modal: true,
    width: 'auto',
    height: 'auto'
  });
}
function doSearch(url) {
  var t = new XMLHttpRequest(),
    n = "path=" + e + "&type=search&ajax=true";
  t.open("POST", "", !0), t.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), t.onreadystatechange = function() {
    if(4 == t.readyState && 200 == t.status) {
      window.searchObj = t.responseText;
      document.getElementById("searchresultWrapper").innerHTML = "";
      window.location.hash = "#searchResult";
    }
  }, t.send(n);
}
function getSearchResult(e, t) {
  var n = [],
    a = [];
  return e.forEach(function(e) {
    "folder" === e.type ? (getSearchResult(e.items, t), e.name.toLowerCase().match(t) && n.push(e)) : "file" === e.type && e.name.toLowerCase().match(t) && a.push(e);
  }), {
    folders: n,
    files: a
  };
}

var searchEl = document.querySelector("input[type=search]"),
  timeout = null;
searchEl.onkeyup = function(e) {
  clearTimeout(timeout);
  var t = JSON.parse(window.searchObj),
    n = document.querySelector("input[type=search]").value;
  timeout = setTimeout(function() {
    if(n.length >= 2) {
      var e = getSearchResult(t, n),
        a = "",
        o = "";
      e.folders.forEach(function(e) {
        a += '<li class="' + e.type + '"><a href="?p=' + e.path + '">' + e.name + "</a></li>";
      }), e.files.forEach(function(e) {
        o += '<li class="' + e.type + '"><a href="?p=' + e.path + "&view=" + e.name + '">' + e.name + "</a></li>";
      }), document.getElementById("searchresultWrapper").innerHTML = '<div class="model-wrapper">' + a + o + "</div>";
    }
  }, 500);
};
//]]>
</script>
EOS;

$this->AdminBottomContent($js);

echo $this->ProcessTemplate('defaultadmin.tpl');
