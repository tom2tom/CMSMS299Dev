<?php
/*
Page-resources generator for article add/edit actions
Copyright (C) 2018-2021 CMS Made Simple Foundation News module installation <foundation@cmsmadesimple.org>

This file is a component of CMS Made Simple <http://www.cmsmadesimple.org>

CMS Made Simple is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of that license, or
(at your option) any later version.

CMS Made Simple is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of that license along with CMS Made Simple.
If not, see <https://www.gnu.org/licenses/>.
*/

use CMSMS\ScriptsMerger;

$baseurl = $this->GetModuleURLPath();
$css = <<<EOS
 <link rel="stylesheet" href="{$baseurl}/css/jquery.datepicker.css">
 <link rel="stylesheet" href="{$baseurl}/css/jquery.timepicker.css">

EOS;
add_page_headtext($css);

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
        $gapmins = 1440; //60*24
        break;
    case News::HALFDAYBLOCK:
        $gapmins = 720; //60*12
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
 if (parts && parts.length == 4) {
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
    tinyMCE.triggerSave(); //TODO generalise e.g. setpagecontent() to migrate editor-content into an input-element to be saved
  }
  var fm = $('form'),
     url = fm.attr('action'),
  params = [
  {name: '{$id}ajax', 'value': 1},
  {name: '{$id}preview', 'value': 1},
  {name: '{$id}previewpage', 'value': $('input[name="preview_returnid"]').val()},
  {name: '{$id}detailtemplate', 'value': $('#preview_template').val()},
  {name: cms_data.job_key, 'value': 1} //curtail display
  ].concat(fm.find('input:not([type=submit]), select, textarea').serializeArray());
  $.ajax(url, {
    method: 'POST',
    data: params,
    dataType: 'xml'
  }).done(function(data) {
    var resp = $(data).find('Response').text(),
     details = $(data).find('Details').text();
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
  }).fail(function(jqXHR, textStatus, errorThrown) {
    cms_notify('error', errorThrown);
  });
}

EOS;
} //templates present

$js .= <<<EOS
$(function() {
  $('.cmsfp_elem').on('change', function() {
    var img = $(this).val();
    if (img) {
      $('.yesimage').show();
    } else {
      $('.yesimage').hide();
    }
    return false;
  }).triggerHandler('change');
  $('[name="{$id}apply"],[name="{$id}submit"]').prop('disabled',true);
  $('#edit_news').dirtyForm({
    onDirty: function() {
      $('[name="{$id}apply"],[name="{$id}submit"]').prop('disabled',false);
    }
  });
  $('#edit_news :input').on('change', function() {
    // on any content change set the form dirty
    $('#edit_news').dirtyForm('option', 'dirty', true);
  });
  $('[name="{$id}submit"],[name="{$id}apply"],[name="{$id}cancel"]').on('click', function() {
    $('#edit_news').dirtyForm('option', 'disabled', true);
  });
  $('#fld11').on('click', function() {
    $('#expiryinfo').toggle('slow');
  });
  $('[name="{$id}cancel"]').on('click', function() {
    $(this).closest('form').attr('novalidate', 'novalidate');
  });

EOS;
if ($list) {
    $js .= <<<EOS
  $('[name="{$id}apply"]').on('click', function(ev) {
    ev.preventDefault();
    if(typeof tinyMCE !== 'undefined') {
      tinyMCE.triggerSave(); //TODO generalise e.g. setpagecontent() to migrate editor-content into an input-element to be saved
    }
    var fm = $('form'),
       url = fm.attr('action'),
    params = [
    {name: '{$id}ajax', 'value': 1},
    {name: '{$id}apply', 'value': 1},
    {name: cms_data.job_key, 'value': 1} // curtail display
    ].concat(fm.find('input:not([type=submit]), select, textarea').serializeArray());
    $.ajax(url, {
      method: 'POST',
      data: params,
      dataType: 'xml'
    }).fail(function(jqXHR, textStatus, errorThrown) {
      cms_notify('error', errorThrown);
    }).done(function(data) {
      var resp = $(resultdata).find('Response').text(),
       details = $(resultdata).find('Details').text();
      if(resp === 'Success' && details !== '') {
        cms_notify('info', details);
      } else {
        cms_notify('error', details);
      }
    });
    return false;
  });
  $('#preview').on('click', function(ev) {
    ev.preventDefault();
    news_dopreview();
    return false;
  });
  $('input[name="preview_returnid"],#preview_template').on('change', function(ev) {
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
    if ($pprop) { // current user may create an article but not self-publish
        $t = lang('ok');
        $js .= <<<EOS
  $('[name="{$id}apply"],[name="{$id}submit"]').on('click', function(ev) {
    var st = $('[name="{$id}status"]').val();
    if (st === 'final') {
      ev.preventDefault();
      cms_dialog($('#post_notice'), {
        modal: true,
        width: 'auto',
        close: function (ev, ui) {
          $('#post_notice').find('form').trigger('submit');
        },
        buttons: {
          '$t': function() {
            $(this).dialog('close');
          }
        }
      });
      return false;
    }
  });

EOS;
    }
} //templates present
$js .= <<<EOS
});

EOS;

$p = cms_join_path($this->GetModulePath(),'lib','js');
$jsm = new ScriptsMerger();
$jsm->queue_matchedfile('jquery.cmsms_dirtyform.js', 1);
$jsm->queue_matchedfile('jquery.datePicker.js', 2, $p);
$jsm->queue_matchedfile('jquery.timepicker.js', 2, $p);
$jsm->queue_string($js, 3);
$out = $jsm->page_content();
if ($out) {
    add_page_foottext($out);
}
