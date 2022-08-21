{if $pmod}
<div class="pageoptions postgap">
  <a href="{$addurl}{$urlext}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}" class="pageoptions">{_la('addgroup')}</a>
</div>
{/if}
{if $tblpages > 1}
<div class="browsenav postgap">
  <span id="tblpagelink">
   <span id="ftpage" class="pagelink">{_ld('layout','pager_first')}</span>&nbsp;|&nbsp;
  {if $tblpages > 2}
   <span id="pspage" class="pagelink">{_ld('layout','pager_previous')}</span>&nbsp;&lt;&gt;&nbsp;
   <span id="ntpage" class="pagelink">{_ld('layout','pager_next')}</span>&nbsp;|&nbsp;
  {/if}
   <span id="ltpage" class="pagelink">{_ld('layout','pager_last')}</span>&nbsp;
   ({_ld('layout','pageof','<span id="cpage">1</span>',"<span id='tpage'>{$tblpages}</span>")})&nbsp;&nbsp;
  </span>
  <select id="tblpagerows" name="tblpagerows">
    {html_options options=$pagelengths selected=$currentlength}
  </select>&nbsp;&nbsp;{_ld('layout','pager_rowspp')}{*TODO sometimes show 'pager_rows'*}
</div>
{/if}{* tblpages *}
<table id="groupslist" class="pagetable">
  <thead>
    <tr>
      <th class="{literal}{sss:text}{/literal}">{_la('name')}</th>
      <th class="{literal}{sss:intfor}{/literal}">{_la('active')}</th>
      {if $pmod}
      <th class="pageicon nosort"></th>{* menu *}
      {/if}
    </tr>
  </thead>
  <tbody>
    {foreach $grouplist as $one}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>
        {if $pmod}
        <a href="{$editurl}{$urlext}&group_id={$one.id}" title="{$one.description}">{$one.name}</a>
        {else}
        <span title="{$one.description}">{$one.name}</span>
        {/if}
      </td>
      <td class="pagepos" data-sss="{if $one.active}1{else}0{/if}">
       {$one.status}
      </td>
      {if $pmod}
      <td class="pagepos">
       <span class="action" context-menu="Group{$one.id}">{$iconmenu}</span></td>
      </td>
      {/if}
{/strip}
      </tr>
    {/foreach}
  </tbody>
</table>
{if $pmod && count($grouplist) > 20}
<div class="pageoptions pregap">
  <a href="{$addurl}{$urlext}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}" class="pageoptions">{_la('addgroup')}</a>
</div>
{/if}
{if !empty($groupmenus)}
<div id="groupmenus">
  {foreach $groupmenus as $menu}{$menu}
{/foreach}
</div>
{/if}
