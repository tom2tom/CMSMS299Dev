<?php
# CoreFileManager module action: view or edit or display properties of a file
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

if (!function_exists('cmsms')) exit;

global $FM_IS_WIN;
$FM_IS_WIN = DIRECTORY_SEPARATOR == '\\';

//labels for sizing, used downstream
global $bytename, $kbname, $mbname, $gbname; //$tbname
$bytename = $this->Lang('bb');
$kbname = $this->Lang('kb');
$mbname = $this->Lang('mb');
$gbname = $this->Lang('gb');
//$tbname = $this->Lang('tb');
//$smarty->assign('bytename', $bytename);

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

//here we assume that simple-plugin processing is handled elsewhere, or else
//simple-plugins storage is somewhere in, or linked into, the uploads dir
$root = (!empty($config['developer_mode'])) ? CMS_ROOT_PATH : $config['uploads_path'];
//TODO maybe and/or some permission e.g. 'Manage Sitecode'
$relpath = $params['p'];
$dir_path = ($relpath) ? cms_join_path($root, $relpath) : $root;

if (isset($params['view'])) {
    $file = fm_clean_path($params['view']);
//    $file = str_replace(DIRECTORY_SEPARATOR, '', $file);
    $file_path = $dir_path . DIRECTORY_SEPARATOR . $file;
    if ($file == '' || !is_file($file_path)) {
        $this->SetError('File not found');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }

    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_type = fm_get_mime_type($file_path);
    $filesize = filesize($file_path);
    $file_url = cms_admin_utils::path_to_url($file_path);

    $is_arch = false;
    $is_image = false;
    $is_audio = false;
    $is_video = false;
    $is_text = false;
    $filenames = false; // for archive
    $content = ''; // for text

    if (in_array($ext, fm_get_archive_exts())) {
        $is_arch = true;
        $type = 'archive';
        $filenames = fm_get_archive_info($file_path);
    } elseif (in_array($ext, fm_get_image_exts())) {
        $is_image = true;
        $type = 'image';
    } elseif (in_array($ext, fm_get_audio_exts())) {
        $is_audio = true;
        $type = 'audio';
    } elseif (in_array($ext, fm_get_video_exts())) {
        $is_video = true;
        $type = 'video';
    } elseif (in_array($ext, fm_get_text_exts()) || strncmp($mime_type, 'text', 4) == 0 || in_array($mime_type, fm_get_text_mimes())) {
        $is_text = true;
        $type = 'text';
        $content = file_get_contents($file_path);
    } else {
        $type = 'file';
    }
    $smarty->assign('ftype', $type);
    $smarty->assign('file_url', $file_url);

    $items = [];
/*
    $items[] = '<a href="?p={$urlencode($FM_PATH)}&amp}dl={$urlencode($file)}"><img downarrow /> Download</a>'
    $items[] = '<a href="{$fm_enc($file_url)}" target="_blank"><i class="if-whatever"></i> Open</a>' ???
    if (!$FM_READONLY && $is_zip && $filenames !== false) {
        $zip_name = pathinfo($file_path, PATHINFO_FILENAME);
        $items[] = '<a href="?p={$urlencode($FM_PATH)}&amp}unzip={$urlencode($file)}"><i class="if-whatever"></i> Expand</a>'
    }
    if (!$FM_READONLY && $is_text) {
        $items[] = '<a href="?p={$urlencode(trim($FM_PATH))}&amp}edit={$urlencode($file)}" class="edit-file">
          <i class="whatever"></i> Edit</a>';
    }
*/
    $smarty->assign('acts', $items);

    $items = [];
    $items[$this->Lang($type)] = fm_enc($file);
    $items[$this->Lang('info_path')] = ($relpath) ? fm_enc(fm_convert_win($relpath)) : $this->Lang('top');
    $items[$this->Lang('info_size')] = fm_get_filesize($filesize);
    $items[$this->Lang('info_mime')] = $mime_type;
    if ($is_arch && $filenames) {
        $total_files = 0;
        $total_uncomp = 0;
/*
   {strip}{if $fn.folder}
    <strong>{fm_enc($fn.name)}</strong>
   {else}
    {fm_enc($fn.name)} ({$fn.filesize})
   {/if}<br />{/strip}
  {/foreach}
*/
        foreach ($filenames as $fn) {
            if (!$fn['folder']) {
                ++$total_files;
            }
            $total_uncomp += $fn['filesize'];
        }
        $items[$this->Lang('info_archcount')] = $total_files;
        $items[$this->Lang('info_archsize')] = fm_get_filesize($total_uncomp);
    } elseif ($is_image) {
        $image_size = getimagesize($file_path);
        if (!empty($image_size[0]) || !empty($image_size[1])) {
            $items[$this->Lang('info_archsize')] = ($image_size[0] ?? '0') . ' x ' . ($image_size[1] ?? '0');
        } else {
            $smarty->assign('setsize', 1); //force svg size
        }
    } elseif ($is_text) {
        if (preg_match("//u", $content)) {
            $enc = 'UTF-8'; // string includes some UTF-8
        } elseif (function_exists('mb_detect_encoding')) {
            $enc = mb_detect_encoding($content, mb_detect_order(), true);
        } else {
            $enc = '?';
        }
        $items[$this->Lang('info_charset')] = $enc;
    }
    $smarty->assign('about', $items);

    if ($is_text) {
        if ($this->GetPreference('highlight', 1)) {
/*
            $style = strtolower($this->GetPreference('highlightstyle', 'default'));
            $out = <<<EOS
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/{$style}.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
<script>
//<![CDATA[
hljs.initHighlightingOnLoad();
//]]>
</script>

EOS;
            $this->AdminHeaderContent($out);
*/
    
            if (empty($ext) || in_array(strtolower($file), fm_get_text_names()) || preg_match('#\.min\.(css|js)$#i', $file)) {
                $hl_class = 'nohighlight';
            } else {
                $specials = [
                'shtml' => 'xml',
                'htaccess' => 'apache',
                'phtml' => 'php',
                'lock' => 'json',
                'svg' => 'xml',
                ];
                $hl_class = (isset($specials[$ext])) ? 'lang-' . $specials[$ext] : 'lang-' . $ext;
            }
        } else {
            $hl_class =  null;
            if (in_array($ext, ['php', 'php4', 'php5', 'phtml', 'phps'])) {
                $content = highlight_string($content, true);
                $smarty->assign('phpstyled', 1);
            } else {
                $content = fm_enc($content);
            }
        }
        $smarty->assign('hl_class', $hl_class);
    } //is text

    $smarty->assign('content', $content);

    echo $this->ProcessTemplate('view.tpl');
    return;
}

