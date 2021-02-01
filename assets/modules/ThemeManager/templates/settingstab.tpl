{form_start action=updatesettings id=edit_settings}
  <div class="pageoverflow postgap">{$t=$mod->Lang('label_current_theme')}
    <label class="pagetext" for="dfltthm">{$t}:</label>
    {cms_help realm=$_module key='help_current_theme' title=$t}
    <p class="pageinput">
      <select id="dfltthm" name="{$actionid}current_theme">
      {html_options options=$themeoptions selected=$current_theme}
      </select>
    </p>
  </div>
{*  <div class="pageoverflow postgap">{$t=$mod->Lang('label_X')}
    <label class="pagetext" for="format">{$t}:</label>
    {cms_help realm=$_module key='help_X' title=$t}
    <p class="pageinput">
       <input type="text" id="format" name="{$actionid}date_format" value="{$date_format}" size="20" maxlength="24" />
    </p>
  </div>
*}
  <div class="pageoverflow">
    <p class="pageinput">
      <button type="submit" name="{$actionid}apply" class="adminsubmit icon check">{$mod->Lang('apply')}</button>
      <button type="submit" name="{$actionid}cancel" class="adminsubmit icon undo">{$mod->Lang('revert')}</button>
    </p>
  </div>
</form>
