<h3>{$mod->Lang('title_uninstall_module')}</h3>
<h4>{$mod->Lang('lbl_module')}: {$module_name}</h4>
<h4>{$mod->Lang('lbl_version')}: {$module_version}</h4>
<div class="red">{$msg}</div>
{form_start mod=$module_name}
<div class="pageoverflow">
  <p class="pageinput">
    <label>
      <input type="checkbox" name="{$actionid}confirm" value="1" /> {$mod->Lang('confirm_action')}
    </label>
  </p>
</div>
<div class="pageoverflow">
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit iconundo">{$mod->Lang('uninstall')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit iconcancel">{$mod->Lang('cancel')}</button>
  </p>
</div>
{form_end}