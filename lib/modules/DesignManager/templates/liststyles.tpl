{tab_header name='sheets' label=$mod->Lang('prompt_stylesheets')}
{tab_header name='groups' label=$mod->Lang('prompt_css_groups')}

{tab_start name='sheets'}

{if $has_add_right}
<div class="pageoptions">
  {cms_action_url action='admin_edit_css' assign='url'}{$t=$mod->Lang('create_stylesheet')}
  <a href="{$url}" title="{$t}">{admin_icon icon='newobject.gif'}</a>
  <a href="{$url}">{$t}</a>
</div>
{/if}
<div id="stylesheet_area"></div>{* ajax-populated sheets list *}

{tab_start name='groups'}

<div class="pageinfo">{$mod->Lang('info_css_groups')}</div>
{if $has_add_right}
<div class="pageoptions">
  {cms_action_url action='admin_edit_category' assign='url'}{$t=$mod->Lang('create_group')}
  <a href="{$url}" title="{$t}">{admin_icon icon='newobject.gif'}</a>
  <a href="{$url}">{$t}</a>
</div>
{/if}
{if $list_groups}
<table id="grouplist" class="pagetable" style="width:auto;">
  <thead>
    <tr>
      {if $manage_stylesheets}<th title="{$mod->Lang('title_group_id')}">{$mod->Lang('prompt_id')}</th>{/if}
      <th title="{$mod->Lang('title_group_name')}">{$mod->Lang('prompt_name')}</th>
      {if $manage_stylesheets}<th class="pageicon"></th>{/if}
    </tr>
  </thead>
  <tbody>
    {foreach $list_groups as $group}{$gid=$group->get_id()}
    {cms_action_url action='admin_edit_category' cat=$gid assign='edit_url'}
    <tr class="{cycle values='row1,row2'} sortable-table" id="cat_{$gid}">
      {if $manage_stylesheets}<td><a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{$gid}</a></td>{/if}
      <td>{if $manage_stylesheets}<a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{$group->get_name()}</a>{else}{$group->get_name()}{/if}</td>
      {if $manage_stylesheets}<td>
        <a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{admin_icon icon='edit.gif'}</a>
        <a href="{cms_action_url action='admin_delete_category' cat=$gid}" class="del_cat" title="{$mod->Lang('prompt_delete')}">{admin_icon icon='delete.gif'}</a>
      </td>{/if}
    </tr>
    {/foreach}
  </tbody>
</table>
{else}
<p class="information">{$mod->Lang('no_group')}</p>
{/if}

{tab_end}
