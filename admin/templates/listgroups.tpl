{if $pmod}
<div class="pageoptions postgap">
  <a href="{$addurl}{$urlext}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}" class="pageoptions">{_ld('admin','addgroup')}</a>
</div>
{/if}
{if $pagination}
<div class="pagewarn">{_ld('admin','show_groups_message')}</div>
<br />
<p class="pageshowrows">{$pagination}</p>
{/if}
<table class="pagetable">
  <thead>
    <tr>
      <th class="pagew60">{_ld('admin','name')}</th>
      <th class="pagepos">{_ld('admin','active')}</th>
      {if $pmod}
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
      {/if}
    </tr>
  </thead>

  <tbody>
    {foreach $grouplist as $one} {if $one@index>=$minsee && $one@index<=$maxsee}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>
        {if $pmod}
        <a href="{$editurl}{$urlext}&amp;group_id={$one->id}" title="{$one->description}">{$one->name}</a>
        {else}
        <span title="{$one->description}">{$one->name}</span>
        {/if}
      </td>
      <td class="pagepos">
        {if $one->id == 1}{$icontrue}{elseif $one->active}{$icontrue}{else}{$iconfalse}{/if}
      </td>
      {if $pmod}
      <td class="pagepos icons_wide">
        <a href="{$permurl}{$urlext}&amp;group_id={$one->id}">{$iconperms}</a>
      </td>
      <td class="pagepos icons_wide">
        <a href="{$assignurl}{$urlext}&amp;group_id={$one->id}">{$iconassign}</a>
      </td>
      <td class="pagepos icons_wide">
        <a href="{$editurl}{$urlext}&amp;group_id={$one->id}">{$iconedit}</a>
      </td>
      <td class="pagepos icons_wide">
        {if $one->id != 1}
{*TODO replace onclick attribute*}
        <a href="{$deleteurl}{$urlext}&amp;group_id={$one->id}" onclick="cms_confirm_linkclick(this,'{cms_html_entity_decode(_ld('admin','deleteconfirm', $one->name))}');return false;">{$icondel}</a>
        {/if}
      </td>
      {/if}
{/strip}
      </tr>
    {/if} {/foreach}
  </tbody>
</table>
{if $pmod && count($grouplist) > 20}
<div class="pageoptions pregap">
  <a href="{$addurl}{$urlext}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}" class="pageoptions">{_ld('admin','addgroup')}</a>
</div>
{/if}
