{$formstart}

{$filename}
{$path}
{*$newmode*}

<div class="pageoverflow">
  <p class="pagetext">{$newmodetext}</p>
  <p class="pageinput">{$modeswitch}</p>
</div>

<div class="pageoverflow">
  <p class="pagetext">&nbsp;</p>
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{_ld($_module,'setpermissions')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </p>
</div>

</form>
