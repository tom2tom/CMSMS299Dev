{if $all_stylesheets}
<p class="pageinfo">{$mod->Lang('info_edittemplate_stylesheets_tab')}</p>
<fieldset>
  <legend>{$mod->Lang('available_stylesheets')}</legend>
  <table class="unselected draggable">
  <tbody class="unselected rsortable">
    {foreach $all_stylesheets as $id => $name}{if !$design_stylesheets || !in_array($id,$design_stylesheets)}
    <tr><td>{$name}<input type="hidden" name="{$actionid}design_stylesheetssel[{$id}]" value="0" /></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
</fieldset>
<fieldset>
  <legend>{$mod->Lang('attached_stylesheets')}</legend>
  <table class="selected draggable">
  <tbody class="selected rsortable">
    {foreach $all_stylesheets as $id => $name}{if $design_stylesheets && in_array($id,$design_stylesheets)}
    <tr><td>{$name}<input type="hidden" name="{$actionid}design_stylesheetssel[{$id}]" value="1" /></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
</fieldset>
{else}
<p class="pageinfo">{$mod->Lang('info_no_stylesheets')}</p>
{/if}
