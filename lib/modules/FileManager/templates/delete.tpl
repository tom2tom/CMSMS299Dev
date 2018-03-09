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
<br />
<div class="pageoverflow">
  <p class="pageinput">
    {if !isset($errors)}
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{$mod->Lang('delete')}</button>
    {/if}
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$cancellabel}</button>
  </p>
</div>
</form>