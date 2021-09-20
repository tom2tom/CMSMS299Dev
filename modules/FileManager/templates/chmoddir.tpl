{$formstart}

{$filename}
{$path}
{$newmode}


<div class="pageoverflow">
  <p class="pagetext">{$newmodetext}</p>
  <p class="pageinput">{$modetable}</p>
</div>

<div class="pageoverflow">
  <p class="pagetext">{$quickmodetext}:</p>
  <p class="pageinput">{$quickmodeinput}</p>
</div>

<div class="pageoverflow">
  <p class="pagetext">{$recurseinputtext}</p>
  <p class="pageinput">{$recurseinput}</p>
</div>


<div class="pageoverflow">
  <p class="pagetext">&nbsp;</p>
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{_ld($_module,'setpermissions')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </p>
</div>

</form>
