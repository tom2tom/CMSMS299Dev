<h3>{$mod->Lang('import_design_step1')}</h3>
{form_start}
 <div class="pageinfo">{$mod->Lang('info_import_xml_step1')}</div>
 <div class="pageoverflow">
  <p class="pagetext">
    {$lbltext=$mod->Lang('prompt_import_xml_file')}<label for="import_xml_file">{$lbltext}:</label>
    {cms_help realm=$_module key2='help_import_xml_file' title=$lbltext}
  </p>
  <p class="pageinput">
    <input type="file" name="{$actionid}import_xml_file" id="import_xml_file" size="50" />
  </p>
 </div>
 <div class="pageinput pregap">
  <button type="submit" name="{$actionid}next1" class="adminsubmit icon go">{$mod->Lang('next')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
 </div>
</form>
