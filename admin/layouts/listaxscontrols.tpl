{if $pmod}
<div class="pageoptions">
 <a href="{$openurl}{$urlext}&edit=0">{$iadd}&nbsp;{_ld('controlsets','add_set')}</a>
</div>
{/if}
{if !empty($ctrlsets)}
<table class="pagetable">
 <thead>
  <tr>
   <th>{_ld('controlsets','set_id')}</th>
   <th>{_la('name')}</th>
   <th>{_ld('controlsets','reltop2')}</th>
   <th>{_ld('controlsets','default')}</th>
   <th>{_ld('controlsets','created')}</th>
   <th>{_ld('controlsets','modified2')}</th>
   {if $pmod}
   <th class="pageicon"></th>
   <th class="pageicon"></th>
   {/if}
  </tr>
 </thead>
 <tbody>
{foreach $ctrlsets as $cset}
  <tr class="{cycle values='row1,row2'}">{strip}
   <td>{$cset->id}</td>
   <td>{if $pmod}
    <a href="{$openurl}{$urlext}&edit={$cset->id}" title="{_ld('controlsets','edit_set')}">{$cset->name}</a>
    {else}
     <a href="{$openurl}{$urlext}&see={$cset->id}" title="{_ld('controlsets','view_set')}">{$cset->name}</a>
    {/if}</td>
   <td>{$cset->reltop}</td>
   {if $pmod}
   <td class="pagepos icons_wide">{if $cset->id == $dfltset_id}
    {$iyes}
       {else}
    <a href="{$selfurl}{$urlext}&default={$cset->id}">{$ino}</a>
       {/if}
   </td>
   {else}
   <td class="pagepos icons_wide">{if $cset->id == $dfltset_id}{$iyes}{else}{$ino}{/if}</td>
   {/if}
   <td>{$cset->create_date|cms_date_format:'timed'}</td>
   <td>{$cset->modified_date|cms_date_format:'timed'}</td>
   {if $pmod}
   <td class="pagepos icons_wide"><a href="{$openurl}{$urlext}&id={$cset->id}" class="pageoptions">{$iedt}</a></td>
   <td class="pagepos icons_wide"><a href="{$selfurl}{$urlext}&delete={$cset->id}" class="pageoptions delete">{$idel}</a></td>
   {/if}
{/strip}</tr>
{/foreach}
 </tbody>
</table>
{elseif $pmod}
<p class="information">{_ld('controlsets','no_set_add')}</p>
{else}
<p class="information">{_ld('controlsets','no_set')}</p>
{/if}