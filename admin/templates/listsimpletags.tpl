<div class="information">
 <p>
 {lang_by_realm('tags','tag_info')}<br />
 {lang_by_realm('tags','tag_info2')}<br />
 {lang('usertag_scope')}
 </p>
</div>
<br />
{if $pmod && count($tags) > 20}
<div class="pageoptions">
  <a href="{$addurl}{$urlext}&amp;tagname=-1" title="{lang('addusertag')}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}&amp;tagname=-1">{lang('addusertag')}</a>
</div>
<br />
{/if}
<table class="pagetable">
  <thead>
    <tr>
      <th>{lang('name')}</th>
      <th>{lang('description')}</th>
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
        <a href="{$editurl}{$urlext}&amp;tagname={$n}" title="{lang('editusertag', {$n})}">{$n}</a>
       {else}
        {$n}
       {/if}
       {if ($tag.help)}&nbsp;<a href="javascript:get_help({$n})">{$iconinfo}</a>{/if}
      </td>
      <td>{$tag.description}</td>
      {if $pmod}
      <td>
        <a href="{$editurl}{$urlext}&amp;tagname={$n}">{$iconedit}</a>
      </td>
      <td>
        <a href="javascript::doDelete({$n})">{$icondel}</a>
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
  <a href="{$addurl}{$urlext}&amp;tagname=-1" title="{lang('addusertag')}">{$iconadd}</a>
  <a href="{$addurl}{$urlext}&amp;tagname=-1">{lang('addusertag')}</a>
</div>
{/if}

<div id="params_dlg" style="display:none;">

</div>
