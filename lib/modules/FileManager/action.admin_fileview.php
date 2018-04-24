<?php
#FileManager module action: display list of files
#Copyright (C) 2006-2018 Morten Poulsen <morten@poulsen.org>
#This file is a component of CMS Made Simple <http://dev.cmsmadesimple.org>
#
#This program is free software; you can redistribute it and/or modify
#it under the terms of the GNU General Public License as published by
#the Free Software Foundation; either version 2 of the License, or
#(at your option) any later version.
#
#This program is distributed in the hope that it will be useful,
#but WITHOUT ANY WARRANTY; without even the implied warranty of
#MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#GNU General Public License for more details.
#You should have received a copy of the GNU General Public License
#along with this program. If not, see <https://www.gnu.org/licenses/>.

use FileManager\filemanager_utils;

if (!isset($gCms)) {
  exit;
}

if (!$this->CheckPermission('Modify Files')) {
  exit;
}

$sortby=$this->GetPreference('sortby', 'nameasc');
$path=filemanager_utils::get_cwd();
$filelist=filemanager_utils::get_file_list($path);

$config = $gCms->GetConfig();
$smarty->assign('path', $path);
$smarty->assign('hiddenpath', $this->CreateInputHidden($id, 'path', $path));
$smarty->assign('formstart', $this->CreateFormStart($id, 'fileaction', $returnid));

$themeObject = cms_utils::get_theme_object();
$titlelink = $this->Lang('filename');
$newsort = '';
if ($sortby == 'nameasc') {
  $newsort = 'namedesc';
  $titlelink .= '+';
} else {
  $newsort = 'nameasc';
  if ($sortby == 'namedesc') {
    $titlelink.='-';
  }
}
$params['newsort'] = $newsort;
$titlelink = $this->CreateLink($id, 'defaultadmin', $returnid, $titlelink, $params);
$smarty->assign('filenametext', $titlelink);

$titlelink = $this->Lang('filesize');
$newsort = '';
if ($sortby == 'sizeasc') {
  $newsort = 'sizedesc';
  $titlelink .= '+';
} else {
  $newsort = 'sizeasc';
  if ($sortby == 'sizedesc') {
    $titlelink .= '-';
  }
}
$params['newsort'] = $newsort;
//}
$titlelink = $this->CreateLink($id, 'defaultadmin', $returnid, $titlelink, $params);
$smarty->assign('filesizetext', $titlelink);
$smarty->assign('fileownertext', $this->Lang('fileowner'));
$smarty->assign('filepermstext', $this->Lang('fileperms'));
$smarty->assign('fileinfotext', $this->Lang('fileinfo'));

$smarty->assign('filedatetext', $this->Lang('filedate'));
$smarty->assign('actionstext', $this->Lang('actions'));

$times = count($filelist);
$countdirs = 0;
$countfiles = 0;
$countfilesize = 0;
$files = array();

