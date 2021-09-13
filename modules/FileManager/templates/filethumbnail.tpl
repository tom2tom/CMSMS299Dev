<h3>{$mod->Lang('createthumbnail')}</h3>
{$formstart}
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('info_createthumb')}:</p>
  <p class="pagetext">{$thumb}</p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}thumb" class="adminsubmit icon add">{$mod->Lang('create')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </p>
</div>
</form>
