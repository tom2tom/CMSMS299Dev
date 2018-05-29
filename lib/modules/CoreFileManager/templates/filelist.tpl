{if count($items) > 0}
 <table id="main-table" class="pagetable">
  <thead><tr>
   <th class="{ldelim}sss:'fname'{rdelim}"></th>
   <th class="center {ldelim}sss:'fname'{rdelim}">{$mod->Lang('name')}</th>
   <th class="center {ldelim}sss:'fint'{rdelim}">{$mod->Lang('size')}</th>
   <th class="center {ldelim}sss:'fint'{rdelim}">{$mod->Lang('modified')}</th>
{if !$FM_IS_WIN}
   <th class="center">{$mod->Lang('perms')}</th>
{/if}
   <th class="center {ldelim}sss:false{rdelim}">{$mod->Lang('actions')}</th>
{if !$FM_READONLY}
   <th class="{ldelim}sss:false{rdelim}"><input type="checkbox" id="checkall" onclick="checkall_toggle(this);"></th>
{/if}
  </tr></thead>
  <tbody>
{foreach $items as $one}
   <tr class="{cycle values='row1,row2'}">
    <td class="icon" data-sort="{if $one->dir}.{/if}{$one->icon}"><i class="{$one->icon}"></i></td>
    <td class="filename" data-sort="{if $one->dir}.{/if}{$one->name}"{if $one->is_link} title="{$pointer} {$one->realpath}"{/if}>{if $one->link}{$one->link}{else}{$one->name}{/if}</td>
    <td data-sort="{if $one->dir}0"{else}{$one->rawsize}" title="{$one->rawsize} {$bytename}"{/if}>{$one->size}</td>
    <td data-sort="{$one->rawtime}">{$one->modat}</td>
{if !$FM_IS_WIN}
    <td style="text-align:center;">{$one->perms}</td>
{/if}
    <td>{$one->acts}</td>
{if !$FM_READONLY}
    <td><input type="checkbox" name="{$actionid}sel['{$one->sel}']" value="1" /></td>
{/if}
   </tr>
{/foreach}
  </tbody>
 </table>
 <br />
{/if}{*count $items*} 
 {$mod->Lang('summary', $filescount, $folderscount, $totalcount)}
