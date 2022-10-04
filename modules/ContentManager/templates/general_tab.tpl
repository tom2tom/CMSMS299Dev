{form_start action='apply_settings' tab='general'}
<div class="pageoverflow postgap">
  {$t=_ld($_module,'prompt_locktimeout')}<label class="pagetext" for="locktimeout">{$t}:</label>
  {cms_help 0=$_module key='help_general_locktimeout' title=$t}
  <div class="pageinput">
    <input type="text" name="{$actionid}locktimeout" value="{$locktimeout}" size="3" maxlength="3">
  </div>
</div>
<div class="pageoverflow postgap">
  {$t=_ld($_module,'prompt_lockrefresh')}<label class="pagetext" for="lockrefresh">{$t}:</label>
  {cms_help 0=$_module key='help_general_lockrefresh' title=$t}
  <div class="pageinput">
    <input type="text" name="{$actionid}lockrefresh" value="{$lockrefresh}" size="4" maxlength="4">
  </div>
</div>
<div class="pageoverflow postgap">
  {$t=_ld($_module,'prompt_template_list_mode')}<label class="pagetext" for="lockrefresh">{$t}:</label>
  {cms_help 0=$_module key='help_general_templatelistmode' title=$t}
  <div class="pageinput">
    <select name="{$actionid}template_list_mode">
      {html_options options=$template_list_opts selected=$template_list_mode}    </select>
  </div>
</div>
<div class="pageinput">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
</div>
</form>
