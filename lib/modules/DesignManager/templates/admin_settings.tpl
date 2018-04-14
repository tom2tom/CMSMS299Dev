{form_start}
<fieldset>
  <legend>{$mod->Lang('prompt_locksettings')}:</legend>
  <div class="pageoverflow">
    <p class="pagetext">
      {$lbltext=$mod->Lang('lock_timeout')}<label for="locktimeout">{$lbltext}:</label>
      {cms_help realm=$_module key2='help_locktimeout' title=$lbltext}
    </p>
    <p class="pageinput">
      <input id="locktimeout" type="text" name="{$actionid}lock_timeout" value="{$lock_timeout}" size="3" maxlength="3"/>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$lbltext=$mod->Lang('lock_refresh')}<label for="lockrefresh">{$lbltext}:</label>
      {cms_help realm=$_module key2='help_lockrefresh' title=$lbltext}
    </p>
    <p class="pageinput">
      <input id="lockrefresh" type="text" name="{$actionid}lock_refresh" value="{$lock_refresh}" size="4" maxlength="4"/>
    </p>
  </div>
</fieldset>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
</div>
</form>
