<h3>{_ld($_module,'actiondelete')}:</h3>
{if isset($errors)}
{$cancellabel=_ld($_module,'return')}
{else}
{$cancellabel=_ld($_module,'cancel')}
{/if}

{$formstart}
<div class="pageoverflow">
  <p class="pagetext">{_ld($_module,'deleteselected')}:</p>
  <p class="pageinput">
    {'<br>'|implode:$sel}
  </p>
</div>
<br>
<div class="pageoverflow">
  <p class="pageinput">
    {if !isset($errors)}
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{_ld($_module,'delete')}</button>
    {/if}
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$cancellabel}</button>
  </p>
</div>
</form>
