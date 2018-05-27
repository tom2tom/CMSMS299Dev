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
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon do">{$mod->Lang('setpermissions')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </p>
</div>

</form>
