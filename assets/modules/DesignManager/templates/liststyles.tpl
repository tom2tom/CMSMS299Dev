{tab_header name='sheets' label=_ld($_module,'prompt_stylesheets')}
{tab_header name='groups' label=_ld($_module,'prompt_css_groups')}

{tab_start name='sheets'}

{if $has_add_right}
<div class="pageoptions">
  {cms_action_url action='edit_css' assign='url'}{$t=_ld($_module,'create_stylesheet')}
  <a href="{$url}" title="{$t}">{admin_icon icon='newobject.gif'}</a>
  <a href="{$url}">{$t}</a>
</div>
{/if}
<div id="stylesheet_area"></div>{* ajax-populated sheets list *}

{tab_start name='groups'}

<div class="pageinfo">{_ld($_module,'info_css_groups')}</div>
{if $has_add_right}
<div class="pageoptions">
  {cms_action_url action='edit_category' assign='url'}{$t=_ld($_module,'create_group')}
  <a href="{$url}" title="{$t}">{admin_icon icon='newobject.gif'}</a>
  <a href="{$url}">{$t}</a>
</div>
{/if}
{if !empty($list_groups)}
<table class="pagetable" id="grouplist">
  <thead>
    <tr>
      {if $manage_stylesheets}<th title="{_ld($_module,'title_group_id')}">{_ld($_module,'prompt_id')}</th>{/if}
      <th title="{_ld($_module,'title_group_name')}">{_ld($_module,'prompt_name')}</th>
      {if $manage_stylesheets}<th class="pageicon"></th>
      <th class="pageicon"></th>{/if}
    </tr>
  </thead>
  <tbody>
    {foreach $list_groups as $group}{$gid=$group->get_id()}
    {cms_action_url action='edit_category' cat=$gid assign='edit_url'}
    <tr class="{cycle values='row1,row2'} sortable-table" id="cat_{$gid}">
      {if $manage_stylesheets}<td><a href="{$edit_url}" title="{_ld($_module,'prompt_edit')}">{$gid}</a></td>{/if}
      <td>{if $manage_stylesheets}<a href="{$edit_url}" title="{_ld($_module,'prompt_edit')}">{$group->get_name()}</a>{else}{$group->get_name()}{/if}</td>
      {if $manage_stylesheets}
      <td class="pagepos icons_wide">
        <a href="{$edit_url}" title="{_ld($_module,'prompt_edit')}">{admin_icon icon='edit.gif'}</a>
      </td>
      <td class="pagepos icons_wide">
        <a href="{cms_action_url action='delete_category' cat=$gid}" class="del_cat" title="{_ld($_module,'prompt_delete')}">{admin_icon icon='delete.gif'}</a>
      </td>{/if}
    </tr>
    {/foreach}
  </tbody>
</table>
{else}
<p class="information">{_ld($_module,'no_group')}</p>
{/if}

{tab_end}
