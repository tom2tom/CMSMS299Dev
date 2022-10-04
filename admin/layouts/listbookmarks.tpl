{if $padd}
<br>
<div class="pageoptions">
  <a href="{$addurl}{$urlext}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}" class="pageoptions">{_la('addbookmark')}</a>
</div>
<br>
{/if}
{if $pagination}
<div class="pagewarn">{_la('show_shortcuts_message')}</div>
<br>
<p class="pageshowrows">{$pagination}</p>
{/if}
<table class="pagetable">
  <thead>
    <tr>
      <th>{_la('name')}</th>
      <th>{_la('url')}</th>
      {if $access}
      <th class="pageicon"></th>
      <th class="pageicon"></th>
      {/if}
    </tr>
  </thead>
  <tbody>
    {foreach $marklist as $one} {if $one@index>=$minsee && $one@index<=$maxsee}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>
        {if $access}
        <a href="{$editurl}{$urlext}&bookmark_id={$one->bookmark_id}">{$one->title}</a>
        {else}
        {$one->title}
        {/if}
      </td>
      <td>{$one->url}</td>
      {if $access}
      <td class="pagepos icons_wide">
        <a href="{$editurl}{$urlext}&bookmark_id={$one->bookmark_id}">{$iconedit}</a>
      </td>
      <td class="pagepos icons_wide">
{*TODO replace onclick handler*}
        <a href="{$deleteurl}{$urlext}&bookmark_id={$one->bookmark_id}" onclick="cms_confirm_linkclick(this,'{cms_html_entity_decode(_la('deleteconfirm', $one->title))}');return false;">{$icondel}</a>
      </td>
      {/if}
{/strip}
    </tr>
    {/if} {/foreach}
  </tbody>
</table>
{if $padd && count($marklist) > 20}
<br>
<div class="pageoptions">
  <a href="{$addurl}{$urlext}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}" class="pageoptions">{_la('addbookmark')}</a>
</div>
{/if}
