{if $category->get_id() == '' }
<h3>{$mod->Lang('create_category')}</h3>
{else}
<h3>{$mod->Lang('edit_category')}: {$category->get_name()} ({$category->get_id()})</h3>
{/if}

{form_start}
{if $category->get_id() != ''}
  <input type="hidden" name="{$actionid}cat" value="{$category->get_id()}" />
{/if}
<div class="pageoverflow">
  <p class="pagetext">
    {$lbltext=$mod->Lang('prompt_name')}<label for="cat_name">* {$lbltext}:</label>
    {cms_help realm=$_module key2='help_category_name' title=$lbltext}
  </p>
  <p class="pageinput">
    <input type="text" id="cat_name" name="{$actionid}name" value="{$category->get_name()}" size="50" maxlength="50" placeholder="{$mod->Lang('create_category')}"/>
  </p>
</div>
<div class="pageoverflow">
  <p class="pagetext">
    {$lbltext=$mod->Lang('prompt_description')}<label for="cat_description">{$lbltext}:</label>
    {cms_help realm=$_module key2='help_category_desc' title=$lbltext}
  </p>
  <p class="pageinput">
    <textarea id="cat_description" name="{$actionid}description" rows="5" cols="80">{$category->get_description()}</textarea>
  </p>
</div>
<div class="pageinput pregap">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
</div>
</form>
