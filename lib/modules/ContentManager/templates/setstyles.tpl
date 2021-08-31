{if $sheets}
<table id="allsheets" class="pagetable" style="width:auto;">
 <thead>
  <tr>
   <th>{$mod->Lang('colhdr_id')}</th>
   <th>{$mod->Lang('colhdr_name')}</th>
{if $grouped}   <th>{$mod->Lang('colhdr_provides')}</th>{/if}
   <th>{$mod->Lang('colhdr_apply')}</th>
  </tr>
 </thead>
 <tbody class="rsortable">
  {foreach $sheets as $obj}<tr{if $obj->id < 0} class="sheetsgroup"{/if}>
   <td>{if $obj->id >= 0}{$obj->id}{else}{-($obj->id)}{/if}</td>
   <td>{$obj->name}</td>
{if $grouped}   <td>{$obj->members}</td>{/if}
   <td style="text-align:center;"><input type="checkbox" name="{$actionid}styles[]" value="{$obj->id}"{if $obj->checked} checked="checked"{/if} /></td>
  </tr>{/foreach}
 </tbody>
</table>
{/if}
