<style type="text/css" scoped>
{literal}
#status_area,#searchresults_cont,#workarea {
  display: none;
}
#searchresults {
  max-height: 25em;
  overflow: auto;
  cursor: pointer;
}
.search_oneresult {
  color: red;
}
{/literal}
</style>

<script type="text/javascript">
//<![CDATA[{literal}
 {/literal}var ajax_url = '{$ajax_url}';
 {if isset($saved_search.slaves) && in_array(-1,$saved_search.slaves)}
 var sel_all = 1;
 {/if}{literal}

 $('#searchbtn').on('click', function() {
   var l = $('#filter_box :checkbox.filter_toggle:checked').length;
   if(l === 0) {
     cms_alert({/literal}'{$mod->Lang("error_select_slave")|escape:"javascript"}'{literal});
   } else {
     $('#searchresults').html('');
   }
 });
{/literal}//]]>
</script>
<script type="text/javascript" src="{$js_url}"></script>

<div id="adminsearchform">
{form_start action=admin_search}

<table class="pagetable">
<tr valign="top">
<td style="width:50%;">
<div class="pageoverflow">
  <p class="pagetext">
    <label for="searchtext">{$mod->Lang('search_text')}:</label>
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}search_text" id="searchtext" value="{$saved_search.search_text|default:''}" size="80" maxlength="80" placeholder="{$mod->Lang('placeholder_search_text')}" />
  </p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" id="searchbtn" class="adminsubmit icon do">{$mod->Lang('search')}</button>
  </p>
</div>
</td>
<td style="width:50%;">
<div class="pageoverflow" id="filter_box">
  <p class="pagetext">{$mod->Lang('filter')}:</p>
  <p class="pageinput">
    <input id="filter_all" type="checkbox" name="{$actionid}slaves[]" value="-1" />&nbsp;<label for="filter_all" title="{$mod->Lang('desc_filter_all')}">{$mod->Lang('all')}</label><br />
    {foreach $slaves as $slave}
      <input class="filter_toggle" id="{$slave.class}" type="checkbox" name="{$actionid}slaves[]" value="{$slave.class}"{if isset($saved_search.slaves) && in_array($slave.class,$saved_search.slaves)} checked="checked"{/if} />&nbsp;<label for="{$slave.class}" title="{$slave.description}">{$slave.name}</label>{if !$slave@last}<br />{/if}
    {/foreach}
    <br /><br />
    <input type="hidden" name="{$actionid}search_descriptions" value="0" />
    <input type="checkbox" id="search_desc" name="{$actionid}search_descriptions" value="1" />&nbsp;
      <label for="search_desc">{$mod->Lang('lbl_search_desc')}</label>
  </p>
  <br />
</div>
</td>
</tr>
</table>

<div class="pageoverflow" id="progress_area"></div>
<div class="pageoverflow" id="status_area"></div>
<fieldset id="searchresults_cont">
  <legend>{$mod->Lang('search_results')}:</legend>
  <div id="searchresults_cont2">
    <ul id="searchresults">
    </ul>
  </div>
</fieldset>
{form_end}
</div>

<iframe id="workarea" name="workarea"></iframe>
