<h3>{$mod->Lang('createthumbnail')}</h3>
{$startform}
<div class="pageoverflow">
  <p class="pagetext">{$mod->Lang('info_createthumb')}:</p>
  <p class="pagetext">{$thumb}</p>
</div>
<div class="pageoverflow">
  <p class="pagetext"></p>
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit iconadd">{$mod->Lang('create')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit iconcancel">{$mod->Lang('cancel')}</button>
  </p>
</div>
{$endform}