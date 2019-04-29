{if isset($list_groups) && count($list_groups) > 1}
<p class="pageinfo">{lang_by_realm('layout','info_tpl_groupdragdrop')}</p>
{/if}

<div class="pageinfo">{lang_by_realm('layout','info_tpl_groups')}</div>
<div class="pageoptions pregap"> {$url="edittplgroup.php`$urlext`"}
 <a href="{$url}" title="{lang_by_realm('layout','create_group')}">{admin_icon icon='newobject.gif'}</a>
 <a href="{$url}">{lang_by_realm('layout','create_group')}</a>
</div>
{if isset($list_groups)}
<table id="grouplist" class="pagetable" style="width:auto;">
 <thead>
  <tr>
    <th title="{lang_by_realm('layout','title_group_id')}">{lang_by_realm('layout','prompt_id')}</th>
    <th title="{lang_by_realm('layout','title_group_name')}">{lang_by_realm('layout','prompt_name')}</th>
    <th title="{lang_by_realm('layout','title_group_members')}">{lang_by_realm('layout','members')}</th>
    <th class="pageicon"></th>{* menu *}
  </tr>
 </thead>
 <tbody>
  {foreach $list_groups as $group}{$gid=$group->get_id()}{$url="edittplgroup.php`$urlext`&grp=`$gid`"}
  <tr class="{cycle values='row1,row2'} sortable-table" id="grp_{$gid}">
    <td><a href="{$url}" title="{lang_by_realm('layout','prompt_edit')}">{$gid}</a></td>
    <td><a href="{$url}" title="{lang_by_realm('layout','prompt_edit')}">{$group->get_name()}</a></td>
    <td>{$group->get_members_summary()}</td>
    <td><span context-menu="Templategroup{$gid}">{admin_icon icon='menu.gif' alt='menu' title=lang_by_realm('layout','title_menu') class='systemicon'}</span></td>
  </tr>
    {/foreach}
 </tbody>
</table>
<div id="grpmenus">
 {foreach $grpmenus as $menu}{$menu}{/foreach}
</div>
{else}
<p class="information">{lang_by_realm('layout','info_no_groups')}</p>
{/if}
