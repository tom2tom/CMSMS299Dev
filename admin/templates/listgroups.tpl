<div class="pagecontainer">
  {if $padd}
  <div class="pageoptions">
    <a href="{$addurl}{$urlext}">{$iconadd}</a>
    <a href="{$addurl}{$urlext}" class="pageoptions">{lang('addgroup')}</a>
  </div>
  <br />
  {/if}
  {$maintitle}
  {if $pagination}
  <p class="pagewarning visible">{lang('show_groups_message')}</p>
  <br />
  <p class="pageshowrows">{$pagination}</p>
  {/if}
  <table class="pagetable">
    <thead>
      <tr>
        <th class="pagew60">{lang('name')}</th>
        <th class="pagepos">{lang('active'}</th>
        {if $access}
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
          {if $access}
          <a href="{$editurl}{$urlext}&amp;group_id={$one->id}" title="{$one->description}">{$one->name}</a>
          {else}
          <span title="{$one->description}">{$one->name}</span>
          {/if}
        </td>
        <td class="pagepos">
          {if $one->id == 1}{$icontrue}{elseif $one->active}{$icontrue}{else}{$iconfalse}{/if}
        </td>
        {if $access}
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
          <a href="{$deleteurl}{$urlext}&amp;group_id={$one->id}" onclick="return confirm('{cms_html_entity_decode(lang('deleteconfirm', $one->name))}');">{$icondelete}</a>
          {/if}
        </td>
        {/if}
{/strip}
        </tr>
      {/if} {/foreach}
    </tbody>
  </table>
  {if $padd && count($grouplist) > 20}
  <br />
  <div class="pageoptions">
    <a href="{$addurl}{$urlext}">{$iconadd}</a>
    <a href="{$addurl}{$urlext}" class="pageoptions">{lang('addgroup')}</a>
  </div>
  {/if}
</div>
