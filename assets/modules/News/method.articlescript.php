<?php

$script_url = CMS_SCRIPTS_URL;

$js = <<<EOS
<script type="text/javascript" src="{$script_url}/jquery.cmsms_dirtyform.min.js"></script>
<script type="text/javascript">
//<![CDATA[

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
$(document).ready(function() {
  $('[name$=apply],[name$=submit]').hide();
  $('#edit_news').dirtyForm({
    onDirty: function() {
      $('[name$=apply],[name$=submit]').show('slow');
    }
  });
  $(document).on('cmsms_textchange', function() {
    // editor text change, set the form dirty.
    $('#edit_news').dirtyForm('option', 'dirty', true);
  });
  $('[name$=submit],[name$=apply],[name$=cancel]').on('click', function() {
    $('#edit_news').dirtyForm('option', 'disabled', true);
  });
  $('#fld11').on('click', function() {
    $('#expiryinfo').toggle('slow');
  });
  $('[name$=cancel]').on('click', function() {
    $(this).closest('form').attr('novalidate', 'novalidate');
  });

EOS;
if ($list) {
    $js .= <<<EOS
  $('[name={$id}apply]').on('click', function(ev) {
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

EOS;
} //templates present
$js .= <<<EOS
});
//]]>
</script>

EOS;

$this->AdminBottomContent($js);
