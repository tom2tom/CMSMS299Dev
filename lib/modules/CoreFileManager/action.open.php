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

if (!isset($gCms)) exit;
$pdev = $this->CheckPermission('Modify Site Code') || !empty($config['developer_mode']);
if (!($pdev || $this->CheckPermission('Modify Files'))) exit;

global $FM_IS_WIN, $helper;
$FM_IS_WIN = DIRECTORY_SEPARATOR == '\\';
$helper = new \CMSMS\FileTypeHelper($config);

$FM_ROOT_PATH = ($pdev) ? CMS_ROOT_PATH : $config['uploads_path'];
$FM_PATH = $params['p'] ?? '';

$path = $FM_ROOT_PATH;
if ($FM_PATH) {
    $path .= DIRECTORY_SEPARATOR . $FM_PATH;
}
if (!is_dir($path)) { //CHECKME link to a dir ok?
    $path = $FM_ROOT_PATH;
    $FM_PATH = '';
}

//labels for sizing, used downstream
global $bytename, $kbname, $mbname, $gbname; //$tbname
$bytename = $this->Lang('bb');
$kbname = $this->Lang('kb');
$mbname = $this->Lang('mb');
$gbname = $this->Lang('gb');
//$tbname = $this->Lang('tb');

require_once __DIR__.DIRECTORY_SEPARATOR.'function.filemanager.php';

if (isset($params['view'])) {
    $file = fm_clean_path($params['view']);
    $edit = false; //in case of text-display
} elseif (isset($params['edit'])) {
    if (isset($params['cancel'])) {
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
    }
    $file = fm_clean_path($params['edit']);
    $edit = true;
} else {
    $file = ' '.DIRECTORY_SEPARATOR; //trigger error
}

$fullpath = $path . DIRECTORY_SEPARATOR . $file;
if ($file == '' || !is_file($fullpath)) {
    $this->SetError('File not found');
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
}

if ($edit) {
    if (isset($params['apply']) || isset($params['submit'])) {
        $res = file_put_contents($fullpath, $params['content'], LOCK_EX);
        if (isset($params['submit'])) {
			if ($res === false) {
                $this->SetError('File save error'); //TODO Lang()
			}
            $this->Redirect($id, 'defaultadmin', '', ['p'=>$FM_PATH]);
        }
		if ($res === false) {
             $this->ShowErrors('File save error');
		}
    }
}

$is_arch = false;
$is_image = false;
$is_audio = false;
$is_video = false;
$is_text = false;
$filenames = false; // for archive
$content = $params['content'] ?? null; // for text

if ($helper->is_archive($fullpath)) {
    $is_arch = true;
    $type = 'archive';
//    $filenames = fm_get_archive_info($fullpath); //TODO
} elseif ($helper->is_image($fullpath)) {
    $is_image = true;
    $type = 'image';
} elseif ($helper->is_audio($fullpath)) {
    $is_audio = true;
    $type = 'audio';
} elseif ($helper->is_video($fullpath)) {
    $is_video = true;
    $type = 'video';
} elseif ($helper->is_text($fullpath)) {
    $is_text = true;
    $type = 'text';
    if ($content === null) $content = file_get_contents($fullpath);
} else {
    $type = 'file';
}
$smarty->assign('ftype', $type);

$file_url = cms_admin_utils::path_to_url($fullpath);
$smarty->assign('file_url', $file_url);

$items = [];
$items[] = '<a href="?p={urlencode($FM_PATH)}&ampdl={urlencode($file)}"><i class="if-download" title="'.$this->Lang('download').'"></i></a>';
/* TODO
if (!$FM_READONLY && $is_arch) {
    $zip_name = pathinfo($fullpath, PATHINFO_FILENAME);
    $items[] = '<a href="?p={urlencode($FM_PATH)}&ampunzip={urlencode($file)}"><i class="if-resize-full" title="'.$this->Lang('expand').'"></i></a>'
}
*/
if (/*!$FM_READONLY && */$pdev && $is_text && !$edit) {
    $items[] = '<a href="?p={urlencode(trim($FM_PATH))}&ampedit={urlencode($file)}"><i class="if-edit" title="'.$this->Lang('edit').'"></i></a>';
}
$smarty->assign('acts', $items);

$items = [];
$items[$this->Lang($type)] = fm_enc($file);
$items[$this->Lang('info_path')] = ($FM_PATH) ? fm_enc(fm_convert_win($FM_PATH)) : $this->Lang('top');
$filesize = filesize($fullpath);
$items[$this->Lang('info_size')] = fm_get_filesize($filesize);
$items[$this->Lang('info_mime')] = fm_get_mime_type($fullpath);

if ($is_arch && $filenames) {
    $total_files = 0;
    $total_uncomp = 0;
    foreach ($filenames as $fn) {
        if (!$fn['folder']) {
            ++$total_files;
        }
        $total_uncomp += $fn['filesize'];
    }
    $items[$this->Lang('info_archcount')] = $total_files;
    $items[$this->Lang('info_archsize')] = fm_get_filesize($total_uncomp);
} elseif ($is_image) {
    $image_size = getimagesize($fullpath);
    if (!empty($image_size[0]) || !empty($image_size[1])) {
        $items[$this->Lang('info_image')] = ($image_size[0] ?? '0') . ' x ' . ($image_size[1] ?? '0');
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

$baseurl = $this->GetModuleURLPath();
$css = <<<EOS
<link rel="stylesheet" href="{$baseurl}/lib/css/filemanager.css">

EOS;
$this->AdminHeaderContent($css);

if ($is_text) {
    if ($edit) {
        $fixed = 'false';
        $smarty->assign('edit', 1);
        $smarty->assign('start_form', $this->CreateFormStart($id, 'open', $returnid, 'post', '', false, '',
            ['p'=>$FM_PATH, 'edit'=>$params['edit']]));
        $smarty->assign('reporter', CmsFormUtils::create_input([
         'type'=>'textarea',
         'name'=>'content',
         'modid'=>$id,
         'htmlid'=>'reporter',
         'style'=>'display:none;',
        ]));
    } else {
        $fixed = 'true';
    }

    $style = cms_userprefs::get_for_user(get_userid(false),'editortheme','');
    if (!$style) {
        $style = $this->GetPreference('editortheme', 'clouds');
    }
    $style = strtolower($style);

    $js = <<<EOS
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ext-modelist.js"></script>
<script type="text/javascript">
//<![CDATA[
var editor = ace.edit("Editor");
(function () {
 var modelist = ace.require("ace/ext/modelist");
 var mode = modelist.getModeForPath("{$fullpath}").mode;
 editor.session.setMode(mode);
}());
editor.setOptions({
 readOnly: $fixed,
 autoScrollEditorIntoView: true,
 showPrintMargin: false,
 maxLines: Infinity,
 fontSize: '100%'
});
editor.renderer.setOptions({
 showGutter: false,
 displayIndentGuides: false,
 showLineNumbers: false,
 theme: "ace/theme/{$style}"
});

EOS;
    if ($edit) {
        $js .= <<<EOS
$(document).ready(function() {
 $('form').on('submit', function(ev) {
  $('#reporter').val(editor.session.getValue());
 });
});

EOS;
     }
     $js .= <<<EOS
//]]>
</script>

EOS;
    $this->AdminBottomContent($js);
} //is text

$smarty->assign('content', $content);

echo $this->ProcessTemplate('open.tpl');
