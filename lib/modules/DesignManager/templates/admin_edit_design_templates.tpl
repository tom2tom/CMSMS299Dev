{if $all_templates}
<p class="pageinfo">{$mod->Lang('info_edittemplate_templates_tab')}</p>
<fieldset>
  <legend>{$mod->Lang('available_templates')}</legend>
  <table class="unselected draggable">
  <tbody class="unselected rsortable">
    {foreach $all_templates as $id => $name}{if !$design_templates || !in_array($id,$design_templates)}
    <tr><td>{$name}<input type="hidden" name="{$actionid}tplsel[{$id}]" value="0" /></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
</fieldset>
<fieldset>
  <legend>{$mod->Lang('attached_templates')}</legend>
  <table class="selected draggable">
  <tbody class="selected rsortable">
    {foreach $all_templates as $id => $name}{if $design_templates && in_array($id,$design_templates)}
    <tr><td>{$name}<input type="hidden" name="{$actionid}tplsel[{$id}]" value="1" /></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
</fieldset>
{else}
<p class="pageinfo">{$mod->Lang('info_no_templates')}</p>
{/if}
