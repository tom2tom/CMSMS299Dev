{$startform}

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
    <button type="submit" name="{$actionid}submit" class="adminsubmit icondo">{$mod->Lang('setpermissions')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit iconcancel">{$mod->Lang('cancel')}</button> 
  </p>
</div>

{$endform}
