<h4 class="pregap">{$mod->Lang('included')}</h4>
<table id="designtemplates" class="pagetable draggable" style="width:auto;">
 <thead>
  <tr>
   <th>{$mod->Lang('prompt_name')}</th>
   <th>{$mod->Lang('prompt_description')}</th>
   <th>{$mod->Lang('prompt_modified')}</th>
  </tr>
 </thead>
 <tbody class="rsortable">
{if !empty($design_templates)}{foreach $design_templates as $id => $row}
  <tr><td><span class="rowid" style="display:none;">{$id}</span>{$row.name}</td><td{if $row.desc} title="{$row.desc}"{/if}>{$row.desc|summarize:6}</td><td>{$row.when|cms_date_format}</td></tr>
{/foreach}{/if}
  <tr class="placeholder"><td>&nbsp;</td><td></td><td></td</tr>
 </tbody>
</table>
<h4 class="pregap">{$mod->Lang('others')}</h4>
<table id="othertemplates" class="pagetable draggable" style="width:auto;">
 <thead>
  <tr>
   <th>{$mod->Lang('prompt_name')}</th>
   <th>{$mod->Lang('prompt_description')}</th>
   <th>{$mod->Lang('prompt_modified')}</th>
  </tr>
 </thead>
 <tbody class="rsortable">
{if !empty($undesign_templates)}{foreach $undesign_templates as $id => $row}
  <tr><td><span class="rowid" style="display:none;">{$id}</span>{$row.name}</td><td{if $row.desc} title="{$row.desc}"{/if}>{$row.desc|summarize:6}</td><td>{$row.when|cms_date_format}</td></tr>
{/foreach}{/if}
  <tr class="placeholder"><td>&nbsp;</td><td></td<td></td</tr>
 </tbody>
</table>
