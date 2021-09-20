{form_start action=updateoptions id=edit_settings}
  <div class="pageoverflow postgap">
    <label class="pagetext" class="pagetext" for="dfltcat">{$label_default_category}:</label>
    {cms_help 0=$_module key='help_dflt_category' title=$label_default_category}
    <div class="pageinput">
    <select id="dfltcat" name="{$actionid}default_category">
      {html_options options=$categorylist selected=$default_category}    </select>
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" class="pagetext" for="expint">{$label_expiry_interval}:</label>
    {cms_help 0=$_module key='help_expiry_interval' title=$label_expiry_interval}
    <div class="pageinput">
      <input type="text" id="expint" name="{$actionid}expiry_interval" value="{$expiry_interval}" size="4" maxlength="4" />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" class="pagetext" for="cms_hierdropdown1_0">{$label_detail_returnid}:</label>
    {cms_help 0=$_module key='help_detail_returnid' title=$label_detail_returnid}
    <p class="pageinput">
      {$detail_returnid}
    </p>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" class="pagetext" for="alert_drafts">{$label_alert_drafts}:</label>
    {cms_help 0=$_module key='help_alert_drafts' title=$label_alert_drafts}
    <input type="hidden" name="{$actionid}alert_drafts" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}alert_drafts" id="alert_drafts" value="1"{if $alert_drafts} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" class="pagetext" for="doview">{$label_expired_viewable}:</label>
    {cms_help 0=$_module key='help_expired_viewable' title=$label_expired_viewable}
    <input type="hidden" name="{$actionid}expired_viewable" value="0" />
    <div class="pageinput">
      <input type="checkbox" id="doview" name="{$actionid}expired_viewable" value="1"{if $expired_viewable} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" class="pagetext" for="dosearch">{$label_expired_searchable}:</label>
    {cms_help 0=$_module key='help_expired_searchable' title=$label_expired_searchable}
    <input type="hidden" name="{$actionid}expired_searchable" value="0" />
    <div class="pageinput">
      <input type="checkbox" id="dosearch" name="{$actionid}expired_searchable" value="1"{if $expired_searchable} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" class="pagetext" for="format">{$label_date_format}:</label>
    {cms_help 0=$_module key='help_date_format' title=$label_date_format}
    <div class="pageinput">
      <input type="text" id="format" name="{$actionid}date_format" value="{$date_format}" size="20" maxlength="24" />
    </div>
  </div>
  <div class="pregap pageinput">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon check">{_ld('admin','apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon undo">{_ld('admin','revert')}</button>
  </div>
</form>
