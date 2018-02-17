<h3>{$mod->Lang('actiondelete')}:</h3>
{if isset($errors)}
{$cancellabel=$mod->Lang('return')}
{else}
{$cancellabel=$mod->Lang('cancel')}
{/if}

{$startform}
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('deleteselected')}:</p>
  <p class="pageinput">
    {'<br />'|implode:$selall}
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext"></p>
  <p class="pageinput">
    {if !isset($errors)}
    <button type="submit" name="{$actionid}submit" class="adminsubmit icondo">{$mod->Lang('delete')}</button>
    {/if}
    <button type="submit" name="{$actionid}cancel" class="adminsubmit iconcancel">{$cancellabel}</button>
  </p>
</div>
{$endform}