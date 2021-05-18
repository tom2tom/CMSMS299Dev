<div class="pageoverflow">
  <p class="pagetext">
    <label for="searchtext">{$mod->Lang('search_text')}:</label>
  </p>
  <p class="pageinput">
    <input type="text" id="searchtext" value="{$saved_search.search_text|default:''}" size="40" maxlength="80" placeholder="{$mod->Lang('placeholder_search_text')}" />
  </p>
</div>

<p class="pagetext">{$mod->Lang('filter')}:</p>
<div class="pageinput pageoverflow" id="filter_box">
  <input id="filter_all" type="checkbox" value="1" title="{$mod->Lang('desc_filter_all')}" />&nbsp;
  <label for="filter_all">{$mod->Lang('all')}</label><br />
  {foreach $slaves as $slave}
   <input type="checkbox" class="filter_toggle" id="{$slave.class}" value="{$slave.class}"{if !empty($saved_search.slaves) && in_array($slave.class,$saved_search.slaves)} checked="checked"{/if} title="{$slave.description}" />&nbsp;
   <label for="{$slave.class}">{$slave.name}</label><br />
  {/foreach}
  <input type="checkbox" class="filter_toggle" id="search_desc" value="1"{if !empty($saved_search.search_descriptions)} checked="checked"{/if} />&nbsp;
  <label for="search_desc">{$mod->Lang('lbl_search_desc')}</label><br />
  <br />
  <input type="checkbox" id="case_sensitive" value="1"{if !empty($saved_search.search_casesensitive)} checked="checked"{/if} />&nbsp;
  <label for="case_sensitive">{$mod->Lang('lbl_cased_search')}</label><br />
</div>

<div class="pregap pageinput">
  <button type="submit" id="searchbtn" class="adminsubmit icon do">{$mod->Lang('search')}</button>
</div>

<fieldset class="pregap" id="searchresults_cont">
  <legend>{$mod->Lang('search_results')}:</legend>
  <div id="searchresults"></div>
</fieldset>
