{if $can_set}
{tab_header name='articles' label=_ld($_module,'articles') active=$tab}
{tab_header name='groups' label=_ld($_module,'categories') active=$tab}
{tab_header name='templates' label=_ld('admin','templates') active=$tab}
{tab_header name='settings' label=_ld('admin','settings') active=$tab}

{tab_start name='articles'}
{/if}
<div class="rowbox expand">
  <div class="pageoptions boxchild">
    {if $can_add}
    <a href="{cms_action_url action=addarticle}">{admin_icon icon='newobject.gif' alt=_ld($_module,'addarticle')} {_ld($_module,'addarticle')}</a>&nbsp;
    {/if}
    {if isset($formstart_itemsfilter)}
    <a id="toggle_filter" title="{_ld($_module,'tip_viewfilter')}">{admin_icon icon=$filterimage}
    {if $curcategory != ''}
    <span id="filter_active">{_ld($_module,'prompt_filtered')}</span>
    {else}
    {_ld($_module,'prompt_filter')}
    {/if}</a>
    {/if}
  </div>{*boxchild*}
{if $itemcount > 0 && isset($rowchanger)}
  <div class="boxchild">
   {_ld($_module,'pageof','<span id="cpage">1</span>',"<span id='tpage' style='margin-right:0.5em;'>`$totpg`</span>")}
   {$rowchanger}{_ld($_module,'pagerows')}
   <a href="javascript:pagefirst()">{_ld($_module,'first')}</a>
   <a href="javascript:pageprev()">{_ld($_module,'previous')}</a>
   <a href="javascript:pagenext()">{_ld($_module,'next')}</a>
   <a href="javascript:pagelast()">{_ld($_module,'last')}</a>
  </div>{*boxchild*}
{/if}
</div>{*rowbox*}
{if $itemcount > 0}
{form_start}
<table class="pagetable{if $itemcount > 1} table_sort{/if}" id="articlelist">
  <thead>
    <tr>
      <th{if $itemcount > 1} class="nosort"{/if}>#</th>
      <th{if $itemcount > 1} class="{ldelim}sss:'text'{rdelim}"{/if}>{$titletext}</th>
      <th{if $itemcount > 1} class="{ldelim}sss:'text'{rdelim}"{/if}>{$categorytext}</th>
      <th{if $itemcount > 1} class="{ldelim}sss:'publishat'{rdelim}"{/if}>{$startdatetext}</th>
      <th{if $itemcount > 1} class="{ldelim}sss:'publishat'{rdelim}"{/if}>{$enddatetext}</th>
      <th class="pageicon{if $itemcount > 1} {ldelim}sss:'icon'{rdelim}{/if}">{$statustext}</th>{*if papp*}
      <th class="pageicon{if $itemcount > 1} nosort{/if}">&nbsp;</th>{*if pmod &||$pdel*}
      <th class="pageicon{if $itemcount > 1} nosort{/if}"><input type="checkbox" id="selectall" value="1" title="{_ld($_module,'selectall')}" /></th>{*if pANY*}
    </tr>
  </thead>
  <tbody>{foreach $items as $entry}
    <tr class="{cycle values='row1,row2'}">
{strip}
      <td>{$entry->id}</td>
      <td>{$entry->title}</td>
      <td>{$entry->category}</td>
      <td>{$entry->startdate}</td>
      <td>{if $entry->expired}
        <div class="important">{$entry->enddate}</div>
        {else}
          {$entry->enddate}
        {/if}
      </td>
      <td{if isset($entry->approve_link)} style="text-align:center">{$entry->approve_link}{else}>{/if}</td>
      <td>
        {if isset($entry->editlink)}{$entry->editlink} {$entry->copylink}{/if}
        {if isset($entry->deletelink)} {$entry->deletelink}{/if}
      </td>
      <td>
        <input type="checkbox" name="{$actionid}sel[]" value="{$entry->id}" title="{_ld($_module,'tip_bulk')}" />
      </td>
{/strip}
    </tr>
    {/foreach}</tbody>
</table>
{else}
<div class="pageinfo">{if $curcategory == ''}{_ld($_module,'noarticles')}{else}{_ld($_module,'noarticlesinfilter')}{/if}</div>
{/if}

<div class="pageoptions rowbox{if isset($addlink) && $itemcount > 10} expand">
  <div class="boxchild">
    <div>{$addlink}</div>
  </div>
  {else}" style="justify-content:flex-end">{/if}
  {if $itemcount > 0}
  <div class="boxchild" id="bulkactions">
    {cms_help 0=$_module key='help_bulk' title=_ld($_module,'prompt_bulk')}
    <label class="boxchild" for="bulk_action">{_ld($_module,'with_selected')}:</label>&nbsp;
    <select id="bulk_action" name="{$actionid}bulk_action">
      <option value="setpublished">{_ld($_module,'bulk_setpublished')}</option>
      <option value="setdraft">{_ld($_module,'bulk_setdraft')}</option>
      <option value="setcategory">{_ld($_module,'bulk_setcategory')}</option>
      {if isset($submit_massdelete)}
      <option value="delete">{_ld($_module,'bulk_delete')}</option>
      {/if}
    </select>
    <div id="category_box" style="display:inline-block;">
      <select id="bulk_category" name="{$actionid}bulk_category">
        {html_options options=$bulkcategories selected=$curcategory}    </select>
    </div>
    <button type="submit" id="bulk_submit" class="adminsubmit icon do">{_ld('admin','submit')}</button>
  </div>{*boxchild*}
{/if}{*$itemcount > 0*}
</div>{*rowbox*}
</form>

{if isset($formstart_itemsfilter)}
<div id="itemsfilter" title="{$filtertext}" style="display: none;">
  {$formstart_itemsfilter}
  <div class="pageoverflow">
    <label class="pagetext" for="selcat">{$label_filtercategory}:</label>
    {cms_help 0=$_module key='help_articles_filtercategory' title=$label_filtercategory}
    <div class="pageinput postgap">
      <select id="selcat" name="{$actionid}filter_category">
        {html_options options=$categorylist selected=$curcategory}     </select>
    </div>
    <label class="pagetext" for="childcats">{$label_filterinclude}:</label>
    {cms_help 0=$_module key='help_articles_filterchildcats' title=$label_filterinclude}
    <div class="pageinput">
      <input id="childcats" type="checkbox" name="{$actionid}filter_descendants" value="1"{if $filter_descendants} checked="checked"{/if} />
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
