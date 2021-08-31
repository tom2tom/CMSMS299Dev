{form_start action='apply_settings' tab='listsettings'}
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_list_namecolumn')}<label class="pagetext" for="namecol">{$t}:</label>
  {cms_help realm=$_module key2='help_listsettings_namecolumn' title=$t}<br />
  <select class="pageinput" id="namecol" name="{$actionid}list_namecolumn">
   {html_options options=$namecolumnopts selected=$list_namecolumn}
  </select>
</div>
<div class="pageoverflow postgap">
  {$t=$mod->Lang('prompt_list_visiblecolumns')}<label class="pagetext" for="visiblecols">{$t}:</label>
  {cms_help realm=$_module key2='help_listsettings_visiblecolumns' title=$t}<br />
  <select class="pageinput" id="visiblecols" name="{$actionid}list_visiblecolumns[]" multiple="multiple" size="5">
   {html_options options=$visible_column_opts selected=$list_visiblecolumns}
  </select>
</div>
<div class="pageinput">
  <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
</div>
</form>

