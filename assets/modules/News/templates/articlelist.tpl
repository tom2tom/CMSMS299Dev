{if isset($formstart) }
<div id="filter" title="{$filtertext}" style="display: none;">
  {$formstart}
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$prompt_category}<label for="filter_category">{$t}:</label>
      {cms_help realm=$_module key='help_articles_filtercategory' title=$t}
    </p>
    <p class="pageinput">
      <select id="filter_category" name="{$actionid}category">
      {html_options options=$categorylist selected=$curcategory}
      </select>
      <br />
      {$t=$prompt_showchildcategories}<label for="filter_allcategories">{$t}:</label>
      <input id="filter_allcategories" type="checkbox" name="{$actionid}allcategories" value="yes"{if $allcategories=="yes" } checked="checked"{/if} />
      {cms_help realm=$_module key='help_articles_filterchildcats' title=$t}
    </p>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}submitfilter" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
    <button type="submit" name="{$actionid}resetfilter" class="adminsubmit icon undo">{$mod->Lang('reset')}</button>
  </div>
 </form>
</div>
{/if}

<div class="rowbox expand">
  <div class="pageoptions boxchild">
    {if $can_add}
    <a href="{cms_action_url action=addarticle}">{admin_icon icon='newobject.gif' alt=$mod->Lang('addarticle')} {$mod->Lang('addarticle')}</a>&nbsp;
    {/if}
    <a id="toggle_filter" title="{$mod->Lang('viewfilter')}">{admin_icon icon=$filterimage} {if $curcategory != ''}<span style="font-weight:bold;color:#0f0;">* {$mod->Lang('title_filter')}</span>{else}{$mod->Lang('title_filter')}{/if}</a>
  </div>{*boxchild*}
{if $itemcount > 0 && isset($rowchanger)}
  <div class="boxchild">
   {$mod->Lang('pageof','<span id="cpage">1</span>',"<span id='tpage' style='margin-right:0.5em;'>`$totpg`</span>")}
   {$rowchanger}{$mod->Lang('pagerows')}
   <a href="javascript:pagefirst()">{$mod->Lang('first')}</a>
   <a href="javascript:pageprev()">{$mod->Lang('previous')}</a>
   <a href="javascript:pagenext()">{$mod->Lang('next')}</a>
   <a href="javascript:pagelast()">{$mod->Lang('last')}</a>
  </div>{*boxchild*}
{/if}
</div>{*rowbox*}
{if $itemcount > 0}
{$form2start}
<table class="pagetable{if $itemcount > 1} table_sort{/if}" id="articlelist">
  <thead>
    <tr>
      <th{if $itemcount > 1} class="nosort"{/if}>#</th>
      <th{if $itemcount > 1} class="{ldelim}sss:'text'{rdelim}"{/if}>{$titletext}</th>
      <th{if $itemcount > 1} class="{ldelim}sss:'publishat'{rdelim}"{/if}>{$startdatetext}</th>
      <th{if $itemcount > 1} class="{ldelim}sss:'publishat'{rdelim}"{/if}>{$enddatetext}</th>
      <th{if $itemcount > 1} class="{ldelim}sss:'text'{rdelim}"{/if}>{$categorytext}</th>
      <th class="pageicon{if $itemcount > 1} {ldelim}sss:'icon'{rdelim}{/if}">{$statustext}</th>{*if papp*}
      <th class="pageicon{if $itemcount > 1} nosort{/if}">&nbsp;</th>{*if pmod &||$pdel*}
      <th class="pageicon{if $itemcount > 1} nosort{/if}"><input type="checkbox" id="selall" value="1" title="{$mod->Lang('selectall')}" /></th>{*if pANY*}
    </tr>
  </thead>
  <tbody>{foreach $items as $entry}
    <tr class="{cycle values='row1,row2'}">
{strip}
      <td>{$entry->id}</td>
      <td>{$entry->title}</td>
      <td>{$entry->startdate}</td>
      <td>{if $entry->expired}
        <div class="important">{$entry->enddate}</div>
        {else}
          {$entry->enddate}
        {/if}
      </td>
      <td>{$entry->category}</td>
      <td>{if isset($entry->approve_link)}{$entry->approve_link}{/if}</td>
      <td>
        {if isset($entry->editlink)}{$entry->editlink} {$entry->copylink}{/if}
        {if isset($entry->deletelink)} {$entry->deletelink}{/if}
      </td>
      <td>
        <input type="checkbox" name="{$actionid}sel[]" value="{$entry->id}" title="{$mod->Lang('toggle_bulk')}" />
      </td>
{/strip}
    </tr>
    {/foreach}</tbody>
</table>
{else}
<div class="pagewarn">{if $curcategory == ''}{$mod->Lang('noarticles')}{else}{$mod->Lang('noarticlesinfilter')}{/if}</div>
{/if}

<div class="pageoptions rowbox{if isset($addlink) && $itemcount > 10} expand">
  <div class="boxchild">
    <p>{$addlink}</p>
  </div>
  {else}" style="justify-content:flex-end">{/if}
  {if $itemcount > 0}
  <div class="boxchild" id="bulkactions">
    {cms_help realm=$_module key2='help_bulk' title=$mod->Lang('title_bulk')}
    <label for="bulk_action">{$mod->Lang('with_selected')}:</label>&nbsp;
    <select id="bulk_action" name="{$actionid}bulk_action">
      {if isset($submit_massdelete)}
      <option value="delete">{$mod->Lang('bulk_delete')}</option>
      {/if}
      <option value="setdraft">{$mod->Lang('bulk_setdraft')}</option>
      <option value="setpublished">{$mod->Lang('bulk_setpublished')}</option>
      <option value="setcategory">{$mod->Lang('bulk_setcategory')}</option>
    </select>
    <div id="bulk_category" style="display:inline-block;">
      {$mod->Lang('category')}: {$categoryinput}
    </div>
    <button type="submit" name="{$actionid}submit_bulkaction" id="submit_bulkaction" class="adminsubmit icon do">{$mod->Lang('submit')}</button>
  </div>{*boxchild*}
{/if}{*$itemcount > 0*}
</div>{*rowbox*}
</form>
