<h3 class="subtitle">{if isset($catid)}{_ld($_module,'prompt_editcategory')}{else}{_ld($_module,'prompt_addcategory')}{/if}</h3>
<p class="pageinfo">{_ld($_module,'info_categories')}</p>

{form_start action=$formaction id='edit_category' extraparms=$formparms}
  <div class="pageoverflow">
    <label class="pagetext" for="catname">* {_ld($_module,'name')}:</label>
    {cms_help realm=$_module key='help_category_name' title=_ld($_module,'name')}
    <div class="pageinput">
      <input type="text" name="{$actionid}name" id="catname" value="{$name|default:''}" size="32" maxlength="48" required>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_ld($_module,'prettyurl')}<label class="pagetext" for="caturl">{$t}:</label>
    {cms_help realm=$_module key='help_category_url' title=$t}
    <div class="pageinput">
      <input type="text" name="{$actionid}category_url" id="caturl" value="{$category_url}" size="32" maxlength="64"><br>
      <input type="checkbox" id="genurl" name="{$actionid}generate_url" value="1">
      <label for="genurl">{_ld($_module,'generateurl')}</label>
    </div>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="catparent">{_ld($_module,'parent')}:</label>
    {cms_help realm=$_module key='help_category_parent' title=_ld($_module,'parent')}
    <div class="pageinput">
      <select name="{$actionid}parent" id="catparent">
        {html_options options=$categories selected=$parent}      </select>
    </div>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="catimage">{_ld($_module,'cat_image')}:</label>
    <div class="pageinput">
      <img id="catimage" class="yesimage" src="{$image_url}" alt="{$image_url}">
      <br class="yesimage">
      {$filepicker}
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_la('submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{_la('cancel')}</button>
  </div>
</form>
