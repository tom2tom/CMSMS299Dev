<h4 class="pregap">{_ld($_module,'included')}</h4>
<table id="designtemplates" class="pagetable draggable" style="width:auto;">
 <thead>
  <tr>
   <th>{_ld($_module,'prompt_name')}</th>
   <th>{_ld($_module,'prompt_description')}</th>
   <th>{_ld($_module,'prompt_modified')}</th>
  </tr>
 </thead>
 <tbody class="rsortable">
{if !empty($design_templates)}{foreach $design_templates as $id => $row}
  <tr><td><span class="rowid" style="display:none;">{$id}</span>{$row.name}</td><td{if $row.desc} title="{$row.desc}"{/if}>{$row.desc|summarize:6}</td><td>{$row.when|cms_date_format:'timed'}</td></tr>
{/foreach}{/if}
  <tr class="placeholder"><td>&nbsp;</td><td></td><td></td</tr>
 </tbody>
</table>
<h4 class="pregap">{_ld($_module,'others')}</h4>
<table id="othertemplates" class="pagetable draggable" style="width:auto;">
 <thead>
  <tr>
   <th>{_ld($_module,'prompt_name')}</th>
   <th>{_ld($_module,'prompt_description')}</th>
   <th>{_ld($_module,'prompt_modified')}</th>
  </tr>
 </thead>
 <tbody class="rsortable">
{if !empty($undesign_templates)}{foreach $undesign_templates as $id => $row}
  <tr><td><span class="rowid" style="display:none;">{$id}</span>{$row.name}</td><td{if $row.desc} title="{$row.desc}"{/if}>{$row.desc|summarize:6}</td><td>{$row.when|cms_date_format:'timed'}</td></tr>
{/foreach}{/if}
  <tr class="placeholder"><td>&nbsp;</td><td></td<td></td</tr>
 </tbody>
</table>
