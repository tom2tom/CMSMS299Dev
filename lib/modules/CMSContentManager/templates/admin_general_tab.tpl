{form_start action='admin_general_tab'}
<div class="pageoverflow">
  <p class="pagetext">
      <label for="locktimeout">{$mod->Lang('prompt_locktimeout')}:</label>
    {cms_help realm=$_module key2='help_general_locktimeout' title=$mod->Lang('prompt_locktimeout')}
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}locktimeout" value="{$locktimeout}" size="3" maxlength="3"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
      <label for="lockrefresh">{$mod->Lang('prompt_lockrefresh')}:</label>
    {cms_help realm=$_module key2='help_general_lockrefresh' title=$mod->Lang('prompt_lockrefresh')}
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}lockrefresh" value="{$lockrefresh}" size="4" maxlength="4"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
      <label for="lockrefresh">{$mod->Lang('prompt_template_list_mode')}:</label>
    {cms_help realm=$_module key2='help_general_templatelistmode' title=$mod->Lang('prompt_template_list_mode')}
  </p>
  <p class="pageinput">
    <select name="{$actionid}template_list_mode">
      {html_options options=$template_list_opts selected=$template_list_mode}
    </select>
  </p>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