if (isset($params['edit'])) {

    if (!$this->CheckPermission('Modify Files')) exit;

    $file = fm_clean_path($params['edit']);
//    $file = str_replace(DIRECTORY_SEPARATOR, '', $file);
    $file_path = $dir_path . DIRECTORY_SEPARATOR . $file;
    if ($file == '' || !is_file($file_path)) {
        $this->SetError('File not found');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }

    $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    $mime_type = fm_get_mime_type($file_path);
    if (!(in_array($ext, fm_get_text_exts()) || strncmp($mime_type, 'text', 4) == 0 || in_array($mime_type, fm_get_text_mimes()))) {
        $this->SetError('File not editable');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }
    $file_url = cms_admin_utils::path_to_url($file_path);
    $smarty->assign('file_url', $file_url);

    $items = [];
    //TODO action-links
    $smarty->assign('acts', $items);

    $items = [];
    $items[$this->Lang('text')] = fm_enc($file);
    $items[$this->Lang('info_path')] = ($relpath) ? fm_enc(fm_convert_win($relpath)) : $this->Lang('top');
    $smarty->assign('about', $items);

    $theme = strtolower($this->GetPreference('editortheme', 'clouds'));
    $out = <<<EOS
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ext-modelist.js"></script>
<script>
//<![CDATA[
var editor = ace.edit("Editor");
(function () {
 var modelist = ace.require("ace/ext/modelist");
 var mode = modelist.getModeForPath("{$file_path}").mode;
 editor.session.setMode(mode);
}());
editor.setTheme("ace/theme/{$theme}");
editor.setOptions({
 autoScrollEditorIntoView: true,
 showPrintMargin: false,
 maxLines: Infinity,
 fontSize: '100%'
});
editor.renderer.setOption("showLineNumbers", false); 

//$(#uploader).val(editor.getSession().getValue()); on-submit ...

function backup(e, t) {
 var n = new XMLHttpRequest(),
     a = "path=" + e + "&file=" + t + "&type=backup&ajax=true";
 return n.open("POST", "", !0), n.setRequestHeader("Content-type", "application/x-www-form-urlencoded"), n.onreadystatechange = function() {
   if(4 == n.readyState && 200 == n.status) cms_alert(n.responseText);
 }, n.send(a), !1;
}
function edit_save(e, t) {
 var n = "ace" == t ? editor.getSession().getValue() : document.getElementById("Editor").value;
 if(n) {
  var a = document.createElement("form");
  a.setAttribute("method", "POST");
  a.setAttribute("action", "");
  var o = document.createElement("textarea");
  o.setAttribute("type", "textarea");
  o.setAttribute("name", "savedata");
  var c = document.createTextNode(n);
  o.appendChild(c);
  a.appendChild(o);
  document.body.appendChild(a);
  a.submit();
 }
}
//]]>
</script>

EOS;
    $this->AdminBottomContent($out);

    echo $this->ProcessTemplate('edit.tpl');
    return;
}

// should never reach here
$this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
