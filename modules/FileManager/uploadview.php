<?php
/*
FileManager module action: defaultadmin - included file for uploads setup
Copyright (C) 2006-2018 Morten Poulsen <morten@poulsen.org>
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

// UPSTREAM
//if (!isset($gCms)) exit;
//if (!$this->CheckPermission('Modify Files')) exit;

$tpl = $smarty->createTemplate($this->GetTemplateResource('uploadview.tpl')); // ,null,null,$smarty);
$tpl->assign('formstart', $this->CreateFormStart($id, 'upload', $returnid, 'post',
 'multipart/form-data', false, '', [
  'disable_buffer'=>'1',
  'path'=>$path,
  ]));
// ->assign('actionid', $id)
// ->assign('maxfilesize', $config['max_upload_size']);

$action_url = $this->create_action_url($id, 'upload');
$refresh_url = $this->create_action_url($id, 'admin_fileview', ['ajax'=>1, 'path'=>$path, 'forjs'=>1, CMS_JOB_KEY=>1]);

$post_max_size = Utils::str_to_bytes(ini_get('post_max_size'));
$upload_max_filesize = Utils::str_to_bytes(ini_get('upload_max_filesize'));
$max_chunksize = min($upload_max_filesize, $post_max_size - 1024);
if (isset($_SERVER['HTTP_USER_AGENT']) && (strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== false)) {
    //some things are not supported on IE browsers
    $tpl->assign('is_ie', 1);
}
$tpl->assign('ie_upload_message', $this->Lang('ie_upload_message'));

/* see filemanager.css
$css = <<<EOS
<style>
/ *.upload-wrapper {
 margin: 10px 0
} * /
.hcentered {
 text-align: center
}
.vcentered {
 display: table-cell;
 vertical-align: middle
}
#dropzone {
 margin: 15px 0;
 border-radius: 4px;
 border: 2px dashed #ccc
}
#dropzone:hover {
 cursor: move
}
#progressarea {
 margin: 15px;
 height: 2em;
 line-height: 2em;
 text-align: center;
 border: 1px solid #aaa;
 border-radius: 4px;
 display: none
}
</style>
EOS;
add_page_headtext($css, false);
*/

$js = <<<EOS
<script type="text/javascript">
//<![CDATA[
function barValue(total, str) {
  $('#progressarea').progressbar({
    value: parseInt(total)
  });
  $('.ui-progressbar-value').html(str);
}

$(function() {
  var _jqXHR = []; // jqXHR array
  var _files = []; // filenames
  // prevent browser default drag/drop handling
  $(document).on('drop dragover', function(e) {
    // prevent default drag/drop stuff.
    e.preventDefault();
  });
  $('#cancel').on('click', function(e) {
    e.preventDefault();
//    aborting = true; //CHECKME
    var ul = $('#fileupload').data('fileupload');
    if(typeof ul !== 'undefined') {
      var data = {};
      data.errorThrown = 'abort';
      ul._trigger('fail', e, data);
    }
  });
  // create our file upload area
  //TODO disable $.ajax cacheing in uploader
   $('#fileupload').fileupload({
    add: function(e, data) {
      _files.push(data.files[0].name);
      _jqXHR.push(data.submit());
    },
    dataType: 'json',
    dropZone: $('#dropzone'),
    maxChunkSize: $max_chunksize,
    start: function(e, data) {
      $('#cancel').show();
      $('#progressarea').show();
    },
//  TODO handler for start of all uploads, to confirm any file/folder replacement
//  send: function (e, data) {}, TODO handler for start of each upload, to confirm any replacement file
    done: function(e, data) {
      _files = [];
      _jqXHR = [];
      //TODO force-redisplay without using browser cache
    },
    fail: function(e, data) {
      $.each(_jqXHR, function(index, obj) {
        if(typeof obj === 'object') {
          obj.abort();
          if(index < _files.length && typeof data.url !== 'undefined') {
            // now delete the file
            var turl = '{$action_url}&' + $.param({ file: _files[index] });
            $.ajax(turl, {
              type: 'DELETE'
            });
          }
        }
      });
      _jqXHR = [];
      _files = [];
      //TODO force-redisplay without using browser cache
    },
    progressall: function(e, data) {
      // overall progress callback
      var perc = (data.loaded / data.total * 100).toFixed(2);
      var total = null;
      total = (data.loaded / data.total * 100).toFixed(0);
      var str = perc + ' %';
      //console.log(total);
      barValue(total, str);
    },
    stop: function(e, data) {
      //TODO force-redisplay without using browser cache
      $('#filesarea').load('$refresh_url');
      $('#cancel').fadeOut();
      $('#progressarea').fadeOut();
    }
  });
});
//]]>
</script>
EOS;
add_page_foottext($js);

$tpl->display();
