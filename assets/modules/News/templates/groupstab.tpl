<p class="pageinfo">{_ld($_module,'info_categories')}</p>
{if $can_mod}
<div class="rowbox expand">
  <div class="pageoptions boxchild">
    <a href="{cms_action_url action='addcategory'}" title="{_ld($_module,'tip_addcategory')}">{admin_icon icon='newobject.gif'} {_ld($_module,'addcategory')}</a>
  </div>
  {if isset($catcount) && $catcount > 1}
    <div class="boxchild">
     <a href="{cms_action_url action='reorder_cats'}" title="{_ld($_module,'tip_reordercat')}">{admin_icon icon='reorder.gif'} {_ld($_module,'reorder')}</a>
    </div>
  {/if}
</div>{*rowbox*}
{/if}

{if !empty($catcount)}
<table class="pagetable" style="width:auto;">
 <thead>
  <tr>
    <th>{_ld($_module,'category')}</th>
    <th class="pageicon"></th>
  </tr>
 </thead>
 <tbody>
  {foreach $cats as $elem}
  {cycle values="row1,row2" name='cats' assign='rowclass'}
   <tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
    <td>{repeat string='&nbsp;&gt;&nbsp' times=$elem->depth}<a href="{$elem->edit_url}" title="{_ld($_module,'edit')}">{$elem->name}</a></td>
    <td><a href="{$elem->edit_url}" title="{_ld($_module,'edit')}">{admin_icon icon='edit.gif'}</a>
    {if $elem->delete_url}<a href="{$elem->delete_url}" title="{_ld($_module,'delete')}" class="del_cat">{admin_icon icon='delete.gif'}</a>{/if}</td>
   </tr>
  {/foreach}
 </tbody>
</table>
{else}
<p class="pageinfo">{_ld($_module,'info_categories')}</p>
{/if}
