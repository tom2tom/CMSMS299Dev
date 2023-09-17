{if $can_set}
{tab_header name='articles' label=_ld($_module,'articles') active=$tab}
{tab_header name='groups' label=_ld($_module,'categories') active=$tab}
{tab_header name='templates' label=_la('templates') active=$tab}
{tab_header name='settings' label=_la('settings') active=$tab}
{tab_start name='articles'}
{/if}
{$xcats = !empty($catcount) && $catcount > 1}
<div class="rowbox expand">
  <div class="pageoptions boxchild">
    {if $can_add}
    <a href="{cms_action_url action=addarticle}">{admin_icon icon='newobject.gif' alt=_ld($_module,'addarticle')} {_ld($_module,'addarticle')}</a>&nbsp;
    {/if}
    {if $xcats && isset($formstart_itemsfilter)}
    <a id="toggle_filter" title="{_ld($_module,'tip_viewfilter')}">{admin_icon icon='icons/extra/filter'}
    {if $curcategory != ''}
    <span id="filter_active">{_ld($_module,'prompt_filtered')}</span>
    {else}
    {_ld($_module,'prompt_filter')}
    {/if}</a>
    {/if}

  </div>{*boxchild*}
{if $itemcount > 0 && isset($rowchanger)}
  <div class="boxchild">
   <span id="ipglink">
   <span id="ftpage" class="pagechange">{_ld('layout','pager_first')}</span>
   {if $tplpages > 2}
   <span id="pspage" class="pagechange">{_ld('layout','pager_previous')}</span>
   <span id="ntpage" class="pagechange">{_ld('layout','pager_next')}</span>
   {/if}
   <span id="ltpage" class="pagechange">{_ld('layout','pager_last')}</span>
   {_ld('layout','pageof','<span id="cpage">1</span>',"<span id='tpage' style='margin-right:0.5em'>{$totpg}</span>")}
   </span>
   {$rowchanger}{_ld('layout','pager_rowspp')}{*TODO sometimes show 'pager_rows'*}
  </div>{*boxchild*}
{/if}
</div>{*rowbox*}
{if !empty($items)}
{form_start}
<table class="pagetable{if $itemcount > 1} table_sort{/if}" id="articlelist">
  <thead>
    <tr>
      <th{if $itemcount > 1} class="nosort"{/if}>#</th>
      <th{if $itemcount > 1} class="{literal}{sss:text}{/literal}"{/if}>{$titletext}</th>
      <th{if $itemcount > 1} class="{literal}{sss:text}{/literal}"{/if}>{$categorytext}</th>
      <th{if $itemcount > 1} class="{literal}{sss:intfor}{/literal}"{/if}>{$startdatetext}</th>
      <th{if $itemcount > 1} class="{literal}{sss:intfor}{/literal}"{/if}>{$enddatetext}</th>
      <th class="pageicon{if $itemcount > 1} {literal}{sss:intfor}{/literal}{/if}">{$statustext}</th>{*if papp*}
      <th class="pageicon{if $itemcount > 1} nosort{/if}"></th>{*if pmod &/| $pdel 0..4 icons in this column TODO replace by context menu *}
      <th class="pageicon{if $itemcount > 1} nosort{/if}"><input type="checkbox" id="selectall" value="1" title="{_ld($_module,'selectall')}"></th>{*if pANY*}
    </tr>
  </thead>
  <tbody>{foreach $items as $entry}
    <tr class="{cycle values='row1,row2'}">
{strip}
      <td>{$entry->id}</td>
      <td>{$entry->title}</td>
      <td>{$entry->category}</td>
      <td{if $itemcount > 1} data-sss="{$entry->start}"{/if}>{$entry->startdate}</td>
      <td{if $itemcount > 1} data-sss="{$entry->end}"{/if}>{if $entry->expired}
        <div class="important">{$entry->enddate}</div>
        {else}
          {$entry->enddate}
        {/if}
      </td>
      <td{if $itemcount > 1} data-sss="{$entry->approve_mode}"{/if}>{if isset($entry->approve_link)}{$entry->approve_link}{/if}</td>
      <td>
        {if isset($entry->editlink)}{$entry->editlink} {$entry->copylink}{if $xcats} {$entry->movelink}{/if}{/if}
        {if isset($entry->deletelink)} {$entry->deletelink}{/if}
      </td>
      <td>
        <input type="checkbox" name="{$actionid}sel[]" value="{$entry->id}" title="{_ld($_module,'tip_bulk')}">
      </td>
{/strip}
    </tr>
    {/foreach}</tbody>
</table>

<div class="pageoptions rowbox{if isset($addlink) && $itemcount > 10} expand">
  <div class="boxchild">
    <div>{$addlink}</div>
  </div>
  {else}" style="justify-content:flex-end">{/if}
  {if $itemcount > 0}
  <div class="boxchild" id="bulkactions">
    {cms_help realm=$_module key='help_bulk' title=_ld($_module,'prompt_bulk')}
    <label class="boxchild" for="bulk_action">{_ld($_module,'with_selected')}:</label>&nbsp;
    <select id="bulk_action" name="{$actionid}bulk_action">
      {html_options options=$bulkactions}    </select>
    <div id="category_box" style="display:inline-block">
      <select id="bulk_category" name="{$actionid}bulk_category">
        {html_options options=$bulkcategories selected=$curcategory}      </select>
    </div>
    <button type="submit" id="bulk_submit" class="adminsubmit icon do">{_la('submit')}</button>
  </div>{*boxchild*}
{/if}{*$itemcount > 0*}
</div>{*rowbox*}
</form>
{else}
<div class="pageinfo">{if $curcategory == ''}{_ld($_module,'noarticles')}{else}{_ld($_module,'noarticlesinfilter')}{/if}</div>
{/if}
{if $xcats && isset($formstart_itemsfilter)}
<div id="itemsfilter" title="{$filtertext}" style="display:none">
  {$formstart_itemsfilter}
  <div class="pageoverflow">
    <label class="pagetext" for="selcat">{$label_filtercategory}:</label>
    {cms_help realm=$_module key='help_articles_filtercategory' title=$label_filtercategory}
    <div class="pageinput postgap">
      <select id="selcat" name="{$actionid}filter_category">
        {html_options options=$categorylist selected=$curcategory}      </select>
    </div>
    <label class="pagetext" for="childcats">{$label_filterinclude}:</label>
    {cms_help realm=$_module key='help_articles_filterchildcats' title=$label_filterinclude}
    <div class="pageinput">
      <input id="childcats" type="checkbox" name="{$actionid}filter_descendants" value="1"{if $filter_descendants} checked{/if}>
    </div>
  </div>
 </form>
</div>
{/if}

{if $can_set}
{tab_start name='groups'}
{include file='module_file_tpl:News;groupstab.tpl'}
{tab_start name='templates'}
{include file='module_file_tpl:News;templatestab.tpl'}
{tab_start name='settings'}
{include file='module_file_tpl:News;settingstab.tpl'}

{tab_end}
{/if}

{if $xcats && $itemcount > 0}
<div id="catselector" title="{$selectortext}" style="display:none">
  {$formstart_catselector}
  <input type="hidden" id="movedarticle" name="{$actionid}articleid">
  <div class="pageoverflow">
   <select id="destcat" name="{$actionid}tocategory">
    {html_options options=$categorylist selected=-1}    </select>
  </div>
 </form>
</div>
{/if}
