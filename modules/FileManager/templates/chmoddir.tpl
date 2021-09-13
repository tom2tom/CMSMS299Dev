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
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{$mod->Lang('setpermissions')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </p>
</div>

</form>