for ($i = 0; $i < $times; $i++) {
  $onerow = new stdClass();
  if (isset($filelist[$i]['url'])) {
    $onerow->url = $filelist[$i]['url'];
  }
  $onerow->name = $filelist[$i]['name'];
  $onerow->urlname = $this->encodefilename($filelist[$i]['name']);
  $onerow->type = array('file');
  $onerow->mime = $filelist[$i]['mime'];
  if (isset($params[$onerow->urlname])) {
    $onerow->checked = true;
  }

  if (strpos($onerow->mime, 'text') !== false) {
    $onerow->type[] = 'text';
  }

  if ($filelist[$i]['dir']) {
    $urlname='dir_' . $this->encodefilename($filelist[$i]['name']);
    $value='';
    if (isset($params[$urlname])) {
      $value='true';
    }
    $onerow->checkbox = $this->CreateInputCheckBox($id, $urlname, 'true', $value);
  } else {
    $urlname='file_' . $this->encodefilename($filelist[$i]['name']);
    $value='';
    if (isset($params[$urlname])) {
      $value='true';
    }
    $onerow->checkbox = $this->CreateInputCheckBox($id, $urlname, 'true', $value);
  }

  $onerow->thumbnail = '';
  $onerow->editor = '';
  if ($filelist[$i]['image']) {
    $onerow->type[] = 'image';
    $params['imagesrc'] = $path.'/'.$filelist[$i]['name'];
    if ($this->GetPreference('showthumbnails', 0) == 1) {
      $onerow->thumbnail = $this->GetThumbnailLink($filelist[$i], $path);
    }
  }

  if ($filelist[$i]['dir']) {
    $onerow->iconlink = $this->CreateLink(
      $id,
      'changedir',
      '',
      $this->GetFileIcon($filelist[$i]['ext'], $filelist[$i]['dir'],
        ['newdir' => $filelist[$i]['name'], 'path' => $path, 'sortby' => $sortby])
    );
  } else {
    $onerow->iconlink = "<a href='" . $filelist[$i]['url'] . "' target='_blank'>" . $this->GetFileIcon($filelist[$i]['ext']) . '</a>';
  }

  $link = $filelist[$i]['name'];
  if ($filelist[$i]['dir']) {
    $parms = [ 'newdir'=>$filelist[$i]['name'], 'path'=>$path, 'sortby'=>$sortby ];
    $url = $this->create_url($id, 'changedir', '', $parms);
    if ($filelist[$i]['name'] != '..') {
      $countdirs++;
      $onerow->type = array('dir');
      $onerow->txtlink = "<a class=\"dirlink\" href=\"{$url}\" title=\"{$this->Lang('title_changedir')}\">{$link}</a>";
    } else {
      // for the parent directory
      $onerow->noCheckbox = 1;
      $icon = $this->GetModuleURLPath().'/icons/themes/default/actions/dir_up.gif';
      $img_tag = '<img src="'.$icon.'" width="32" height="32" title="'.$this->Lang('title_changeupdir').'" />';
      $onerow->iconlink = $this->CreateLink($id, 'changedir', '', $img_tag, $parms);
      $onerow->txtlink = "<a class=\"dirlink\" href=\"{$url}\" title=\"{$this->Lang('title_changeupdir')}\">{$link}</a>";
    }
  } else {
    $countfiles++;
    $countfilesize+=$filelist[$i]['size'];
    //$url = $this->create_url($id,'view','',array('file'=>$this->encodefilename($filelist[$i]['name'])));
    $url = $onerow->url;
    //$onerow->txtlink = "<a href='" . $filelist[$i]["url"] . "' target='_blank' title=\"".$this->Lang('title_view_newwindow')."\">" . $link . "</a>";
    $onerow->txtlink = "<a class=\"filelink\" href='" . $url . "' target='_blank' title=\"".$this->Lang('title_view_newwindow').'">' . $link . '</a>';
  }
  if ($filelist[$i]['archive']) {
    $onerow->type[] = 'archive';
  }

  $onerow->fileinfo = trim($filelist[$i]['fileinfo']);
  if ($filelist[$i]['name'] == '..') {
    $onerow->fileaction = '&nbsp;';
    $onerow->filepermissions = '&nbsp;';
  } else {
    $onerow->fileowner = $filelist[$i]['fileowner'];
    $onerow->filepermissions = $filelist[$i]['permissions'];
  }
  if ($filelist[$i]['dir']) {
    $onerow->filesize = '&nbsp;';
  } else {
    $filesize = filemanager_utils::format_filesize($filelist[$i]['size']);
    $onerow->filesize = $filesize['size'];
    $onerow->filesizeunit = $filesize['unit'];
  }

  if (!$filelist[$i]['dir']) {
    $onerow->filedate = $filelist[$i]['date'];
  } else {
    $onerow->filedate = '';
  }

  $files[] = $onerow;
}

if (!empty($params['viewfile'])) {
  foreach ($files as $file) {
    if ($file->urlname == $params['viewfile']) {
      $fn = cms_join_path(filemanager_utils::get_full_cwd(), $file->name);
      if (in_array('text', $file->type)) {
        if (file_exists($fn)) {
          $data = @file_get_contents($fn);
        }
        if ($data) {
          $data = cms_htmlentities($data);
          $data = nl2br($data);
          echo $data;
          exit;
        }
      } elseif (in_array('image', $file->type)) {
        $data = '<img src="'.$file->url.'" alt="'.$file->name.'" />';
        echo $data;
        exit;
      }
    }
  }
}

// build display
$smarty->assign('files', $files);
$smarty->assign('itemcount', count($files));
$totalsize = filemanager_utils::format_filesize($countfilesize);
$counts = $totalsize['size'] . ' ' . $totalsize['unit'] . ' ' . $this->Lang('in') . ' ' . $countfiles . ' ';
if ($countfiles == 1) {
  $counts.=$this->Lang('file');
} else {
  $counts.=$this->Lang('files');
}
$counts.=' ' . $this->Lang('and') . ' ' . $countdirs . ' ';
if ($countdirs == 1) {
  $counts.=$this->Lang('subdir');
} else {
  $counts.=$this->Lang('subdirs');
}
$smarty->assign('countstext', $counts);
$smarty->assign('formend', $this->CreateFormEnd());
$smarty->assign('mod', $this);
$smarty->assign('confirm_unpack', $this->Lang('confirm_unpack'));

