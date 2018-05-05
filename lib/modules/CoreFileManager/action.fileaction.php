<?php
# CoreFileManager module action: handles various operations
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
if (!$this->CheckPermission('Modify Files')) exit;

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
$path = ($relpath) ? cms_join_path($root, $relpath) : $root;

if (isset($params['view'])) {
    $file = fm_clean_path($params['view']);
    $file = str_replace(DIRECTORY_SEPARATOR, '', $file);
    $file_path = $path . DIRECTORY_SEPARATOR . $file;
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
        $filenames = fm_get_zif_info($file_path); //TODO any supported archive
    } elseif (in_array($ext, fm_get_image_exts())) {
        $is_image = true;
        $type = 'image';
    } elseif (in_array($ext, fm_get_audio_exts())) {
        $is_audio = true;
        $type = 'audio';
    } elseif (in_array($ext, fm_get_video_exts())) {
        $is_video = true;
        $type = 'video';
    } elseif (in_array($ext, fm_get_text_exts()) || substr($mime_type, 0, 4) == 'text' || in_array($mime_type, fm_get_text_mimes())) {
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
        $zip_name = pathinfo($path, PATHINFO_FILENAME);
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
    $items['Site path'] = ($relpath) ? fm_enc(fm_convert_win($relpath)) : $this->Lang('top');
    $items['File size'] = fm_get_filesize($filesize);
    $items['MIME-type'] = $mime_type;
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
        $items['Files in archive'] = $total_files;
        $items['Total size'] = fm_get_filesize($total_uncomp);
    } elseif ($is_image) {
        $image_size = getimagesize($file_path);
        if (!empty($image_size[0]) || !empty($image_size[1])) {
            $items['Image dimesions'] = ($image_size[0] ?? '0') . ' x ' . ($image_size[1] ?? '0');
        } else {
            $smarty->assign('setsize', 1); //svg
        }
    } elseif ($is_text) {
        $enc = '?';
        if (preg_match("//u", $content)) {
            // string includes some UTF-8
            $enc = 'UTF-8';
        } elseif (function_exists('mb_detect_encoding')) {
            $enc = mb_detect_encoding($content, mb_detect_order(), true);
        } else {
            $enc = '?';
        }
        $items['Text encoding'] = $enc;
    }
    $smarty->assign('about', $items);

    $FM_USE_HIGHLIGHTJS = $this->GetPreference('syntaxhighlight', 1);
    if ($FM_USE_HIGHLIGHTJS) {
        $style = strtolower($this->GetPreference('highlightstyle', 'default'));
        $js = <<<EOS
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/styles/{$style}.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/highlight.js/9.12.0/highlight.min.js"></script>
<script>
//<![CDATA[
hljs.initHighlightingOnLoad();
//]]>
</script>

EOS;
        $this->AdminHeaderContent($js);

        if (empty($ext) || in_array(strtolower($file), fm_get_text_names()) || preg_match('#\.min\.(css|js)$#i', $file)) {
            $hl_class = 'nohighlight';
        } else {
            $hl_classes = [
            'shtml' => 'xml',
            'htaccess' => 'apache',
            'phtml' => 'php',
            'lock' => 'json',
            'svg' => 'xml',
            ];
            $hl_class = (isset($hl_classes[$ext])) ? 'lang-' . $hl_classes[$ext] : 'lang-' . $ext;
        }
    } else {
        if (in_array($ext, ['php', 'php4', 'php5', 'phtml', 'phps'])) {
            $content = highlight_string($content, true);
            $smarty->assign('phpstyled', 1);
        } else {
            $content = fm_enc($content);
        }
        $hl_class =  null;
    }
    $smarty->assign('hl_class', $hl_class);
    $smarty->assign('content', $content);

    echo $this->ProcessTemplate('view.tpl');
    return;
}

if (isset($params['edit'])) {
    $js = <<<'EOS'
<script src="//cdnjs.cloudflare.com/ajax/libs/ace/1.2.9/ace.js"></script>
<script>
//<![CDATA[
var editor = ace.edit('editor');
editor.getSession().setMode('ace/mode/javascript');
//]]>
</script>

EOS;
    $this->AdminHeaderContent($css);

    echo $this->ProcessTemplate('edit.tpl');
    return;
}

