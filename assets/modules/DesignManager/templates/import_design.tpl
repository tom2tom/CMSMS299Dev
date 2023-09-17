<h3>{_ld($_module,'import_design_step1')}</h3>
{form_start}
 <div class="pageinfo">{_ld($_module,'info_import_xml_step1')}</div>
 <div class="pageoverflow">
  {$lbltext=_ld($_module,'prompt_import_xml_file')}<label class="pagetext" for="import_xml_file">{$lbltext}:</label>
  {cms_help realm=$_module key='help_import_xml_file' title=$lbltext}
  <div class="pageinput">
    <input type="file" name="{$actionid}import_xml_file" id="import_xml_file" size="50" required>
  </div>
 </div>
 <div class="pageinput pregap">
  <button type="submit" name="{$actionid}next1" class="adminsubmit icon go">{_ld($_module,'next')}</button>
  <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel" formnovalidate>{_ld($_module,'cancel')}</button>
 </div>
</form>
