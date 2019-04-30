<?php
#Page-resources generator for article add/edit actions
#Copyright (C) 2018-2019 CMS Made Simple Foundation <foundation@cmsmadesimple.org>
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

use CMSMS\ScriptOperations;

$baseurl = $this->GetModuleURLPath();
$css = <<<EOS
 <link rel="stylesheet" href="{$baseurl}/css/jquery.datepicker.css">
 <link rel="stylesheet" href="{$baseurl}/css/jquery.timepicker.css">

EOS;
$this->AdminHeaderContent($css);

//js wants quoted period-names
$t = $this->Lang('selector_days');
$dnames = "'".str_replace(",","','",$t)."'";
$t = $this->Lang('selector_shortdays');
$sdnames = "'".str_replace(",","','",$t)."'";
$t = $this->Lang('selector_months');
$mnames = "'".str_replace(",","','",$t)."'";
$t = $this->Lang('selector_shortmonths');
$smnames = "'".str_replace(",","','",$t)."'";
$noday = $this->Lang('selector_badday');
$t = $this->Lang('selector_times');
$mdm = explode(',',$t);
$n = $this->GetPreference('timeblock',News::HOURBLOCK);
switch($n) {
    case News::DAYBLOCK:
        $gapmins = 60*24;
        break;
    case News::HALFDAYBLOCK:
        $gapmins = 60*12;
        break;
    default:
        $gapmins = 60;
        break;
}

$js = <<<EOS
$.datePicker.strings = {
 monthsFull: [$mnames],
 monthsShort: [$smnames],
 daysFull: [$dnames],
 daysShort: [$sdnames],
 messageLocked: '$noday'
};
$.datePicker.defaults.formatDate = function(date) {
 var formatted = date.getFullYear() + '-' + $.datePicker.utils.pad(date.getMonth() + 1, 2) + '-' +  $.datePicker.utils.pad(date.getDate(), 2) ;
 return formatted;
};
$.datePicker.defaults.parseDate = function(string) {
 var date, parts = string.match(/(\d{4})-(\d{1,2})-(\d{1,2})/);
 if ( parts && parts.length == 4 ) {
  date = new Date( parts[1], parts[2] - 1, parts[3] );
 } else {
  date = new Date();
 }
 return date;
};

EOS;
if ($list) {
    $js .= <<<EOS
function news_dopreview() {
  if(typeof tinyMCE !== 'undefined') {
    tinyMCE.triggerSave();
  }
  var fm = $('form'),
     url = fm.attr('action'),
    data = fm.find('input:not([type=submit]), select, textarea').serializeArray();
  data.push({ 'name': '{$id}ajax', 'value': 1 });
  data.push({ 'name': '{$id}preview', 'value': 1 });
  data.push({ 'name': '{$id}previewpage', 'value': $("input[name='preview_returnid']").val() });
  data.push({ 'name': '{$id}detailtemplate', 'value': $('#preview_template').val() });
  data.push({ 'name': 'cmsjobtype', 'value': 1 }); //url param to curtail display
  $.post(url, data, function(resultdata, textStatus, jqXHR) {
    var resp = $(resultdata).find('Response').text(),
     details = $(resultdata).find('Details').text();
    if(resp === 'Success' && details !== '') {
      // preview worked... now the details should contain the url
      details = details.replace(/amp;/g, '');
      $('#previewframe').attr('src', details);
    } else {
      if(details === '') {
        details = '$this->Lang("error_unknown")';
      }
      // preview save did not work
      cms_notify('error', details);
    }
  }, 'xml');
}

EOS;
} //templates present
$js .= <<<EOS
$(function() {
  $('[name$="apply"],[name$="submit"]').attr('disabled','disabled');
  $('#edit_news').dirtyForm({
    onDirty: function() {
      $('[name$="apply"],[name$="submit"]').removeAttr('disabled');
    }
  });
  $(document).on('cmsms_textchange', function() {
    // editor text change, set the form dirty.
    $('#edit_news').dirtyForm('option', 'dirty', true);
  });
  $('[name$="submit"],[name$="apply"],[name$="cancel"]').on('click', function() {
    $('#edit_news').dirtyForm('option', 'disabled', true);
  });
  $('#fld11').on('click', function() {
    $('#expiryinfo').toggle('slow');
  });
  $('[name$="cancel"]').on('click', function() {
    $(this).closest('form').attr('novalidate', 'novalidate');
  });

EOS;
if ($list) {
    $js .= <<<EOS
  $('[name="{$id}apply"]').on('click', function(ev) {
    ev.preventDefault();
    if(typeof tinyMCE !== 'undefined') {
      tinyMCE.triggerSave();
    }
    var fm = $('form'),
       url = fm.attr('action');
      data = fm.find('input:not([type=submit]), select, textarea').serializeArray();
    data.push({ 'name': '{$id}ajax', 'value': 1 });
    data.push({ 'name': '{$id}apply', 'value': 1 });
    data.push({ 'name': 'cmsjobtype', 'value': 1 }); //url param to curtail display
    $.post(url, data, function(resultdata, textStatus, jqXHR) { //TODO robust API
      var resp = $(resultdata).find('Response').text(),
       details = $(resultdata).find('Details').text();
      if(resp === 'Success' && details !== '') {
        cms_notify('info', details);
      } else {
        cms_notify('error', details);
      }
    }, 'xml');
  });
  $('#preview').on('click', function(ev) {
    ev.preventDefault();
    news_dopreview();
    return false;
  });
  $("input[name='preview_returnid'],#preview_template").on('change', function(ev) {
    ev.preventDefault();
    news_dopreview();
    return false;
  });
  $('#pickers .time').timepicker({
    timeFormat: 'g:ia',
    step: $gapmins,
    lang: {
     am: '$mdm[0]',
     pm: '$mdm[1]',
     AM: '$mdm[2]',
     PM: '$mdm[3]',
     decimal: '$mdm[4]',
     mins: '$mdm[5]',
     hr: '$mdm[6]',
     hrs: '$mdm[7]'
    }
  });

EOS;
} //templates present
$js .= <<<EOS
});

EOS;

$p = cms_join_path($this->GetModulePath(),'lib','js').DIRECTORY_SEPARATOR;
$sm = new ScriptOperations();
$sm->queue_file(CMS_SCRIPTS_PATH.DIRECTORY_SEPARATOR.'jquery.cmsms_dirtyform.min.js', 1);
$sm->queue_file($p.'jquery.datePicker.min.js', 2);
$sm->queue_file($p.'jquery.timepicker.min.js', 2);
$sm->queue_string($js, 3);
$out = $sm->render_inclusion('', false, false);
if ($out) {
    $this->AdminBottomContent($out);
}
