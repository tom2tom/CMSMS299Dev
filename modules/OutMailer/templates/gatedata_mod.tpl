<input type="hidden" name="{$actionid}{$space}~gate_id" value="{$gateid}">
{if !empty($hidden)}{$hidden}{/if}
<fieldset class="settings">
<legend>{$gatetitle}</legend>
<div class="pageoverflow" style="margin-top:0;">
{foreach $data as $one}
 <p class="pagetext"{if $one@first} style="margin-top:0;"{/if}>{$one->title}</p>
{if $one->apiname}<div class="pageinput">
  <input type="text" name="{$actionid}{$one->space}value" size="{if !empty($one->size)}{$one->size}{else}15{/if}" value="{$one->value}">
  <input type="hidden" name="{$actionid}{$one->space}encrypt" value="{$one->encrypt}">
 </div>{/if}
{if !empty($one->help)}<p class="pageinput">{$one->help}</p>{/if}
{/foreach}
</div>
</fieldset>
