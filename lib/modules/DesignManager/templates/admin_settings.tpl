{form_start}
<div class="topsubmits">
  <p class="pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  </p>
</div>
<fieldset>
  <legend>{$mod->Lang('prompt_locksettings')}:</legend>
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="locktimeout">{$mod->Lang('lock_timeout')}:</label>
    {cms_help realm=$_module key2='help_locktimeout' title=$mod->Lang('lock_timeout')}
      </p>
    <p class="pageinput">
      <input id="locktimeout" type="text" name="{$actionid}lock_timeout" value="{$lock_timeout}" size="3" maxlength="3"/>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      <label for="lockrefresh">{$mod->Lang('lock_refresh')}:</label>
    {cms_help realm=$_module key2='help_lockrefresh' title=$mod->Lang('lock_refresh')}
      </p>
    <p class="pageinput">
      <input id="lockrefresh" type="text" name="{$actionid}lock_refresh" value="{$lock_refresh}" size="4" maxlength="4"/>
    </p>
  </div>
</fieldset>
</form>
