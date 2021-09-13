{$formstart}

<div class="pageoverflow">
  <p class="pagetext">
    <label for="newname">{$newnametext}:</label>
  </p>
  <p class="pageinput">
    <input id="newname" type="text" name="{$actionid}newname" value="{$newname}" size="40" />
  </p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}rename" class="adminsubmit icon do">{$mod->Lang('rename')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </p>
</div>
</form>
