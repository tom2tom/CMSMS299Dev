{$startform}
<div class="pageoverflow">
  <p class="pagetext">
      <label for="newdir">{$mod->Lang('newdir')}:</label>
  </p>
  <p class="pageinput"><input type="text" name="{$actionid}newdirname" id="newdir" value="{$newdirname}" size="40" /></p>
</div>

<div class="pageoverflow">
  <p class="pagetext"></p>
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit">{$mod->Lang('create')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit iconcancel">{$mod->Lang('cancel')}</button> 
  </p>
</div>
{$endform}
