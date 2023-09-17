{if !empty($list_all_types)}
<table id="typelist" class="pagetable">
  <thead>
  <tr>
   <th class="{literal}{sss:numeric}{/literal}">{_ld('layout','prompt_id')}</th>
   <th class="{literal}{sss:text}{/literal}">{_ld('layout','prompt_name')}</th>
   <th class="pageicon nosort"></th>
   <th class="pageicon nosort"></th>
   <th class="pageicon nosort"></th>
   <th class="pageicon nosort"></th>
  </tr>
  </thead>
  <tbody>
{strip}
  {foreach $list_all_types as $type} {$tid=$type->get_id()}
   {$url="edittpltype.php{$urlext}&type=$tid"}
   {cycle values="row1,row2" assign='rowclass'}
{/strip}
   <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
    <td>{$tid}</td>{strip}
    <td>{$tmp=$type->get_description()}
     <a href="{$url}"{if $tmp} class="action tooltip" data-cms-description="{$tmp|summarize}"{else} class="action"{/if} title="{_ld('layout','title_edit_type')}">{$type->get_langified_display_value()}</a>
    </td>
    {$ul=!$type->locked()}
    <td class="pagepos icons_wide">
    {$t=_ld('layout','prompt_locked')}
     <span class="locked" data-type-id="{$tid}" title="{$t}"{if $ul} style="display:none"{/if}>{admin_icon icon='icons/extra/block.gif' title=$t}</span>
    </td>
    <td class="pagepos icons_wide">
    {$t=_ld('layout','prompt_steal_lock')}
    <a class="steal_lock" href="{$url}&steal=1" data-type-id="{$tid}" title="{$t}" accesskey="e"{if $ul} style="display:none"{/if}>{admin_icon icon='permissions.gif' title=$t}</a>
    </td>
    <td class="pagepos icons_wide">
    {$t=_ld('layout','title_edit_type')}
    <a class="action" href="{$url}" title="{$t}"{if !$ul} style="display:none"{/if}>{admin_icon icon='edit.gif' title=$t}</a>
    </td>
    <td class="pagepos icons_wide">
    {if $type->get_dflt_flag()}
    {$t=_ld('layout','title_reset_factory')}
     <a class="action" href="templateoperations.php{$urlext}&op=reset&type={$tid}" title="{$t}"{if !$ul} style="display:none"{/if}>{admin_icon icon='icons/extra/reset.gif' title=$t}</a>
    {/if}
    </td>{/strip}
   </tr>
  {/foreach}
  </tbody>
</table>
{else}
<p class="information">{_ld('layout','info_no_types')}</p>
{/if}
