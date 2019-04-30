<div class="pageinfo postgap">{$mod->Lang('info_pagedflt')}</div>

{form_start action=admin_pagedefaults_tab pagedefaults=1}
<div class="pageinput postgap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="contenttype">{$mod->Lang('prompt_pagedflt_contenttype')}</label>:
    {cms_help realm=$_module key2='help_pagedflt_contenttype' title=$mod->Lang('prompt_pagedflt_contenttype')}
  </p>
  <p class="pageinput">
    <select id="contenttype" name="{$actionid}contenttype">
      {html_options options=$all_contenttypes selected=$page_prefs.contenttype}
    </select>
  </p>
</div>
<div class="pageoverflow">
{*
  <p class="pagetext">
    <label for="design_id">{$mod->Lang('prompt_pagedflt_design_id')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_design_id' title=$mod->Lang('prompt_pagedflt_design_id')}
  </p>
  <p class="pageinput">
    <select id="design_id" name="{$actionid}design_id">
      {html_options options=$design_list selected=$page_prefs.design_id}
    </select>
  </p>
*}
  TODO CSS SELECTOR
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="template_id">{$mod->Lang('prompt_pagedflt_template_id')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_template_id' title=$mod->Lang('prompt_pagedflt_template_id')}
  </p>
  <p class="pageinput">
    <select id="template_id" name="{$actionid}template_id">
      {html_options options=$template_list selected=$page_prefs.template_id}
    </select>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="metadata">{$mod->Lang('prompt_pagedflt_metadata')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_metadata' title=$mod->Lang('prompt_pagedflt_metadata')}
  </p>
  <p class="pageinput">
    <textarea id="metadata" name="{$actionid}metadata" rows="5" cols="80">{$page_prefs.metadata}</textarea>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="content">{$mod->Lang('prompt_pagedflt_content')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_content' title=$mod->Lang('prompt_pagedflt_content')}
  </p>
  <p class="pageinput">
    <textarea id="content" name="{$actionid}content" rows="5" cols="80">{$page_prefs.content}</textarea>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="cachable">{$mod->Lang('prompt_pagedflt_cachable')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_cachable' title=$mod->Lang('prompt_pagedflt_cachable')}
  </p>
  <input type="hidden" name="{$actionid}cachable" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}cachable" id="cachable" value="1"{if $page_prefs.cachable} checked="checked"{/if} />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="active">{$mod->Lang('prompt_pagedflt_active')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_active' title=$mod->Lang('prompt_pagedflt_active')}
  </p>
  <input type="hidden" name="{$actionid}active" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}active" id="active" value="1"{if $page_prefs.active} checked="checked"{/if} />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="showinmenu">{$mod->Lang('prompt_pagedflt_showinmenu')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_showinmenu' title=$mod->Lang('prompt_pagedflt_showinmenu')}
  </p>
  <input type="hidden" name="{$actionid}showinmenu" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}showinmenu" id="showinmenu" value="1"{if $page_prefs.showinmenu} checked="checked"{/if} />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="searchable">{$mod->Lang('prompt_pagedflt_searchable')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_searchable' title=$mod->Lang('prompt_pagedflt_searchable')}
  </p>
  <input type="hidden" name="{$actionid}searchable" value="0" />
  <p class="pageinput">
    <input type="checkbox" name="{$actionid}searchable" id="searchable" value="1"{if $page_prefs.searchable} checked="checked"{/if} />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="addteditors">{$mod->Lang('prompt_pagedflt_addteditors')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_addteditors' title=$mod->Lang('prompt_pagedflt_addteditors')}
  </p>
  <p class="pageinput">
    <select id="addteditors" name="{$actionid}addteditors[]" multiple="multiple" size="6">
      {html_options options=$addteditor_list selected=$page_prefs.addteditors}
    </select>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="extra1">{$mod->Lang('prompt_pagedflt_extra1')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_extra1' title=$mod->Lang('prompt_pagedflt_extra1')}
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}extra1" id="extra1" value="{$page_prefs.extra1}" size="80" maxlength="255" />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="extra2">{$mod->Lang('prompt_pagedflt_extra2')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_extra2' title=$mod->Lang('prompt_pagedflt_extra2')}
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}extra2" id="extra2" value="{$page_prefs.extra2}" size="80" maxlength="255" />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    <label for="extra3">{$mod->Lang('prompt_pagedflt_extra3')}:</label>
    {cms_help realm=$_module key2='help_pagedflt_extra3' title=$mod->Lang('prompt_pagedflt_extra3')}
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}extra3" id="extra3" value="{$page_prefs.extra3}" size="80" maxlength="255" />
  </p>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
</div>
</form>
