<h3 class="subtitle">{if isset($catid)}{_ld($_module,'prompt_editcategory')}{else}{_ld($_module,'prompt_addcategory')}{/if}</h3>
<p class="pageinfo">{_ld($_module,'info_categories')}</p>

{form_start action=$formaction id='edit_category' extraparms=$formparms}
  <div class="pageoverflow">
    <label class="pagetext" for="catname">* {_ld($_module,'name')}:</label>
    {cms_help 0=$_module key='help_category_name' title=_ld($_module,'name')}
    <div class="pageinput">
      <input type="text" name="{$actionid}name" id="catname" value="{$name|default:''}" size="32" maxlength="48" required="required" />
    </div>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="catparent">{_ld($_module,'parent')}:</label>
    {cms_help 0=$_module key='help_category_parent' title=_ld($_module,'parent')}
    <div class="pageinput">
      <select name="{$actionid}parent" id="catparent">
        {html_options options=$categories selected=$parent}      </select>
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld('admin','submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{_ld('admin','cancel')}</button>
  </div>
</form>
