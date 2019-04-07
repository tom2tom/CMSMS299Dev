{if $list_all_types}
<table class="pagetable">
  <thead>
    <tr>
      <th>{$mod->Lang('prompt_id')}</th>
      <th>{$mod->Lang('prompt_name')}</th>
      <th class="pageicon"></th>
    </tr>
  </thead>
  <tbody>
  {foreach $list_all_types as $type}
   {cycle values="row1,row2" assign='rowclass'}
   {$reset_url=''}
   {if $type->get_dflt_flag()}
     {cms_action_url action='admin_reset_type' type=$type->get_id() assign='reset_url'}
   {/if}
   {cms_action_url action='admin_edit_type' type=$type->get_id() assign='edit_url'}
   <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
      <td>{$type->get_id()}</td>
      <td>
        <a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{$type->get_langified_display_value()}</a>
      </td>
      <td>
      <a href="{$edit_url}" title="{$mod->Lang('prompt_edit')}">{admin_icon icon='edit.gif'}</a>
      {if $has_add_right}
        <a href="{cms_action_url action=admin_edit_template import_type=$type->get_id()}" title="{$mod->Lang('prompt_import')}">{admin_icon icon='import.gif'}</a>
      {/if}
      </td>
    </tr>
  {/foreach}
  </tbody>
</table>
{/if}
