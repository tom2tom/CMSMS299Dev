<div class="pagecontainer">
  {if $padd}
  <div class="pageoptions">
    <a href="{$addurl}{$urlext}">{$iconadd}</a>
    <a href="{$addurl}{$urlext}" class="pageoptions">{lang('addbookmark')}</a>
  </div>
  <br />
  {/if}
  {$maintitle}
  {if $pagination}
  <p class="pagewarning visible">{lang('show_shortcuts_message')}</p>
  <br />
  <p class="pageshowrows">{$pagination}</p>
  {/if}
  <table class="pagetable">
    <thead>
      <tr>
        <th class="pagew60">{lang('name')}</th>
        <th class="pagew60">{lang('url')}</th>
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
          <a href="{$deleteurl}{$urlext}&amp;bookmark_id={$one->bookmark_id}" onclick="return confirm('{cms_html_entity_decode(lang('deleteconfirm', $one->title))}');">{$icondel}</a>
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
    <a href="{$addurl}{$urlext}" class="pageoptions">{lang('addbookmark')}</a>
  </div>
  {/if}
</div>