if (isset($params['noform'])) {
  $smarty->assign('noform', 1);
} else {
  $out = <<<EOS
<style type="text/css">
a.filelink:visited {
 color:#000;
}
</style>
EOS;
  $this->AdminHeaderContent($out);

  $refresh_url = str_replace('&amp;', '&', $this->create_url($id, 'admin_fileview', '', ['noform'=>1]));
  $viewfile_url = str_replace('&amp;', '&', $this->create_url($id, 'admin_fileview', '', ['ajax'=>1]));
  $out = <<<EOS
<script type="text/javascript">
//<![CDATA[
function enable_button(idlist) {
  $(idlist).removeAttr('disabled').removeClass('ui-state-disabled ui-button-disabled');
}
function disable_button(idlist) {
  $(idlist).attr('disabled', 'disabled').addClass('ui-state-disabled ui-button-disabled');
}
function enable_action_buttons() {
  var files = $("#filesarea input[type='checkbox'].fileselect").filter(':checked').length,
    dirs = $("#filesarea input[type='checkbox'].dir").filter(':checked').length,
    arch = $("#filesarea input[type='checkbox'].archive").filter(':checked').length,
    text = $("#filesarea input[type='checkbox'].text").filter(':checked').length,
    imgs = $("#filesarea input[type='checkbox'].image").filter(':checked').length;
  disable_button('button.filebtn');
  $('button.filebtn').attr('disabled', 'disabled');
  if(files === 0 && dirs === 0) {
    // nothing selected, enable anything with select_none
    enable_button('#btn_newdir');
  } else if(files == 1) {
    // 1 selected, enable anything with select_one
    enable_button('#btn_rename');
    enable_button('#btn_move');
    enable_button('#btn_delete');
    if(dirs === 0) enable_button('#btn_copy');
    if(arch == 1) enable_button('#btn_unpack');
    if(imgs == 1) enable_button('#btn_view,#btn_thumb,#btn_resizecrop,#btn_rotate');
    if(text == 1) enable_button('#btn_view');
  } else if(files > 1 && dirs === 0) {
    // multiple files selected
    enable_button('#btn_delete,#btn_copy,#btn_move');
  } else if(files > 1 && dirs > 0) {
    // multiple selected, at least one dir
    enable_button('#btn_delete,#btn_move');
  }
}

$(document).ready(function() {
  enable_action_buttons();
  $('#refresh').off('click').on('click', function() {
    // ajaxy reload for the files area.
	$('#filesarea').load('{$refresh_url}&cmsjobtype=1');
    return false;
  });
  $(document).on('dropzone_chdir', $(this), function(e, data) {
    // if change dir via the dropzone, make sure filemanager refreshes.
    location.reload();
  });
  $(document).on('dropzone_stop', $(this), function(e, data) {
    // if change dir via the dropzone, make sure filemanager refreshes.
    location.reload();
  });
  $('#filesarea input[type="checkbox"].fileselect').on('change', function(e) {
    // find the parent row
    var t = $(this).attr('checked');
    if(t) {
      $(this).closest('tr').addClass('selected');
    } else {
      $(this).closest('tr').removeClass('selected');
    }
    enable_action_buttons();
    return false;
  });
  $('#tagall').on('change', function() {
    if($(this).is(':checked')) {
      $('#filesarea input:checkbox.fileselect').attr('checked', true).trigger('change');
    } else {
      $('#filesarea input:checkbox.fileselect').attr('checked', false).trigger('change');
    }
  });
  $('#btn_view').on('click', function() {
    // find the selected item.
    var tmp = $("#filesarea input[type='checkbox']").filter(':checked').val();
    var url = '{$viewfile_url}&cmsjobtype=1&{$id}viewfile=' + tmp;
    url = url.replace(/amp;/g, '');
    $('#popup_contents').load(url);
    cms_dialog($('#popup'), {
      minWidth: 380,
      maxHeight: 600
    });
    return false;
  });
  $('td.clickable').on('click', function() {
    var t = $(this).parent().find(':checkbox').attr('checked');
    if(t !== 'checked') {
      $(this).parent().find(':checkbox').attr('checked', true).trigger('change');
    } else {
      $(this).parent().find(':checkbox').attr('checked', false).trigger('change');
    }
  });
});
//]]>
</script>
EOS;
  $this->AdminBottomContent($out);
}

echo $this->ProcessTemplate('filemanager.tpl');
