<h4 class="pregap">{_ld($_module,'included')}</h4>
<table id="designsheets" class="pagetable draggable" style="width:auto;">
 <thead>
  <tr>
   <th>{_ld($_module,'prompt_name')}</th>
   <th>{_ld($_module,'prompt_description')}</th>
   <th>{_ld($_module,'prompt_modified')}</th>
  </tr>
 </thead>
 <tbody class="rsortable">
{if !empty($design_stylesheets)}{foreach $design_stylesheets as $id => $row}
  <tr><td><span class="rowid" style="display:none;">{$id}</span>{$row.name}</td><td{if $row.desc} title="{$row.desc}"{/if}>{$row.desc|summarize:6}</td><td>{$row.when|cms_date_format:'timed'}</td></tr>
{/foreach}{/if}
  <tr class="placeholder"><td>&nbsp;</td><td></td><td></td</tr>
 </tbody>
</table>
<h4 class="pregap">{_ld($_module,'others')}</h4>
<table id="othersheets" class="pagetable draggable" style="width:auto;">
 <thead>
  <tr>
   <th>{_ld($_module,'prompt_name')}</th>
   <th>{_ld($_module,'prompt_description')}</th>
   <th>{_ld($_module,'prompt_modified')}</th>
  </tr>
 </thead>
 <tbody class="rsortable">
{if !empty($undesign_stylesheets)}{foreach $undesign_stylesheets as $id => $row}
  <tr><td><span class="rowid" style="display:none;">{$id}</span>{$row.name}</td><td{if $row.desc} title="{$row.desc}"{/if}>{$row.desc|summarize:6}</td><td>{$row.when|cms_date_format:'timed'}</td></tr>
{/foreach}{/if}
  <tr class="placeholder"><td>&nbsp;</td><td></td<td></td</tr>
 </tbody>
</table>
