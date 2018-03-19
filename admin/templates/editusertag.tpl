<script type="text/javascript">
{literal}//<![CDATA[
$(document).ready(function() {
  $(document).on('click', '#runbtn', function(ev) {
    // get the data
    ev.preventDefault();
    cms_confirm({/literal}'{lang("confirm_runusertag")|strip|escape:"quotes"}'{literal}).done(function() {
      var code = $('#udtcode').val();
      if(code.length === 0) {
        cms_notify('error', {/literal}'{lang("noudtcode")}'{literal});
        return false;
      }
      var data = $('#edit_userplugin')
        .find('input:not([type=submit]), select, textarea')
        .serializeArray();
      data.push({ 'name': 'code', 'value': code });
      data.push({ 'name': 'run', 'value': 1 });
      data.push({ 'name': 'apply', 'value': 1 });
      data.push({ 'name': 'ajax', 'value': 1 });
      $.post({/literal}'{$smarty.server.REQUEST_URI}'{literal}, data,
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
            cms_notify('info', {/literal}'<h3>{lang("output")}</h3>'{literal} + d);
          }
        }
      );
      return false;
    }).fail(function() {
      return false;
    });
  });

  $(document).on('click', '#applybtn', function() {
    var data = $('#edit_userplugin')
      .find('input:not([type=submit]), select, textarea')
      .serializeArray();
    data.push({ 'name': 'ajax', 'value': 1 });
    data.push({ 'name': 'apply', 'value': 1 });
    $.post({/literal}'{$smarty.server.REQUEST_URI}'{literal}, data,
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
{/literal}//]]>
</script>

<h3 class="pagesubtitle">{if $record.userplugin_id}{lang('usertag')}: {$tagname}{/if}</h3>

<form id="edit_userplugin" action="{$selfurl}{$urlext}" method="post">
<div class="hidden">
  <input type="hidden" name="userplugin_id=" value="{$record.userplugin_id}" />
</div>
<fieldset>
  <div style="width:49%;float:left;">
    <div class="pageoverflow">
      <p class="pageinput">
        <button type="submit" name="submit" id="submitme" class="adminsubmit icon check">{lang('submit')}</button>
        <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
        {if $record.userplugin_id != ''}
          <button type="submit" name="apply" id="applybtn" title="{lang('title_applyusertag')}" class="adminsubmit icon apply">{lang('apply')}</button>
          <button type="submit" name="run" id="runbtn" title="{lang('runuserplugin')}" class="adminsubmit icon do">{lang('run')}</button>
        {/if}
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="name">{lang('name')}:</label>
        {cms_help key1=h_udtname title=lang('name')}
      </p>
      <p class="pageinput">
        <input type="text" id="name" name="userplugin_name" value="{$record.userplugin_name}" size="50" maxlength="50" />
      </p>
    </div>
  </div>

  <div style="width:49%;float:right;">
    {if $record.create_date != ''}
      <div class="pageoverflow">
        <p class="pagetext">{lang('created_at')}:</p>
        <p class="pageinput">{$record.create_date|cms_date_format}</p>
      </div>
    {/if}

    {if $record.modified_date != ''}
      <div class="pageoverflow">
        <p class="pagetext">{lang('last_modified_at')}:</p>
        <p class="pageinput">{$record.modified_date|cms_date_format}</p>
      </div>
    {/if}
  </div>
</fieldset>

{tab_header name='code' label=lang('code')}
{tab_header name='description' label=lang('description')}

{tab_start name='code'}
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="code"><strong>{lang('code')}:</strong></label>
      {cms_help key1=h_udtcode title=lang('code')}
    </p>
    <p class="pageinput">
      {cms_textarea id='udtcode' name='code' value=$record.code wantedsyntax=php rows=10 cols=80}
    </p>
  </div>

{tab_start name='description'}
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="description">{lang('description')}:</label>
      {cms_help key1=h_udtdesc title=lang('description')}
    </p>
    <p class="pageinput">
      <textarea id="description" name="description" rows="3" cols="80">{$record.description}</textarea>
    </p>
  </div>
{tab_end}

</form>
