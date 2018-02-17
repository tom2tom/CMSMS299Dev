{$startform}

<div class="pageoverflow">
  <p class="pagetext">
      <label for="newname">{$newnametext}:</label>
  </p>
  <p class="pageinput"><input id="newname" type="text" name="{$actionid}newname" value="{$newname}" size="40" /></p>
</div>

<div class="pageoverflow">
  <p class="pagetext"></p>
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icondo">{$mod->Lang('rename')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit iconcancel">{$mod->Lang('cancel')}</button> 
  </p>
</div>

{$endform}
