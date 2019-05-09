<div class="pageoptions">
  <p class="pageoptions">
    <a href="{cms_action_url action='addcategory'}" title="{$mod->Lang('addcategory')}">{admin_icon icon='newobject.gif'} {$mod->Lang('addcategory')}</a> &nbsp; {if isset($catcount) && $catcount > 1}<a href="{cms_action_url action='admin_reorder_cats'}" title="{$mod->Lang('reorder')}">{admin_icon icon='reorder.gif'} {$mod->Lang('reorder')}</a>{/if}
  </p>
</div>

{if !empty($catcount)}
<table class="pagetable">
  <thead>
    <tr>
      <th>{$mod->Lang('category')}</th>
      <th class="pageicon">&nbsp;</th>
      <th class="pageicon">&nbsp;</th>
    </tr>
  </thead>
  <tbody>
    {foreach $cats as $entry}
    <tr class="{cycle name='cats' values='row1,row2'}">
      <td>{repeat string='&nbsp;&gt;&nbsp' times=$entry->depth}<a href="{$entry->edit_url}" title="{$mod->Lang('edit')}">{$entry->name}</a></td>
      <td><a href="{$entry->edit_url}" title="{$mod->Lang('edit')}">{admin_icon icon='edit.gif'}</a></td>
      <td>{if $entry->delete_url}<a href="{$entry->delete_url}" title="{$mod->Lang('delete')}" class="del_cat">{admin_icon icon='delete.gif'}</a>{/if}</td>
    </tr>
    {/foreach}
  </tbody>
</table>
{else}
<p class="pageinfo">{$mod->Lang('info_categories')}</p>
{/if}
