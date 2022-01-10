{form_start action=updateoptions id=edit_settings}
  <fieldset>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="dfltcat">{$label_default_category}:</label>
    {cms_help 0=$_module key='help_dflt_category' title=$label_default_category}
    <div class="pageinput">
    <select id="dfltcat" name="{$actionid}default_category">
      {html_options options=$categorylist selected=$default_category}    </select>
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="clearcat">{$label_clear_category}:</label>
    {cms_help 0=$_module key='help_clear_category' title=$label_clear_category}
    <input type="hidden" name="{$actionid}clear_category" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}clear_category" id="clearcat" value="1"{if $clear_category} checked="checked"{/if} />
    </div>
  </div>
  </fieldset>
  <fieldset>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="dformat">{$label_date_format}:</label>
    {cms_help 0=$_module key='help_date_format' title=$label_date_format}
    <div class="pageinput">
      <input type="text" id="dformat" name="{$actionid}date_format" value="{$date_format}" size="30" maxlength="36" />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="timeblock">{$label_timeblock}:</label>
    {cms_help 0=$_module key='help_timeblock' title=$label_timeblock}
    <div class="pageinput">
    <select id="timeblock" name="{$actionid}timeblock">
      {html_options options=$blockslist selected=$timeblock}    </select>
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="pagelines">{$label_article_pagelimit}:</label>
    {cms_help 0=$_module key='help_pagelimit' title=$label_article_pagelimit}
    <div class="pageinput">
      <input type="text" id="pagelines" name="{$actionid}article_pagelimit" value="{$article_pagelimit}" size="4" maxlength="4" />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="doview">{$label_expired_viewable}:</label>
    {cms_help 0=$_module key='help_expired_viewable' title=$label_expired_viewable}
    <input type="hidden" name="{$actionid}expired_viewable" value="0" />
    <div class="pageinput">
      <input type="checkbox" id="doview" name="{$actionid}expired_viewable" value="1"{if $expired_viewable} checked="checked"{/if} />
    </div>
  </div>
  </fieldset>
  <fieldset>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="expint">{$label_expiry_interval}:</label>
    {cms_help 0=$_module key='help_expiry_interval' title=$label_expiry_interval}
    <div class="pageinput">
      <input type="text" id="expint" name="{$actionid}expiry_interval" value="{$expiry_interval}" size="4" maxlength="4" />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="cms_hierdropdown1_0">{$label_detail_returnid}:</label>
    {cms_help 0=$_module key='help_detail_returnid' title=$label_detail_returnid}
    <p class="pageinput">
      {$detail_returnid}
    </p>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="summary_wysiwyg">{$label_summary_wysiwyg}:</label>
    {cms_help 0=$_module key='help_summary_wysiwyg' title=$label_summary_wysiwyg}
    <input type="hidden" name="{$actionid}allow_summary_wysiwyg" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}allow_summary_wysiwyg" id="summary_wysiwyg" value="1"{if $allow_summary_wysiwyg} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="dosearch">{$label_expired_searchable}:</label>
    {cms_help 0=$_module key='help_expired_searchable' title=$label_expired_searchable}
    <input type="hidden" name="{$actionid}expired_searchable" value="0" />
    <div class="pageinput">
      <input type="checkbox" id="dosearch" name="{$actionid}expired_searchable" value="1"{if $expired_searchable} checked="checked"{/if} />
    </div>
  </div>
  </fieldset>
  <fieldset>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="alert_drafts">{$label_alert_drafts}:</label>
    {cms_help 0=$_module key='help_alert_drafts' title=$label_alert_drafts}
    <input type="hidden" name="{$actionid}alert_drafts" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}alert_drafts" id="alert_drafts" value="1"{if $alert_drafts} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="mailsubject">{$label_email_subject}:</label>
    {cms_help 0=$_module key='help_email_subject' title=$label_email_subject}
    <div class="pageinput">
      <input type="text" id="mailsubject" name="{$actionid}email_subject" value="{$email_subject}" size="40" maxlength="80" />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="mailto">{$label_email_to}:</label>
    {cms_help 0=$_module key='help_email_to' title=$label_email_to}
    <div class="pageinput">
      <input type="text" id="mailto" name="{$actionid}email_to" value="{$email_to}" size="50" maxlength="160" />
    </div>
  </div>
  <div class="pageoverflow postgap">
    <label class="pagetext" for="mailbody">{$label_email_template}:</label>
    {cms_help 0=$_module key='help_email_template' title=$label_email_template}
    <div class="pageinput">
    <select id="mailbody" name="{$actionid}email_template">
      {html_options options=$mailbodylist selected=$email_template}      </select>
    </div>
  </div>
  </fieldset>
  <div class="pregap pageinput">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon check">{_la('apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon undo">{_la('revert')}</button>
  </div>
</form>
