<h3>{_ld($_module,'createthumbnail')}</h3>
{$formstart}
<div class="pageoverflow">
  <p class="pagetext">{_ld($_module,'info_createthumb')}:</p>
  <p class="pagetext">{$thumb}</p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}thumb" class="adminsubmit icon add">{_ld($_module,'create')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </p>
</div>
</form>
