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
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$prompt_sorting}<label for="filter_sortby">{$t}:</label>
      {cms_help realm=$_module key='help_articles_sortby' title=$t}
    </p>
    <p class="pageinput">
      <select id="filter_sorting" name="{$actionid}sortby">
      {html_options options=$sortlist selected=$sortby}
      </select>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$prompt_pagelimit}<label for="filter_pagelimit">{$t}:</label>
      {cms_help realm=$_module key='help_articles_pagelimit' title=$t}
      </p>
    <p class="pageinput">
      <select id="filter_pagelimit" name="{$actionid}pagelimit">
      {html_options options=$pagelimits selected=$sortby}
      </select>
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
  {if $itemcount > 0 && $pagecount > 1}
  <div class="pageoptions boxchild">
    {form_start}
    {$mod->Lang('prompt_page')}&nbsp;
      <select name="{$actionid}pagenumber">
        {cms_pageoptions numpages=$pagecount curpage=$pagenumber}
      </select>&nbsp;
    <button type="submit" name="{$actionid}paginate" class="adminsubmit icon do">{$mod->Lang('prompt_go')}</button>
    </form>
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
      <th class="pageicon{if $itemcount > 1} {ldelim}sss:'icon'{rdelim}{/if}">{$statustext}</th>
      <th class="pageicon{if $itemcount > 1} nosort{/if}">&nbsp;</th>
      <th class="pageicon{if $itemcount > 1} nosort{/if}">&nbsp;</th>
      <th class="pageicon{if $itemcount > 1} nosort{/if}"><input type="checkbox" id="selall" value="1" title="{$mod->Lang('selectall')}" /></th>
    </tr>
  </thead>
  <tbody>
    {foreach $items as $entry}
    <tr class="{$entry->rowclass}">
{strip}
      <td>{$entry->id}</td>
      <td>
        {if isset($entry->edit_url)}
        <a href="{$entry->edit_url}" title="{$mod->Lang('editarticle')}">{$entry->news_title|cms_escape}</a>
        {else}
        {$entry->news_title|cms_escape}
        {/if}
      </td>
      <td>{if !empty($entry->u_enddate)}{$entry->u_startdate}{/if}</td>
      <td>{if $entry->expired == 1}
        <div class="important">
          {$entry->u_enddate}
        </div>
        {else}
          {$entry->u_enddate}
        {/if}
      </td>
      <td>{$entry->category}</td>
      <td>{if isset($entry->approve_link)}{$entry->approve_link}{/if}</td>
      <td>
        {if isset($entry->edit_url)}
        <a href="{$entry->edit_url}" title="{$mod->Lang('editarticle')}">{admin_icon icon='edit.gif'}</a>
        {/if}
      </td>
      <td>
        {if isset($entry->delete_url)}
        <a class="delete_article" href="{$entry->delete_url}" title="{$mod->Lang('delete_article')}">{admin_icon icon='delete.gif'}</a>
        {/if}
      </td>
      <td>
        <input type="checkbox" name="{$actionid}sel[]" value="{$entry->id}" title="{$mod->Lang('toggle_bulk')}" />
      </td>
{/strip}
    </tr>
    {/foreach}
  </tbody>
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
