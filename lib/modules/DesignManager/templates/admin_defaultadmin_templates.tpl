<script type="text/javascript">
{literal}//<![CDATA[
$(document).ready(function() {
  // load the templates area.
  cms_busy();
  $('#template_area').autoRefresh({
    url: '{$ajax_templates_url}',
    data: {
      filter: '{$jsonfilter}'
    }
  });
  $('#tpl_bulk_action,#tpl_bulk_submit').attr('disabled', 'disabled');
  $('#tpl_bulk_submit').button({ 'disabled': true });
  $('#tpl_selall,.tpl_select').on('click', function() {
    var l = $('.tpl_select:checked').length;
    if(l === 0) {
      $('#tpl_bulk_action').attr('disabled', 'disabled');
      $('#tpl_bulk_submit').attr('disabled', 'disabled');
      $('#tpl_bulk_submit').button({ 'disabled': true });
    } else {
      $('#tpl_bulk_action').removeAttr('disabled');
      $('#tpl_bulk_submit').removeAttr('disabled');
      $('#tpl_bulk_submit').button({ 'disabled': false });
    }
  });
  $('a.steal_tpl_lock').on('click', function(e) {
    // we're gonna confirm stealing this lock
    e.preventDefault();
    cms_confirm_linkclick(this,'{/literal}{$mod->Lang("confirm_steal_lock")|escape:"javascript"}{literal}');
    return false;
  });
  $('a.sedit_tpl').on('click', function(e) {
    if($(this).hasClass('steal_tpl_lock')) return true;
    // do a double check to see if this page is locked or not.
    var tpl_id = $(this).attr('data-tpl-id');
    var url = {/literal}'{$admin_url}/ajax_lock.php?cmsjobtype=1'{literal};
    var opts = { opt: 'check', type: 'template', oid: tpl_id };
    opts[cms_data.secure_param_name] = cms_data.user_key;
    $.ajax({
      url: url,
      data: opts,
    }).done(function(data) {
      if(data.status === 'success') {
        if(data.locked) {
          // gotta display a message.
          ev.preventDefault();
          cms_alert({/literal}'{$mod->Lang("error_contentlocked")|escape:"javascript"}'{literal});
        }
      }
    });
  });
  $('#tpl_bulk_submit').on('click', function() {
    var n = $('input:checkbox:checked.tpl_select').length;
    if(n === 0) {
      cms_alert({/literal}'{$mod->Lang("error_nothingselected")|escape:"javascript"}'{literal});
      return false;
    }
  });
  $('#template_area').on('click', '#edittplfilter', function() {
    cms_dialog($('#filterdialog'), {
      width: 'auto',
      buttons: {
        {/literal}'{$mod->Lang("submit")|escape:"javascript"}'{literal}: function() {
          cms_dialog($(this), 'close');
          $('#filterdialog_form').submit();
        },
        {/literal}'{$mod->Lang("reset")|escape:"javascript"}'{literal}: function() {
          cms_dialog($(this), 'close');
          $('#submit_filter_tpl').val('-1');
          $('#filterdialog_form').submit();
        },
        {/literal}'{$mod->Lang("cancel")|escape:"javascript"}'{literal}: function() {
          cms_dialog($(this), 'close');
        }
      }
    });
  });
  $('#addtemplate').on('click', function() {
    cms_dialog($('#addtemplatedialog'), {
      width: 'auto',
      buttons: {
        {/literal}'{$mod->Lang("submit")|escape:"javascript"}'{literal}: function() {
          cms_dialog($(this), 'close');
          $('#addtemplate_form').submit();
        },
        {/literal}'{$mod->Lang("cancel")|escape:"javascript"}'{literal}: function() {
          cms_dialog($(this), 'close');
        }
      }
    });
  });
});
{/literal}//]]>
</script>

<div id="filterdialog" title="{$mod->Lang('tpl_filter')|escape:'javascript'}">
 {form_start action='defaultadmin' id='filterdialog_form' __activetab='templates'}
  <input type="hidden" id="submit_filter_tpl" name="{$actionid}submit_filter_tpl" value="1" />
  <table class="responsive">
  <tbody>
  <tr>
    <td><label for="filter_tpl">{$mod->Lang('prompt_options')}:</label></td>
    <td><select id="filter_tpl" name="{$actionid}filter_tpl" title="{$mod->Lang('title_filter')}">
      {html_options options=$filter_tpl_options selected=$tpl_filter.tpl}
    </select></td>
  </tr>
  <tr>
    <td><label for="filter_sortby">{$mod->Lang('prompt_sortby')}:</label></td>
    <td><select id="filter_sortby" name="{$actionid}filter_sortby" title="{$mod->Lang('title_sortby')}">
      <option value="name"{if $tpl_filter.sortby == 'name'} selected="selected"{/if}>{$mod->Lang('name')}</option>
      <option value="type"{if $tpl_filter.sortby == 'type'} selected="selected"{/if}>{$mod->Lang('type')}</option>
      <option value="created"{if $tpl_filter.sortby == 'created'} selected="selected"{/if}>{$mod->Lang('created')}</option>
      <option value="modified"{if $tpl_filter.sortby == 'modified'} selected="selected"{/if}>{$mod->Lang('modified')}</option>
    </select></td>
  </tr>
  <tr>
    <td><label for="filter_sortorder">{$mod->Lang('prompt_sortorder')}:</label></td>
    <td><select id="filter_sortorder" name="{$actionid}filter_sortorder" title="{$mod->Lang('title_sortorder')}">
      <option value="asc"{if $tpl_filter.sortorder == 'asc'} selected="selected"{/if}>{$mod->Lang('asc')}</option>
      <option value="desc"{if $tpl_filter.sortorder == 'desc'} selected="selected"{/if}>{$mod->Lang('desc')}</option>
    </select></td>
  </tr>
  <tr>
    <td><label for="filter_limit">{$mod->Lang('prompt_limit')}:</label></td>
    <td><select id="filter_limit" name="{$actionid}filter_limit_tpl" title="{$mod->Lang('title_filterlimit')}">
      <option value="10"{if $tpl_filter.limit == 10} selected="selected"{/if}>10</option>
      <option value="25"{if $tpl_filter.limit == 25} selected="selected"{/if}>25</option>
      <option value="50"{if $tpl_filter.limit == 50} selected="selected"{/if}>50</option>
      <option value="100"{if $tpl_filter.limit == 100} selected="selected"{/if}>100</option>
    </select></td>
  </tr>
  </tbody>
  </table>
 </form>
</div>{* #filterdialog *}

{if $has_add_right}
<div id="addtemplatedialog" title="{$mod->Lang('create_template')}">
  {form_start id="addtemplate_form"}
  <input type="hidden" name="{$actionid}submit_create" value="1" />
  <table class="responsive">
  <tbody>
  <tr>
    <td><label for="tpl_import_type">{$mod->Lang('tpl_type')}:</label></td>
    <td><select name="{$actionid}import_type" id="tpl_import_type" title="{$mod->Lang('title_tpl_import_type')}">
      {html_options options=$list_types}
    </select></td>
  </tr>
  </tbody>
  </table>
  </form>
</div>{* #addtemplatedialog *}
{/if}

<div id="template_area"></div>
