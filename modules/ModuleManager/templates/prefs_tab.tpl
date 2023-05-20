{if isset($message)}<p>{$message}</p>{/if}

{form_start action='setprefs'}
<input type="hidden" id="inp_reset" name="{$actionid}reseturl" value="">
{if isset($module_repository)}
  <div class="pageoverflow">
    <label class="pagetext" for="mr_url">{_ld($_module,'prompt_repository_url')}:</label>
    <div class="pageinput">
      <input type="text" name="{$actionid}url" id="mr_url" size="55" value="{$module_repository}">
      <button type="submit" name="{$actionid}reset" id="reseturl" class="adminsubmit icon undo">{_ld($_module,'reset')}</button>
    </div>
  </div>
{/if}

  <div class="pageoverflow">
    <label class="pagetext" for="chunksize">{_ld($_module,'prompt_dl_chunksize')}:</label>
    {cms_help 0=$_module key='help_dl_chunksize' title=_ld($_module,'prompt_dl_chunksize')}
    <div class="pageinput">
      <input type="text" name="{$actionid}dl_chunksize" id="chunksize" value="{$dl_chunksize}" size="3" maxlength="3">
    </div>
  </div>

  <div class="pageoverflow">
    <label class="pagetext" for="latestdepends">{_ld($_module,'latestdepends')}:</label>
    {cms_help 0=$_module key='help_latestdepends' title=_ld($_module,'latestdepends')}
    <input type="hidden" name="{$actionid}latestdepends" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}latestdepends" id="latestdepends" value="1"{if $latestdepends} checked{/if}>
    </div>
  </div>

{if !empty($develop_mode)}
  <div class="pageoverflow">
    <label class="pagetext" for="allowuninstall">{_ld($_module,'allowuninstall')}:</label>
    {cms_help 0=$_module key='help_allowuninstall' title=_ld($_module,'allowuninstall')}<br>
    <p class="warning">{_ld($_module,'allowuninstallwarn')}</p>
    <input type="hidden" name="{$actionid}allowuninstall" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}allowuninstall" id="allowuninstall" value="1"{if $allowuninstall} checked{/if}>
    </div>
  </div>
{/if}

{if isset($disable_caching)}
  <div class="pageoverflow">
    <label class="pagetext" for="disable_caching">{_ld($_module,'prompt_disable_caching')}:</label>
    {cms_help 0=$_module key='help_disable_caching' title=_ld($_module,'prompt_disable_caching')}
    <input type="hidden" name="{$actionid}disable_caching" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}disable_caching" id="disable_caching" value="1"{if $disable_caching} checked{/if}>
    </div>
  </div>
{/if}
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}submit" id="settings_submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
  </div>
</form>
