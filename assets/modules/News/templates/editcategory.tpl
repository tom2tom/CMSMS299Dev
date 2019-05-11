{if isset($catid)}
 <h3 class="subtitle">{$mod->Lang('editcategory')}</h3>
{else}
 <h3 class="subtitle">{$mod->Lang('addcategory')}</h3>
{/if}
<div class="pageinfo">{$mod->Lang('info_categories')}</div>

{$startform}
<div class="pageoverflow">
  <p class="pagetext">
    <label for="{$actionid}name">*{$mod->Lang('name')}:</label>
    {cms_help realm=$_module key='help_category_name' title=$mod->Lang('name')}
  </p>
  <p class="pageinput">
    <input type="text" name="{$actionid}name" id="{$actionid}name" value="{$name|default:''}" required="required" />
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
     <label for="{$actionid}parent">{$mod->Lang('parent')}:</label>
     {cms_help realm=$_module key='help_category_parent' title=$mod->Lang('parent')}
  </p>
  <p class="pageinput">
    <select id="{$actionid}parent" name="{$actionid}parent">
      {html_options options=$categories selected=$parent}
    </select>
  </p>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" id="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{lang('cancel')}</button>
</div>
</form>
