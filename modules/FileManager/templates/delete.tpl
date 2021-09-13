<h3>{$mod->Lang('actiondelete')}:</h3>
{if isset($errors)}
{$cancellabel=$mod->Lang('return')}
{else}
{$cancellabel=$mod->Lang('cancel')}
{/if}

{$formstart}
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('deleteselected')}:</p>
  <p class="pageinput">
    {'<br />'|implode:$sel}
  </p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    {if !isset($errors)}
    <button type="submit" name="{$actionid}delete" class="adminsubmit icon do">{$mod->Lang('delete')}</button>
    {/if}
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$cancellabel}</button>
  </p>
</div>
</form>