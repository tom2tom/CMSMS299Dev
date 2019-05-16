<h3 class="subtitle">{if isset($catid)}{$mod->Lang('prompt_editcategory')}{else}{$mod->Lang('prompt_addcategory')}{/if}</h3>
<p class="pageinfo">{$mod->Lang('info_categories')}</p>

{form_start action=$formaction id='edit_category' extraparms=$formparms}
  <div class="pageoverflow">
    <label class="pagetext" for="catname">* {$mod->Lang('name')}:</label>
    {cms_help realm=$_module key='help_category_name' title=$mod->Lang('name')}
    <p class="pageinput">
    <input type="text" name="{$actionid}name" id="catname" value="{$name|default:''}" size="32" maxlength="48" required="required" />
    </p>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="catparent">{$mod->Lang('parent')}:</label>
    {cms_help realm=$_module key='help_category_parent' title=$mod->Lang('parent')}
    <p class="pageinput">
    <select name="{$actionid}parent" id="catparent">
    {html_options options=$categories selected=$parent}
    </select>
    </p>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
  </div>
</form>
