{form_start action='apply_settings' tab='general'}
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_locktimeout')}<label class="pagetext" for="locktimeout">{$t}:</label>
  {cms_help realm=$_module key2='help_general_locktimeout' title=$t}
  <p class="pageinput">
    <input type="text" name="{$actionid}locktimeout" value="{$locktimeout}" size="3" maxlength="3" />
  </p>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_lockrefresh')}<label class="pagetext" for="lockrefresh">{$t}:</label>
  {cms_help realm=$_module key2='help_general_lockrefresh' title=$t}
  <p class="pageinput">
    <input type="text" name="{$actionid}lockrefresh" value="{$lockrefresh}" size="4" maxlength="4" />
  </p>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_template_list_mode')}<label class="pagetext" for="lockrefresh">{$t}:</label>
  {cms_help realm=$_module key2='help_general_templatelistmode' title=$t}<br />
  <select class="pageinput" name="{$actionid}template_list_mode">
    {html_options options=$template_list_opts selected=$template_list_mode}
  </select>
</div>
<div class="pageinput">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
