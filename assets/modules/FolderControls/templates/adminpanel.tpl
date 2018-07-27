<div class="information">
  <p>{$mod->Lang('info_cset')}</p>
</div>

<div class="pageoptions">
  <a href="{$addurl}">{$iconadd}</a>
  <a href="{$addurl}" class="pageoptions">{$mod->Lang('title_add_cset')}</a>
</div>

{if !empty($sets)}
<table class="pagetable">
  <thead>
    <tr>
      <th>{$mod->Lang('text_id')}</th>
      <th>{$mod->Lang('name')}</th>
      <th>{$mod->Lang('text_reltop')}</th>
      <th>{$mod->Lang('default')}</th>
      <th>{$mod->Lang('text_created')}</th>
      <th>{$mod->Lang('text_modified')}</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
    </tr>
  </thead>
  <tbody>
    {foreach $sets as $one}
    <tr class="{cycle values='row1,row2'}">
      <td>{$one->id}</td>
      <td><a href="{$editurl}&amp;setid={$one->id}" title="{$mod->Lang('edit')}">{$one->name}</a></td>
      <td>{$one->reltop}</td>
      <td>
        {if $one->id == $dfltset_id}
        {$iconyes}
        {else}
        <a href="{$defaulturl}&amp;setid={$one->id}" title="{$mod->Lang('desc_default')}">{$iconno}</a>
        {/if}
      </td>
      <td>{$one->created}</td>
      <td>{$one->modified}</td>
      <td><a href="{$editurl}&amp;setid={$one->id}" class="pageoptions">{$iconedit}</a></td>
      <td><a href="{$delurl}&amp;setid={$one->id}" class="pageoptions">{$icondel}</a></td>
    </tr>
    {/foreach}
  </tbody>
</table>
{else}
  <p class="information">{$mod->Lang('no_cset')}</p>
{/if}
