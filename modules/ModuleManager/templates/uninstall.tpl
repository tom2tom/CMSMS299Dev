<h3>{_ld($_module,'uninstall_module')}</h3>
<h4>{_ld($_module,'module')}: {$module_name}</h4>
<h4>{_ld($_module,'version')}: {$module_version}</h4>
<div class="warning">{$msg}</div>
{form_start mod=$module_name}
<div class="pageoverflow">
  <p class="pageinput">
    <label>
      <input type="checkbox" name="{$actionid}confirm" value="1" /> {_ld($_module,'confirm_action')}
    </label>
  </p>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon undo">{_ld($_module,'uninstall')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
</div>
</form>