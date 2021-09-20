<div class="pageoverflow">
  <label class="pagetext" for="searchtext">{_ld($_module,'search_text')}:</label>
  <div class="pageinput">
    <input type="text" id="searchtext" value="{$saved_search.search_text|default:''}" size="40" maxlength="80" placeholder="{_ld($_module,'placeholder_search_text')}" />
  </div>
</div>

<p class="pagetext">{_ld($_module,'filter')}:</p>
<div class="pageinput pageoverflow">
  <input type="checkbox" id="filter_all" value="1" title="{_ld($_module,'desc_filter_all')}" />&nbsp;
  <label class="check" for="filter_all">{_ld($_module,'all')}</label><br />
  <br />
  <div id="filter_box">
  {foreach $slaves as $slave}
   <input type="checkbox" class="filter_toggle" id="{$slave.class}"{if !empty($slave.description)} title="{$slave.description}"{/if} value="{$slave.class}"{if !empty($saved_search.slaves) && in_array($slave.class,$saved_search.slaves)} checked="checked"{/if} />&nbsp;
   <label class="check" for="{$slave.class}"{if !empty($slave.description)} title="{$slave.description}"{/if}>{$slave.name}</label><br />
  {/foreach}
  </div>
  <div id="opts_box">
  <input type="checkbox" class="filter_toggle" id="search_descriptions" value="1"{if !empty($saved_search.search_descriptions)} checked="checked"{/if} />&nbsp;
  <label class="check" for="search_descriptions">{_ld($_module,'lbl_search_desc')}</label><br />
  <br />
  <input type="checkbox" id="case_sensitive" value="1"{if !empty($saved_search.search_casesensitive)} checked="checked"{/if} />&nbsp;
  <label class="check" for="case_sensitive">{_ld($_module,'lbl_cased_search')}</label><br />
  {$t=_ld($_module,'desc_verbatim_search')}<input type="checkbox" id="verbatim_search" title="{$t}" value="1"{if !empty($saved_search.verbatim_search)} checked="checked"{/if} />&nbsp;
  <label class="check" for="verbatim_search" title="{$t}">{_ld($_module,'lbl_verbatim_search')}</label><br />
  {$t=_ld($_module,'desc_save_search')}<input type="checkbox" id="save_search" title="{$t}" value="1"{if !empty($saved_search.save_search)} checked="checked"{/if} />&nbsp;
  <label class="check" for="save_search" title="{$t}">{_ld($_module,'lbl_save_search')}</label>
  </div>
</div>

<div class="pregap pageinput">
  <button type="submit" id="searchbtn" class="adminsubmit icon do">{_ld($_module,'search')}</button>
</div>

<fieldset class="pregap" id="searchresults_cont">
  <legend>{_ld($_module,'search_results')}:</legend>
  <div id="searchresults"></div>
</fieldset>
