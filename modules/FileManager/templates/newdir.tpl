{$formstart}
<div class="pageoverflow">
  <p class="pagetext">
   <label for="newdir">{_ld($_module,'newdir')}:</label>
  </p>
  <p class="pageinput">
   <input type="text" name="{$actionid}newdirname" id="newdir" value="{$newdirname}" size="40" />
  </p>
</div>
<br />
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}newdir" class="adminsubmit icon do">{_ld($_module,'create')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </p>
</div>
</form>
