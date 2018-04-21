<script type="text/javascript">
{literal}//<![CDATA[
$(document).ready(function() {
  cms_busy();
  $('#stylesheet_area').autoRefresh({
    url: {/literal}'{$ajax_stylesheets_url}'{literal},
    data: {
      filter: {/literal}'{$jsoncssfilter}'{literal}
    }
  });
  $('#css_bulk_action,#css_bulk_submit').attr('disabled', 'disabled');
  $('#css_bulk_submit').button({ 'disabled': true });
  $('#css_selall,.css_select').on('click', function() {
    // if one or more .css_select is checked, enable the bulk actions
    var l = $('.css_select:checked').length;
    if(l === 0) {
      $('#css_bulk_action').attr('disabled', 'disabled');
      $('#css_bulk_submit').attr('disabled', 'disabled');
      $('#css_bulk_submit').button({ 'disabled': true });
    } else {
      $('#css_bulk_action').removeAttr('disabled');
      $('#css_bulk_submit').removeAttr('disabled');
      $('#css_bulk_submit').button({ 'disabled': false });
    }
  });
  $('a.steal_css_lock').on('click', function(e) {
    // we're gonna confirm stealing this lock
    e.preventDefault();
    cms_confirm_linkclick(this,'{/literal}{$mod->Lang("confirm_steal_lock")|escape:"javascript"}{literal}');
    return false;
  });
  $('#stylesheet_area').on('click', '#editcssfilter', function() {
    cms_dialog($('#filtercssdlg'), {
      width: 'auto',
      buttons: {
        {/literal}'{$mod->Lang("submit")}'{literal}: function() {
          cms_dialog($(this), 'close');
          $('#filtercssdlg_form').submit();
        },
        {/literal}'{$mod->Lang("reset")}'{literal}: function() {
          cms_dialog($(this), 'close');
          $('#submit_filter_css').val('-1');
          $('#filtercssdlg_form').submit();
        },
        {/literal}'{$mod->Lang("cancel")}'{literal}: function() {
          cms_dialog($(this), 'close');
        }
      }
    });
  });
});
{/literal}//]]>
</script>

<div id="stylesheet_area"></div>

<div id="filtercssdlg" style="display: none;" title="{$mod->Lang('css_filter')}">
  {form_start id='filtercssdlg_form'}{*strip*}
    <input type="hidden" name="{$actionid}submit_filter_css" id="submit_filter_css" value="1" />
    <div class="vbox">
    <div class="hbox flow">
      <div class="boxchild"></div><label for="filter_css_design">{$mod->Lang('prompt_design')}:</label></div>
      <div class="boxchild fill"><select name="{$actionid}filter_css_design" id="filter_css_design" title="{$mod->Lang('title_filter_design')}" class="grid_9">
        <option value="">{$mod->Lang('any')}</option>
        {html_options options=$design_names selected=$css_filter.design}
      </select></div>
    </div>
    <div class="hbox flow">
      <div class="boxchild"><label for="filter_css_sortby">{$mod->Lang('prompt_sortby')}:</label></div>
      <div class="boxchild fill"><select name="{$actionid}filter_css_sortby" id="filter_css_sortby" title="{$mod->Lang('title_sortby')}" class="grid_9">
        <option value="name"{if $css_filter.sortby == 'name'} selected="selected"{/if}>{$mod->Lang('name')}</option>
        <option value="created"{if $css_filter.sortby == 'created'} selected="selected"{/if}>{$mod->Lang('created')}</option>
        <option value="modified"{if $css_filter.sortby == 'modified'} selected="selected"{/if}>{$mod->Lang('modified')}</option>
      </select></div>
    </div>
    <div class="hbox flow">
      <div class="boxchild"><label for="filter_css_sortorder">{$mod->Lang('prompt_sortorder')}:</label></div>
      <div class="boxchild fill"><select name="{$actionid}filter_css_sortorder" id="filter_css_sortorder" title="{$mod->Lang('title_sortorder')}" class="grid_9">
        <option value="asc"{if $css_filter.sortorder == 'asc'} selected="selected"{/if}>{$mod->Lang('asc')}</option>
        <option value="desc"{if $css_filter.sortorder == 'desc'} selected="selected"{/if}>{$mod->Lang('desc')}</option>
      </select></div>
    </div>
    <div class="hbox flow">
      <div class="boxchild"><label for="filter_limit_css">{$mod->Lang('prompt_limit')}:</label></div>
      <div class="boxchild fill"><select name="{$actionid}filter_limit_css" id="filter_limit_css">
        <option value="10"{if isset($css_filter.limit) && $css_filter.limit == 10} selected="selected"{/if}>10</option>
        <option value="25"{if isset($css_filter.limit) && $css_filter.limit == 25} selected="selected"{/if}>25</option>
        <option value="50"{if isset($css_filter.limit) && $css_filter.limit == 50} selected="selected"{/if}>50</option>
        <option value="100"{if isset($css_filter.limit) && $css_filter.limit == 100} selected="selected"{/if}>100</option>
      </select></div>
    </div>
  </div>{*vbox*}
  </form>
</div>
