{if $can_mod}
<div class="rowbox expand">
  <div class="pageoptions boxchild">
    <a href="{cms_action_url action='addcategory'}" title="{$mod->Lang('tip_addcategory')}">{admin_icon icon='newobject.gif'} {$mod->Lang('addcategory')}</a> 
  </div>
  {if isset($catcount) && $catcount > 1}
    <div class="boxchild">
     <a href="{cms_action_url action='reorder_cats'}" title="{$mod->Lang('tip_reordercat')}">{admin_icon icon='reorder.gif'} {$mod->Lang('reorder')}</a>
    </div>
  {/if}
</div>{*rowbox*}
{/if}

{if !empty($catcount)}
<table class="pagetable">
 <thead>
  <tr>
    <th>{$mod->Lang('category')}</th>
    <th class="pageicon"></th>
    <th class="pageicon"></th>
  </tr>
 </thead>
 <tbody>
  {foreach $cats as $elem}
  {cycle values="row1,row2" name='cats' assign='rowclass'}
   <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
    <td>{repeat string='&nbsp;&gt;&nbsp' times=$elem->depth}<a href="{$elem->edit_url}" title="{$mod->Lang('edit')}">{$elem->name}</a></td>
    <td><a href="{$elem->edit_url}" title="{$mod->Lang('edit')}">{admin_icon icon='edit.gif'}</a></td>
    <td>{if $elem->delete_url}<a href="{$elem->delete_url}" title="{$mod->Lang('delete')}" class="del_cat">{admin_icon icon='delete.gif'}</a>{/if}</td>
   </tr>
  {/foreach}
 </tbody>
</table>
{else}
<p class="pageinfo">{$mod->Lang('info_categories')}</p>
{/if}