if (isset($params['ajax'])) {
    //AJAX request
    if (isset($params['type']) && $params['type']=='search') {
        //get list of items in the current folder
        $response = scan($path);
        echo json_encode($response);
    } elseif (isset($params['type']) && $params['type']=='backup') {
        //backup files
        $file = $params['file'];
        $date = date('Ymd-His');
        $newFile = $file.'-'.$date.'.bak';
        if (copy($path.DIRECTORY_SEPARATOR.$file, $path.DIRECTORY_SEPARATOR.$newFile)) {
            echo "Backup $newFile Created"; //TODO $this->Lang('', $newfile);
        } else {
            echo 'Unable to backup';
        }
    }
    exit;
}

if (!empty($_FILES)) {
    // Upload
    $f = $_FILES;

    $errors = 0;
    $uploads = 0;
    $total = count($f['file']['name']);
    $allowed = (FM_EXTENSION) ? explode(',', FM_EXTENSION) : false;

    $filename = $f['file']['name'];
    $tmp_name = $f['file']['tmp_name'];
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    $isFileAllowed = ($allowed) ? in_array($ext, $allowed) : true;

    if (empty($f['file']['error']) && !empty($tmp_name) && $tmp_name != 'none' && $isFileAllowed) {
        if (move_uploaded_file($tmp_name, $path . DIRECTORY_SEPARATOR . $f['file']['name'])) {
            echo 'Successfully uploaded';
        } else {
            echo sprintf('Error while uploading files. Uploaded files: %s', $uploads);
        }
    }
    exit;
}

