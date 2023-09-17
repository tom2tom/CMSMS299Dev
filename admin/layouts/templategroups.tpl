{if isset($list_groups) && count($list_groups) > 1}
<p class="pageinfo">{_ld('layout','info_tpl_groupdragdrop')}</p>
{/if}

<div class="pageinfo">{_ld('layout','info_tpl_groups')}</div>
<div class="pageoptions pregap">{$url="edittplgroup.php{$urlext}"}
 <a href="{$url}" title="{_ld('layout','create_group')}">{admin_icon icon='newobject.gif'}</a>
 <a href="{$url}">{_ld('layout','create_group')}</a>
</div>
{if !empty($list_groups)}
<table id="grouplist" class="pagetable">
 <thead>
  <tr>
    <th title="{_ld('layout','title_group_id')}">{_ld('layout','prompt_id')}</th>
    <th title="{_ld('layout','title_group_name')}">{_ld('layout','prompt_name')}</th>
    <th title="{_ld('layout','title_group_members')}">{_ld('layout','member_ids')}</th>
    <th class="pageicon"></th>{* locks/menu *}
  </tr>
 </thead>
 <tbody>{$micon={admin_icon icon='menu.gif' alt='menu' title=_ld('layout','title_menu') class='systemicon'}}
  {foreach $list_groups as $group}{$gid=$group->get_id()}{$url="edittplgroup.php{$urlext}&grp=$gid"}
  {cycle values='row1,row2' assign='rowclass'}
  <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
    <td><a href="{$url}" title="{_ld('layout','prompt_edit')}">{$gid}</a></td>{/strip}
    <td><a href="{$url}" title="{_ld('layout','prompt_edit')}">{$group->get_name()}</a></td>
    <td>{$group->get_members_summary()}</td>
    <td>
      {$ul=!$group->locked()}
      {$t=_ld('layout','prompt_locked')}
      <span class="locked" data-grp-id="{$gid}" title="{$t}"{if $ul} style="display:none"{/if}>{admin_icon icon='icons/extra/block.gif' title=$t}</span>
      {$t=_ld('layout','prompt_steal_lock')}
      <a class="steal_lock" href="{$url}&steal=1" data-grp-id="{$gid}" title="{$t}" accesskey="e"{if $ul} style="display:none"{/if}>{admin_icon icon='permissions.gif' title=$t}</a>
      <span class="action" context-menu="Templategroup{$gid}"{if !$ul} style="display:none"{/if}>{$micon}</span>
    </td>{/strip}
  </tr>
    {/foreach}
 </tbody>
</table>
<div id="grpmenus">
 {foreach $grpmenus as $menu}{$menu}
{/foreach}
</div>
{else}
<p class="information">{_ld('layout','info_no_groups')}</p>
{/if}
