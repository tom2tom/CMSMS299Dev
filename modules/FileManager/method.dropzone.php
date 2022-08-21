<?php
/*
FileManager module action: dropzone
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2022 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 3 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use FileManager\Utils;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files')) {
    exit;
}

$cwd = Utils::get_cwd();

if (isset($params['template'])) {
    $template = trim($params['template']);
    if (!endswith($template, '.tpl')) {
        $template .= '.tpl';
    }
} else {
    $template = 'dropzone.tpl';
}
$tpl = $smarty->createTemplate($this->GetTemplateResource($template)); //,null,null,$smarty);

$tpl->assign('formstart', $this->CreateFormStart($id, 'upload', $returnid, 'post', 'multipart/form-data'))
    ->assign('chdir_formstart', $this->CreateFormStart($id, 'changedir', $returnid, 'post', '', false, '', [
  'id' => 'chdir_form',
  'class' => 'cms_form',
  'path' => $cwd,
  'ajax' => 1
  ]))
// ->assign('mod',$this) see DoActionBase()
// ->assign('actionid',$id)
    ->assign('action_url', $this->create_action_url($id, 'upload'))
    ->assign('prompt_dropfiles', $this->Lang('prompt_dropfiles'))
    ->assign('cwd', $cwd);

$upload_max_filesize = Utils::str_to_bytes(ini_get('upload_max_filesize'));
$post_max_size = Utils::str_to_bytes(ini_get('post_max_size'));
$max_chunksize = min($upload_max_filesize, $post_max_size - 1024);

if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    //some things are not supported on IE browsers
    $tpl->assign('is_ie', 1);
} elseif ($template == 'dropzone.tpl') {
    $chdir_url = $this->create_action_url($id, 'changedir', ['forjs' => 1, CMS_JOB_KEY => 1]);
    $js = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(function() {
  $('.drop .dialog').on('dialogopen', function(event, ui) {
    $.get('$chdir_url', function(data) {
      $('#fm_newdir').val('/' + data);
    });
  });
  $('#chdir_form').submit(function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    $.post('$chdir_url', data, function(data, textStatus, jqXHR) {
      // stuff to do on post finishing.
      $('#chdir_form').trigger('dropzone_chdir');
      cms_dialog($('.dialog'), 'close');
    });
    return false;
  });
  // prevent browser default drag/drop handling
  $(document).on('drop dragover', function(e) {
    // prevent default drag/drop stuff.
    e.preventDefault();
    return false;
  });
  var zone = $('#theme_dropzone');
  $('#theme_dropzone_i').fileupload({
    dataType: 'json',
    dropZone: zone,
    maxChunkSize: $max_chunksize,
    progressall: function(e, data) {
      var total = (data.loaded / data.total * 100).toFixed(0);
      zone.progressbar({
        value: parseInt(total)
      });
      $('.ui-progressbar-value').html(total + '%');
    },
    stop: function(e, data) {
      zone.progressbar('destroy');
      zone.trigger('dropzone_stop');
    }
  });
});
//]]>
</script>
EOS;
    add_page_foottext($js);
} else {
    $tpl->assign('max_chunksize', $max_chunksize);
}

$advancedmode = $this->GetPreference('advancedmode', 0);
if (strlen($advancedmode) > 1) {
    $advancedmode = 0;
}

// get a folder list...
$startdir = $config['uploads_path'];
if ($this->AdvancedAccessAllowed() && $advancedmode) {
    $startdir = CMS_ROOT_PATH;
}

// now get a simple list of all of the directories we have 'write' access to.
$basedir = dirname($startdir);
function get_dirs($startdir, $prefix = DIRECTORY_SEPARATOR)
{
    $res = [];
    if (!is_dir($startdir)) {
        return;
    }

    global $showhiddenfiles;
    $dh = opendir($startdir);
    while (false !== ($entry = readdir($dh))) {
        if ($entry == '.') {
            continue;
        }
        if ($entry == '..') {
            continue;
        }
        $full = cms_join_path($startdir, $entry);
        if (!is_dir($full)) {
            continue;
        }
        if (!is_readable($full)) {
            continue;
        }
        if (!$showhiddenfiles && ($entry[0] == '.' || $entry[0] == '_')) {
            continue;
        }

        if ($entry == '.svn' || $entry == '.git') {
            continue;
        }
        if (is_writable($full)) {
            $res[$prefix.$entry] = $prefix.$entry;
        }
        $tmp = get_dirs($full, $prefix.$entry.DIRECTORY_SEPARATOR);
        if ($tmp) {
            $res = array_merge($res, $tmp);
        }
    }
    closedir($dh);
    return $res;
}

$output = get_dirs($startdir, DIRECTORY_SEPARATOR.basename($startdir).DIRECTORY_SEPARATOR);
$output['/'.basename($startdir)] = DIRECTORY_SEPARATOR.basename($startdir);
if ($output) {
    ksort($output);
    $tpl->assign('dirlist', $output);
}

$tpl->display();
