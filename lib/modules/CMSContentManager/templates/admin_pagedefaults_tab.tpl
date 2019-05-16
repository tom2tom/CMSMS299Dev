<div class="pageinfo postgap">{$mod->Lang('info_pagedflt')}</div>
{form_start action=admin_pagedefaults_tab pagedefaults=1}
<div class="pageinput postgap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_contenttype')}<label class="pagetext" for="contenttype">{$t}</label>:
  {cms_help realm=$_module key2='help_pagedflt_contenttype' title=$t}<br />
  <select class="pageinput" id="contenttype" name="{$actionid}contenttype">
  {html_options options=$all_contenttypes selected=$page_prefs.contenttype}
  </select>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_styles')}<label class="pagetext" for="styles">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_styles' title=$t}<br />
  SELECTOR NEEDED: TODO
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_template_id')}<label class="pagetext" for="template_id">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_template_id' title=$t}<br />
  <select class="pageinput" id="template_id" name="{$actionid}template_id">
  {html_options options=$template_list selected=$page_prefs.template_id}
  </select>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_content')}<label class="pagetext" for="edit_area">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_content' title=$t}<br />
  <textarea class="pageinput" id="edit_area" name="{$actionid}content" rows="5" cols="40" data-cms-lang="smarty" style="width:40em;min-height:2em;">{$page_prefs.content}</textarea>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_cachable')}<label class="pagetext" for="cachable">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_cachable' title=$t}
  <div class="pageinput">
    <input type="hidden" name="{$actionid}cachable" value="0" />
    <input type="checkbox" name="{$actionid}cachable" id="cachable" value="1"{if $page_prefs.cachable} checked="checked"{/if} />
  </div>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_active')}<label class="pagetext" for="active">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_active' title=$t}
  <div class="pageinput">
    <input type="hidden" name="{$actionid}active" value="0" />
    <input type="checkbox" name="{$actionid}active" id="active" value="1"{if $page_prefs.active} checked="checked"{/if} />
  </div>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_showinmenu')}<label class="pagetext" for="showinmenu">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_showinmenu' title=$t}
  <div class="pageinput">
    <input type="hidden" name="{$actionid}showinmenu" value="0" />
    <input type="checkbox" name="{$actionid}showinmenu" id="showinmenu" value="1"{if $page_prefs.showinmenu} checked="checked"{/if} />
  </div>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_searchable')}<label class="pagetext" for="searchable">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_searchable' title=$t}
  <div class="pageinput">
    <input type="hidden" name="{$actionid}searchable" value="0" />
    <input type="checkbox" name="{$actionid}searchable" id="searchable" value="1"{if $page_prefs.searchable} checked="checked"{/if} />
  </div>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_addteditors')}<label class="pagetext" for="addteditors">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_addteditors' title=$t}<br />
  <select class="pageinput" id="addteditors" name="{$actionid}addteditors[]" multiple="multiple" size="6">
  {html_options options=$addteditor_list selected=$page_prefs.addteditors}
  </select>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_metadata')}<label class="pagetext" for="metadata">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_metadata' title=$t}<br />
  <textarea  class="pageinput" id="metadata" name="{$actionid}metadata" rows="5" cols="40"style="width:40em;min-height:2em;">{$page_prefs.metadata}</textarea>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_extra1')}<label class="pagetext" for="extra1">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_extra1' title=$t}
  <p class="pageinput">
    <input type="text" name="{$actionid}extra1" id="extra1" value="{$page_prefs.extra1}" size="50" maxlength="255" />
  </p>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_extra2')}<label class="pagetext" for="extra2">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_extra2' title=$t}
  <p class="pageinput">
    <input type="text" name="{$actionid}extra2" id="extra2" value="{$page_prefs.extra2}" size="50" maxlength="255" />
  </p>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_pagedflt_extra3')}<label class="pagetext" for="extra3">{$t}:</label>
  {cms_help realm=$_module key2='help_pagedflt_extra3' title=$t}
  <p class="pageinput">
    <input type="text" name="{$actionid}extra3" id="extra3" value="{$page_prefs.extra3}" size="50" maxlength="255" />
  </p>
</div>
<div class="pageinput">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
