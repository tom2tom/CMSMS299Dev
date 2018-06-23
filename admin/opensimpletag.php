<?php
#procedure to add or edit a user-defined-tag / simple-plugin
#Copyright (C) 2018 The CMSMS Dev Team <coreteam@cmsmadesimple.org>
#This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>
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

$CMS_ADMIN_PAGE=1;

require_once dirname(__DIR__).DIRECTORY_SEPARATOR.'lib'.DIRECTORY_SEPARATOR.'include.php';

check_login();

$userid = get_userid();
$urlext='?'.CMS_SECURE_PARAM_NAME.'='.$_SESSION[CMS_USER_KEY];

if (isset($_POST['cancel'])) {
	redirect('listsimpletags.php'.$urlext);
}

if (isset($_POST['submit']) || isset($_POST['apply']) ) {
	cleanArray($_POST);
	$ADBG = $_POST;
/*
$_POST[
'oldtagname',
'code',
'tagname',
'description',
'parameters',
'license',
];
*/
/* validation?
$URI = $_SERVER['REQUEST_URI'];
$errm = json_encode(lang('noudtcode'));
$confirm = json_encode(lang('confirm_runusertag'));
$output = htmlentities(lang('output'));
$out = <<<EOS
<script type="text/javascript">
//<![CDATA[
$(document).ready(function() {
 $('#runbtn').on('click', function(ev) {
  // get the data
  ev.preventDefault();
  cms_confirm($confirm).done(function() {
   var code = $('#udtcode').val();
   if(code.length === 0) {
    cms_notify('error', $errm);
    return false;
   }
   var data = $('#edit_userplugin')
    .find('input:not([type=submit]), select, textarea')
    .serializeArray();
   data.push({ 'name': 'code', 'value': code });
   data.push({ 'name': 'run', 'value': 1 });
   data.push({ 'name': 'apply', 'value': 1 });
   data.push({ 'name': 'ajax', 'value': 1 });
   $.post('$URI', data,
    function(resultdata, textStatus, jqXHR) { //TODO robust API
     var r, d, e;
     try {
     var x = JSON.parse(resultdata); //IE8+
      if(typeof x.response !== 'undefined') {
       r = x.response;
       d = x.details;
      } else {
       d = resultdata;
      }
     } catch(e) {
      r = '_error';
      d = resultdata;
     }
     if(r === '_error') {
      cms_notify('error', d);
     } else {
      cms_notify('info', '<h3>$output</h3>' + d);
     }
    }
   );
   return false;
  }).fail(function() {
   return false;
  });
 });
 $('#applybtn').on('click', function() {
  var data = $('#edit_userplugin')
   .find('input:not([type=submit]), select, textarea')
   .serializeArray();
  data.push({ 'name': 'ajax', 'value': 1 });
  data.push({ 'name': 'apply', 'value': 1 });
  $.post('$URI', data,
   function(resultdata, textStatus, jqXHR) { //TODO robust API
    var x = JSON.parse(resultdata); //IE8+
    if(x.response === 'Success') {
     cms_notify('success', x.details);
    } else {
     cms_notify('error', x.details);
    }
   }
  );
  return false;
 });
});
//]]>
</script>
EOS;
*/
	if (isset($_POST['submit'])) {
		redirect('listsimpletags.php'.$urlext);
	}
	$tagname = $_POST['tagname'];
} else {
	$tagname = cleanValue($_GET['tagname']);
}

$edit = check_permission($userid, 'Modify Simple Tags');
//TODO also $_GET['mode'] == 'edit'

$fixed = ($edit) ? 'false' : 'true';

$ops = \CMSMS\SimplePluginOperations::get_instance();

if ($tagname != '-1') {
	$fullpath = $ops->plugin_filepath($tagname);
	list($meta, $code) = $ops->get($tagname);
} else {
	$fullpath = __FILE__; //anything .php
	$meta = [];
	$code = '';
}

$style = cms_userprefs::get_for_user(get_userid(false), 'editortheme', 'clouds');
$style = strtolower($style);

$js = <<<EOS
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ace.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/ace/1.3.3/ext-modelist.js"></script>
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

$themeObject = cms_utils::get_theme_object();
$themeObject->add_footertext($js);

//TODO
$selfurl = basename(__FILE__);

$smarty = CMSMS\internal\Smarty::get_instance();
$smarty->assign([
	'name' => $tagname,
    'description' => $meta['description'] ?? null,
    'parameters' => (isset($meta['parameters'])) ? implode("\n", $meta['parameters']) : null,
    'license' => $meta['license'] ?? null,
    'code' => $code,
    'urlext' => $urlext,
    'selfurl' => $selfurl,
]);

include_once 'header.php';
$smarty->display('opensimpletag.tpl');
include_once 'footer.php';
