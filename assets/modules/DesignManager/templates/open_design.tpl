<h3>{$title}</h3>
{tab_header name='general' label=_ld($_module,'general_settings') active=$tab}
{tab_header name='stylesheets' label=_ld($_module,'styles_settings') active=$tab}
{tab_header name='templates' label=_ld($_module,'template_settings') active=$tab}
{tab_start name='general'}
{$form_start}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}">
{/foreach}
<div class="pageoverflow">
  {$t=_ld($_module,'title_name')}<label class="pagetext" for="designname">* {$t}:</label>
  {cms_help 0=$_module key='help_design_name' title=$t}
  <div class="pageinput">
    <input type="text" id="designname" name="{$actionid}name" value="{$name}" size="40" maxlength="64" placeholder="{_ld($_module,'enter_name')}">
  </div>
</div>
<div class="pageoverflow">
  {$t=_ld($_module,'title_description')}<label class="pagetext" for="description">{$t}:</label>
  {cms_help 0=$_module key='help_design_description' title=$t}
  <div class="pageinput">
    <textarea id="description" name="{$actionid}description" rows="3" cols="40" style="width:40em;min-height:2em;">{$description}</textarea>
  </div>
</div>
{tab_start name='stylesheets'}
<p class="pageinfo">{_ld($_module,'info_edit_stylesheets')}</p>
<div class="pageoverflow">
{include file='module_file_tpl:DesignManager;stylemembers.tpl'}
</div>
{tab_start name='templates'}
<p class="pageinfo">{_ld($_module,'info_edit_templates')}</p>
<div class="pageoverflow">
{include file='module_file_tpl:DesignManager;templatemembers.tpl'}
</div>
{tab_end}
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
  <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_ld($_module,'apply')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
</div>
</form>
