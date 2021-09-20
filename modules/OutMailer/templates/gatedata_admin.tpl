<input type="hidden" name="{$actionid}{$space}~gate_id" value="{$gateid}" />
{if !empty($hidden)}{$hidden}{/if}
{if !empty($gatetitle)}
<fieldset class="gate">
<legend>{$gatetitle}</legend>
{/if}
<div style="margin-top:0;">
{$help}
</div>
<div class="pageoverflow" style="margin-top:1em;width:auto;display:inline-block;">
<table class="pagetable gatedata">
<thead><tr>
<th>{$title_title}</th>
<th>{$title_value}</th>
<th>{$title_encrypt}</th>
<th>{$title_apiname}</th>
<th>{$title_enabled}</th>
<th>{$title_help}</th>
<th>{$title_select}</th>
</tr></thead>
<tbody>
{foreach $data as $one}{cycle values='row1,row2' assign=rowclass}
<tr class="{$rowclass}" onmouseover="this.className='{$rowclass}hover';" onmouseout="this.className='{$rowclass}';">
<td><input type="text" name="{$actionid}{$one->space}title" size="15" value="{$one->title}" /></td>
<td><input type="text" name="{$actionid}{$one->space}value" size="{if !empty($one->size)}{$one->size}{else}15{/if}" value="{$one->value}" /></td>
<td><input type="checkbox" name="{$actionid}{$one->space}encrypt"{if $one->encrypt} checked="checked"{/if} /></td>
<td><input type="text" name="{$actionid}{$one->space}apiname" size="15" value="{$one->apiname}" /></td>
<td><input type="checkbox" name="{$actionid}{$one->space}enabled"{if $one->enabled} checked="checked"{/if} /></td>
<td>{if !empty($one->help)}{$one->help}>{/if}</td>
<td><input type="checkbox" name="{$actionid}{$one->space}sel" /></td>
</tr>
{/foreach}
</tbody>
</table>
<div class="pageoptions" style="margin-top:1em;">
{$additem}
{if $dcount}<div style="float:right;">
 <button type="submit" name="{$actionid}{$space}~delete" class="adminsubmit icon delete" title="{_ld($_module,'delete_tip')}">{_ld($_module,'delete')}</button>
</div>
<div style="float:clear"></div>{/if}
</div>
{if !empty($gatetitle)}
</fieldset>
{/if}