if (isset($params['delete'])) {
    if (isset($params['group'])) {  //TODO some array
        // Mass delete
        $errors = 0;
        $files = $params['file'];
        if (is_array($files) && count($files)) {
            foreach ($files as $f) {
                if ($f != '') {
                    $new_path = $path . DIRECTORY_SEPARATOR . $f;
                    if (!fm_rdelete($new_path)) {
                        $errors++;
                    }
                }
            }
            if ($errors == 0) {
                $this->SetMessage('Selected file(s) and/or folder(s) deleted');
            } else {
                $this->SetError('Error while deleting items');
            }
        } else {
            $this->SetWarning('Nothing selected');
        }
    } else {
        // Delete file / folder
        $del = fm_clean_path($params['del']);
        $del = str_replace(DIRECTORY_SEPARATOR, '', $del); //??
        if ($del != '' && $del != '..' && $del != '.') {
            $is_dir = is_dir($path . DIRECTORY_SEPARATOR . $del);
            if (fm_rdelete($path . DIRECTORY_SEPARATOR . $del)) {
                $msg = $is_dir ? 'Folder <strong>%s</strong> deleted' : 'File <strong>%s</strong> deleted';
                $this->SetMessage(sprintf($msg, fm_enc($del)));
            } else {
                $msg = $is_dir ? 'Folder <strong>%s</strong> not deleted' : 'File <strong>%s</strong> not deleted';
                $this->SetError(sprintf($msg, fm_enc($del)));
            }
        } else {
            $this->SetError('Wrong file or folder name');
        }
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['new'], $params['type'])) {
    // Create folder
    $new = strip_tags($params['new']);
    $new = fm_clean_path($params['type']);
    $new = str_replace(DIRECTORY_SEPARATOR, '', $new); //??
    if ($new != '' && $new != '..' && $new != '.') {
        if ($params['type']=='file') {
            if (!file_exists($path . DIRECTORY_SEPARATOR . $new)) {
                @fopen($path . DIRECTORY_SEPARATOR . $new, 'w') or die('Cannot open file:  '.$new);
                $this->SetMessage(sprintf('File <strong>%s</strong> created', fm_enc($new)));
            } else {
                $this->SetInfo(sprintf('File <strong>%s</strong> already exists', fm_enc($new)));
            }
        } else {
            if (fm_mkdir($path . DIRECTORY_SEPARATOR . $new, false) === true) {
                $this->SetMessage(sprintf('Folder <strong>%s</strong> created', $new));
            } elseif (fm_mkdir($path . DIRECTORY_SEPARATOR . $new, false) === $path . DIRECTORY_SEPARATOR . $new) {
                $this->SetInfo(sprintf('Folder <strong>%s</strong> already exists', fm_enc($new)));
            } else {
                $this->SetError(sprintf('Folder <strong>%s</strong> not created', fm_enc($new)));
            }
        }
    } else {
        $this->SetError('Wrong folder name');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['copy'], $params['finish'])) {
    // Copy folder / file
    // from
    $copy = fm_clean_path($params['copy']);
    // empty path
    if ($copy == '') {
        $this->SetError('Source path not defined');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }
    // abs path from
    $from = FM_ROOT_PATH . DIRECTORY_SEPARATOR . $copy;
    // abs path to
    $dest = FM_ROOT_PATH;
    if (FM_PATH != '') {
        $dest .= DIRECTORY_SEPARATOR . FM_PATH;
    }
    $dest .= DIRECTORY_SEPARATOR . basename($from);
    // move?
    $move = isset($params['move']);
    // copy/move
    if ($from != $dest) {
        $msg_from = trim(FM_PATH . DIRECTORY_SEPARATOR . basename($from), DIRECTORY_SEPARATOR);
        if ($move) {
            $rename = fm_rename($from, $dest);
            if ($rename) {
                $this->SetMessage(sprintf('Moved from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
            } elseif ($rename === null) {
                $this->SetInfo('File or folder with this path already exists');
            } else {
                $this->SetError(sprintf('Error while moving from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
            }
        } else {
            if (fm_rcopy($from, $dest)) {
                $this->SetMessage(sprintf('Copied from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
            } else {
                $this->SetError(sprintf('Error while copying from <strong>%s</strong> to <strong>%s</strong>', fm_enc($copy), fm_enc($msg_from)));
            }
        }
    } else {
        $this->SetWarn('Paths must be different');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['file'], $params['copy_to'], $params['finish'])) {
    // Mass copy files/folders
    // from $path
    // to
    $copy_to_path = FM_ROOT_PATH;
    $copy_to = fm_clean_path($params['copy_to']);
    if ($copy_to != '') {
        $copy_to_path .= DIRECTORY_SEPARATOR . $copy_to;
    }
    if ($path == $copy_to_path) {
        $this->SetInfo('Paths must be different');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }
    if (!is_dir($copy_to_path)) {
        if (!fm_mkdir($copy_to_path, true)) {
            $this->SetError('Unable to create destination folder');
            $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
        }
    }
    // move?
    $move = isset($params['move']);
    // copy/move
    $errors = 0;
    $files = $params['file'];
    if (is_array($files) && count($files)) {
        foreach ($files as $f) {
            if ($f != '') {
                // abs path from
                $from = $path . DIRECTORY_SEPARATOR . $f;
                // abs path to
                $dest = $copy_to_path . DIRECTORY_SEPARATOR . $f;
                // do
                if ($move) {
                    $rename = fm_rename($from, $dest);
                    if ($rename === false) {
                        $errors++;
                    }
                } else {
                    if (!fm_rcopy($from, $dest)) {
                        $errors++;
                    }
                }
            }
        }
        if ($errors == 0) {
            $msg = $move ? 'Selected files and folders moved' : 'Selected files and folders copied';
            $this->SetMessage($msg);
        } else {
            $msg = $move ? 'Error while moving items' : 'Error while copying items';
            $this->SetError($msg);
        }
    } else {
        $this->SetWarning('Nothing selected');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['ren'], $params['to'])) {
    // Rename
    // old name
    $old = fm_clean_path($params['ren']);
    $old = str_replace(DIRECTORY_SEPARATOR, '', $old); //??
    // new name
    $new = fm_clean_path($params['to']);
    $new = str_replace(DIRECTORY_SEPARATOR, '', $new); //??

    // rename
    if ($old != '' && $new != '') {
        if (fm_rename($path . DIRECTORY_SEPARATOR . $old, $path . DIRECTORY_SEPARATOR . $new)) {
            $this->SetMessage(sprintf('Renamed from <strong>%s</strong> to <strong>%s</strong>', fm_enc($old), fm_enc($new)));
        } else {
            $this->SetError(sprintf('Error while renaming from <strong>%s</strong> to <strong>%s</strong>', fm_enc($old), fm_enc($new)));
        }
    } else {
        $this->SetError('Names not set');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['dl'])) {
    // Download
    $dl = fm_clean_path($params['dl']);
    $dl = str_replace(DIRECTORY_SEPARATOR, '', $dl); //??
    $fp = $path . DIRECTORY_SEPARATOR . $dl;

    if ($dl != '' && is_file($fp)) {
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($fp) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Connection: Keep-Alive');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Pragma: public');
        header('Content-Length: ' . filesize($fp));
        readfile($fp);
        exit;
    } else {
        $this->SetError('File not found');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }
}

if (isset($params['compress'])) {
    // Pack files
    if (isset($params['group'])) {   //TODO some array

    } else {

    }

    if (!class_exists('ZipArchive')) {
        $this->SetError('Operations with archives are not available');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }

    $files = $params['file'];
    if (!empty($files)) {
        chdir($path);

        if (count($files) == 1) {
            $one_file = reset($files);
            $one_file = basename($one_file);
            $zipname = $one_file . '_' . date('Ymd-His') . '.zip';
        } else {
            $zipname = 'archive_' . date('Ymd-His') . '.zip';
        }

        $zipper = new FM_Zipper();
        $res = $zipper->create($zipname, $files);

        if ($res) {
            $this->SetMessage(sprintf('Archive <strong>%s</strong> created', fm_enc($zipname)));
        } else {
            $this->SetError('Archive not created');
        }
    } else {
        $this->SetInfo('Nothing selected');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['decompress'])) {
    // Unpack
    $unzip = fm_clean_path($params['unzip']);
    $unzip = str_replace(DIRECTORY_SEPARATOR, '', $unzip); //??

    // TODO per archive-type
    if (!class_exists('ZipArchive')) {
        $this->SetError('Operations with archives are not available');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }

    $zip_path = $path . DIRECTORY_SEPARATOR . $unzip;
    if ($unzip != '' && is_file($zip_path)) {
        //to folder
        $tofolder = '';
        if (isset($params['tofolder'])) {
            $tofolder = pathinfo($zip_path, PATHINFO_FILENAME);
            if (fm_mkdir($path . DIRECTORY_SEPARATOR . $tofolder, true)) {
                $path .= DIRECTORY_SEPARATOR . $tofolder;
            }
        }

        $zipper = new FM_Zipper();
        $res = $zipper->unzip($zip_path, $path);

        if ($res) {
            $this->SetMessage('Archive unpacked');
        } else {
            $this->SetError('Archive not unpacked');
        }
    } else {
        $this->SetError('File not found');
    }
    $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
}

if (isset($params['chmod']) && !FM_IS_WIN) {
    // Change Perms (not for Windows)
    $file = fm_clean_path($params['chmod']);
    $file = str_replace(DIRECTORY_SEPARATOR, '', $file); //??
    $fp = $path . DIRECTORY_SEPARATOR . $file;
    if ($file == '' || (!(is_file($fp) || is_dir($fp)))) {
        $this->SetError('File not found');
        $this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);
    }

    $mode = 0;
    if (!empty($params['ur'])) {
        $mode |= 0400;
    }
    if (!empty($params['uw'])) {
        $mode |= 0200;
    }
    if (!empty($params['ux'])) {
        $mode |= 0100;
    }
    if (!empty($params['gr'])) {
        $mode |= 0040;
    }
    if (!empty($params['gw'])) {
        $mode |= 0020;
    }
    if (!empty($params['gx'])) {
        $mode |= 0010;
    }
    if (!empty($params['or'])) {
        $mode |= 0004;
    }
    if (!empty($params['ow'])) {
        $mode |= 0002;
    }
    if (!empty($params['ox'])) {
        $mode |= 0001;
    }

    if (@chmod($fp, $mode)) {
        $this->SetMessage('Permissions changed');
    } else {
        $this->SetError('Permissions not changed');
    }
}

$this->Redirect($id, 'defaultadmin', '', ['p'=>$relpath]);

/*

 TODO get/use these properties
$use_highlightjs = $pdev && $this->GetPreference('syntaxhighlight', 1);
$highlightjs_style = $this->GetPreference('highlightstyle', 'Vs');
$upload_extensions = ($pdev) ? '' : //everything
    'svg,png,gif,txt,pdf,htm,html' ;


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



$this->AdminHeaderContent('<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">');
//$baseurl = $this->GetModuleURLPath().'/lib/css/';
$css = <<<EOS
<link rel="stylesheet" href="{$baseurl}font-awesome.min.css">
<link rel="stylesheet" href="{$baseurl}tinyfilemanager.css">

EOS;

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

*/
