<fieldset>
  <legend>{$attached_legend}</legend>
{if !empty($all_items)}
  <table class="selected draggable">
  <tbody class="selected rsortable">
    <tr class="placeholder"><td>{$placeholder}</td></tr>
    {if !empty($group_items)}{foreach $all_items as $id => $name}{if isset($group_items[$id])}
    <tr><td>{$name}<input type="hidden" name="member[{$id}]" value="1"></td></tr>
    {/if}{/foreach}{/if}
  </tbody>
  </table>
{/if}
</fieldset>
<fieldset>
  <legend>{$unattached_legend}</legend>
{if !empty($all_items)}
  <table class="unselected draggable">
  <tbody class="unselected rsortable">
    <tr class="placeholder"><td>{$placeholder}</td></tr>
    {foreach $all_items as $id => $name}{if empty($group_items) || !isset($group_items[$id])}
    <tr><td>{$name}<input type="hidden" name="member[{$id}]" value="0"></td></tr>
    {/if}{/foreach}
  </tbody>
  </table>
{/if}
</fieldset>
