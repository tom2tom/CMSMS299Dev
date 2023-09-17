<?php
/*
FileManager module action: resizecrop
Copyright (C) 2006-2008 Morten Poulsen <morten@poulsen.org>
Copyright (C) 2018-2023 CMS Made Simple Foundation <foundation@cmsmadesimple.org>

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

use CMSMS\Lone;
use FileManager\Utils;
use FileManager\ImageEditor;

//if (some worthy test fails) exit;
if (!$this->CheckPermission('Modify Files') && !$this->AdvancedAccessAllowed()) {
    exit;
}
if (isset($params['cancel'])) {
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$sel = $params['sel'];
if (!is_array($sel)) {
    $sel = json_decode(rawurldecode($sel), true);
}
unset($params['sel']);

if (!$sel) {
    $params['fmerror'] = 'nofilesselected';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}
if (count($sel) > 1) {
    $params['fmerror'] = 'morethanonefiledirselected';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}

$config = Lone::get('Config');
$basedir = CMS_ROOT_PATH;
$filename = $this->decodefilename($sel[0]);
$src = cms_join_path($basedir, Utils::get_cwd(), $filename);
if (!file_exists($src)) {
    $params['fmerror'] = 'filenotfound';
    $this->Redirect($id, 'defaultadmin', $returnid, $params);
}
$imageinfo = getimagesize($src);
if (!$imageinfo || !isset($imageinfo['mime']) || !startswith($imageinfo['mime'], 'image')) {
    $this->SetError($this->Lang('filenotimage'));
    $this->Redirect($id, 'defaultadmin', $returnid);
}
if (!is_writable($src)) {
    $this->SetError($this->Lang('filenotimage'));
    $this->Redirect($id, 'defaultadmin', $returnid);
}

//
// handle submit action(s).
//

if (empty($params['reset'])
     && !empty($params['cx']) && !empty($params['cy'])
     && !empty($params['cw']) && !empty($params['ch'])
     && !empty($params['iw']) && !empty($params['ih'])) {
    //Get the mimeType
    $mimeType = ImageEditor::getMime($src);

    //Open new instance
    $instance = ImageEditor::open($src);

    //Resize it if necessary
    if (!empty($params['iw']) && !empty($params['ih'])) {
        $instance = ImageEditor::resize($instance, $mimeType, $params['iw'], $params['ih']);
    }

    //Crop it if necessary
    if (!empty($params['cx']) && !empty($params['cy']) && !empty($params['cw']) && !empty($params['ch'])) {
        $instance = ImageEditor::crop($instance, $mimeType, $params['cx'], $params['cy'], $params['cw'], $params['ch']);
    }

    //Save it
    $res = ImageEditor::save($instance, $src, $mimeType);
    if ($this->GetPreference('create_thumbnails')) {
        Utils::create_thumbnail($src);
    }

    $this->Redirect($id, 'defaultadmin', $returnid);
}

if (is_array($sel)) {
    $params['sel'] = rawurlencode(json_encode($sel));
}

/* see filemanager.css
$css = <<<EOS
<style>
input.invalid {
  background-color: salmon;
}
</style>
EOS;
add_page_headtext($css, false);
*/

$image_width = $imageinfo[0];
$image = Utils::get_cwd_url()."/$filename";

$js = <<<EOS
<script>
$(function() {
  // Apply jrac on some image
  $('#img').jrac({
    'crop_width': 250,
    'crop_height': 170,
    'crop_x': 100,
    'crop_y': 100,
    'image_width': $image_width,
    'viewport_width': $('#test1').width() - 30,
    'viewport_onload': function() {
      var \$viewport = this;
      var inputs = $('table#coords input:text');
      var events = ['jrac_crop_x', 'jrac_crop_y', 'jrac_crop_width', 'jrac_crop_height', 'jrac_image_width', 'jrac_image_height'];
      for(var i = 0; i < events.length; i++) {
        var event_name = events[i];
        // Register an event with an element.
        \$viewport.observator.register(event_name, inputs.eq(i));
        // Attach a handler to that event for the element.
        inputs.eq(i).on(event_name, function(event, \$viewport, value) {
          $(this).val(Math.floor(value));
        })
        // Attach a handler for the built-in jQuery change event, handler
        // which reads user input and applies it to relevent viewport object.
          .change(event_name, function(ev) { // TODO .on('change ...
            var event_name = ev.data;
            \$viewport.$image.scale_proportion_locked = \$viewport.\$container.parent('.pane').find('.coords input:checkbox').is(':checked');
            \$viewport.observator.set_property(event_name, $(this).val());
          });
      }
      $('#natsize').html(\$viewport.$image.originalWidth + ' x ' + \$viewport.$image.originalHeight);
    }
  })
    // React on all viewport events
    .on('jrac_events', function(ev, \$viewport) {
      var inputs = $('table#coords input:text');
      if(\$viewport.observator.crop_consistent()) {
        inputs.removeClass('invalid');
        inputs.addClass('valid');
      } else {
        inputs.removeClass('valid');
        inputs.addClass('invalid');
      }
      $('#submit').prop('disabled', (\$viewport.observator.crop_consistent()) ? false : true);
  });
});
</script>
EOS;
add_page_foottext($js);

//
// build the form
//
$tpl = $smarty->createTemplate($this->GetTemplateResource('pie.tpl')); //,null,null,$smarty);

$tpl->assign('formstart', $this->CreateFormStart($id, 'resizecrop', $returnid, 'post', '', false, '', $params))
    ->assign('filename', $filename);

$tpl->display();
