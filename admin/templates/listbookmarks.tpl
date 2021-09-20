{if $padd}
<br />
<div class="pageoptions">
  <a href="{$addurl}{$urlext}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}" class="pageoptions">{_ld('admin','addbookmark')}</a>
</div>
<br />
{/if}
{if $pagination}
<div class="pagewarn">{_ld('admin','show_shortcuts_message')}</div>
<br />
<p class="pageshowrows">{$pagination}</p>
{/if}
<table class="pagetable">
  <thead>
    <tr>
      <th class="pagew60">{_ld('admin','name')}</th>
      <th class="pagew60">{_ld('admin','url')}</th>
      {if $access}
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
      {/if}
    </tr>
  </thead>
  <tbody>
    {foreach $marklist as $one} {if $one@index>=$minsee && $one@index<=$maxsee}
    <tr class="{cycle values='row1,row2'}">
      {strip}
      <td>
        {if $access}
        <a href="{$editurl}{$urlext}&amp;bookmark_id={$one->bookmark_id}">{$one->title}</a>
        {else}
        {$one->title}
        {/if}
      </td>
      <td>{$one->url}</td>
      {if $access}
      <td>
        <a href="{$editurl}{$urlext}&amp;bookmark_id={$one->bookmark_id}">{$iconedit}</a>
      </td>
      <td>
{*TODO replace onclick handler*}
        <a href="{$deleteurl}{$urlext}&amp;bookmark_id={$one->bookmark_id}" onclick="cms_confirm_linkclick(this,'{cms_html_entity_decode(_ld('admin','deleteconfirm', $one->title))}');return false;">{$icondel}</a>
      </td>
      {/if}
{/strip}
    </tr>
    {/if} {/foreach}
  </tbody>
</table>
{if $padd && count($marklist) > 20}
<br />
<div class="pageoptions">
  <a href="{$addurl}{$urlext}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}" class="pageoptions">{_ld('admin','addbookmark')}</a>
</div>
{/if}
