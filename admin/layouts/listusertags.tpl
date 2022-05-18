<div class="information">
 <p>
 {_ld('tags','tag_info')}<br />
 {_ld('tags','tag_info2')}<br />
 {_ld('tags','udt__scope')}
 </p>
</div>
<br />
{if $pmod && count($tags) > 20}
<div class="pageoptions">
  <a href="{$addurl}{$urlext}&tagname=-1" title="{_la('add_usrplg')}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}&tagname=-1">{_la('add_usrplg')}</a>
</div>
<br />
{/if}
<table class="pagetable">
  <thead>
    <tr>
      <th>{_la('name')}</th>
      <th>{_la('description')}</th>
      {if $pmod}
      <th class="pageicon"></th>
      <th class="pageicon"></th>
      {/if}
    </tr>
  </thead>
  <tbody>
    {foreach $tags as $tag}
    <tr class="{cycle values='row1,row2'}">
      {strip}{$n=$tag.name}
      <td>
       {if $pmod}
        <a href="{$editurl}{$urlext}&tagname={$n}" title="{_la('edit_usrplg', {$n})}">{$n}</a>
       {else}
        {$n}
       {/if}
       {if ($tag.help)}&nbsp;<a href="javascript:getParms('{$n}')">{$iconinfo}</a>{/if}
      </td>
      <td>{$tag.description}</td>
      {if $pmod}
      <td>
        <a href="{$editurl}{$urlext}&tagname={$n}">{$iconedit}</a>
      </td>
      <td>
        <a href="javascript:doDelete('{$n}')">{$icondel}</a>
      </td>
      {/if}
{/strip}
    </tr>
    {/foreach}
  </tbody>
</table>
{if $pmod}
<br />
<div class="pageoptions">
  <a href="{$addurl}{$urlext}&tagname=-1" title="{_la('add_usrplg')}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}&tagname=-1">{_la('add_usrplg')}</a>
</div>
{/if}

<div id="params_dlg" title="{_ld('tags','user_tag')}" style="display:none;">
<h4 id="namer" style="text-align:center;"></h4>
<h4>{_la('parameters')}:</h4>
<p id="params"></p>
</div>
