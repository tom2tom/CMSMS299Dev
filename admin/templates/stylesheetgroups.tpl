{if !empty($list_groups)}
<table id="grouplist" class="pagetable" style="width:auto;">
  <thead>
    <tr>
      {if $manage_stylesheets}<th title="{lang_by_realm('layout','title_group_id')}">{lang_by_realm('layout','prompt_id')}</th>{/if}
      <th title="{lang_by_realm('layout','title_css_name')}">{lang_by_realm('layout','prompt_name')}</th>
      <th title="{lang_by_realm('layout','title_group_members')}">{lang_by_realm('layout','members')}</th>
      {if $manage_stylesheets}<th class="pageicon"></th>{/if} {* menu *}
    </tr>
  </thead>
  <tbody>
    {foreach $list_groups as $group}{$gid=$group->get_id()}
    {$url="editcssgroup.php`$urlext`&grp=`$gid`"}
    <tr class="{cycle values='row1,row2'} sortable-table" id="cat_{$gid}">
      {if $manage_stylesheets}<td><a href="{$url}" title="{lang_by_realm('layout','prompt_edit')}">{$gid}</a></td>{/if}
      <td>{if $manage_stylesheets}<a href="{$url}" title="{lang_by_realm('layout','prompt_edit')}">{$group->get_name()}</a>{else}{$group->get_name()}{/if}</td>
      <td>{$group->get_members_summary()}</td>
      {if $manage_stylesheets}
      <td><span context-menu="Sheetsgroup{$gid}">{admin_icon icon='menu.gif' alt='menu' title=lang_by_realm('layout','title_menu') class='systemicon'}</span></td>
      {/if}
    </tr>
    {/foreach}
  </tbody>
</table>
{if $manage_stylesheets && !empty($list_groups)}
<div id="grpmenus">
 {foreach $grpmenus as $menu}{$menu}{/foreach}
</div>
{/if} {* manage etc *}
{else}
<p class="information">{lang_by_realm('layout','info_no_groups')}</p>
{/if}
