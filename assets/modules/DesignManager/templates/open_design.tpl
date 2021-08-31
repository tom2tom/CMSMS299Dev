<h3>{$title}</h3>
{tab_header name='general' label=$mod->Lang('general_settings') active=$tab}
{tab_header name='stylesheets' label=$mod->Lang('styles_settings') active=$tab}
{tab_header name='templates' label=$mod->Lang('template_settings') active=$tab}
{tab_start name='general'}
{$form_start}
{foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}" />{/foreach}
<div class="pageoverflow">
  <p class="pagetext">
    {$t=$mod->Lang('title_name')}<label for="designname">* {$t}:</label>
    {cms_help realm=$_module key2='help_design_name' title=$t}
  </p>
  <p class="pageinput">
    <input type="text" id="designname" name="{$actionid}name" value="{$name}" size="40" maxlength="64" placeholder="{$mod->Lang('enter_name')}" />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    {$t=$mod->Lang('title_description')}<label for="description">{$t}:</label>
    {cms_help realm=$_module key2='help_design_description' title=$t}
  </p>
  <p class="pageinput">
    <textarea id="description" name="{$actionid}description" rows="3" cols="40" style="width:40em;min-height:2em;">{$description}</textarea>
  </p>
</div>
{tab_start name='stylesheets'}
<p class="pageinfo">{$mod->Lang('info_edit_stylesheets')}</p>
<div class="pageoverflow">
{include file='module_file_tpl:DesignManager;stylemembers.tpl'}
</div>
{tab_start name='templates'}
<p class="pageinfo">{$mod->Lang('info_edit_templates')}</p>
<div class="pageoverflow">
{include file='module_file_tpl:DesignManager;templatemembers.tpl'}
</div>
{tab_end}
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
</div>
</form>
