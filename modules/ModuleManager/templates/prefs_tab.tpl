{if isset($message)}<p>{$message}</p>{/if}

{form_start action='setprefs'}
<input type="hidden" id="inp_reset" name="{$actionid}reseturl" value="" />
{if isset($module_repository)}
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="mr_url">{$mod->Lang('prompt_repository_url')}:</label>
    </p>
    <p class="pageinput">
      <input type="text" name="{$actionid}url" id="mr_url" size="50" maxlength="255" value="{$module_repository}" />
      <button type="submit" name="{$actionid}reset" id="reseturl" class="adminsubmit icon undo">{$mod->Lang('reset')}</button>
    </p>
  </div>
{/if}

  <div class="pageoverflow">
    <p class="pagetext">
      <label>{$mod->Lang('prompt_dl_chunksize')}:</label>
      {cms_help realm=$_module key2='help_dl_chunksize' title=$mod->Lang('prompt_dl_chunksize')}
    </p>
    <p class="pageinput">
      <input type="text" name="{$actionid}dl_chunksize" value="{$dl_chunksize}" size="3" maxlength="3" />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      <label for="latestdepends">{$mod->Lang('latestdepends')}:</label>
      {cms_help realm=$_module key2='help_latestdepends' title=$mod->Lang('latestdepends')}
    </p>
    <input type="hidden" name="{$actionid}latestdepends" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}latestdepends" id="latestdepends" value="1"{if $latestdepends} checked="checked"{/if} />
    </p>
  </div>

{if !empty($develop_mode)}
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="allowuninstall">{$mod->Lang('allowuninstall')}:</label>
      {cms_help realm=$_module key2='help_allowuninstall' title=$mod->Lang('allowuninstall')}
    </p>
    <input type="hidden" name="{$actionid}allowuninstall" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}allowuninstall" id="allowuninstall" value="1"{if $allowuninstall} checked="checked"{/if} />
    </p>
  </div>
{/if}

{if isset($disable_caching)}
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="disable_caching">{$mod->Lang('prompt_disable_caching')}:</label>
      {cms_help realm=$_module key2='help_disable_caching' title=$mod->Lang('prompt_disable_caching')}
    </p>
    <input type="hidden" name="{$actionid}disable_caching" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}disable_caching" id="disable_caching" value="1"{if $disable_caching} checked="checked"{/if} />
    </p>
  </div>
{/if}
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}submit" id="settings_submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  </div>
</form>
