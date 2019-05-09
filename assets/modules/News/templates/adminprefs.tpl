{$formstart_prefs}
  <div class="pageoverflow">
    <label class="pagetext" for="dfltcat">{$title_default_category}:</label>
    {cms_help realm=$_module key='help_opt_dflt_category' title=$title_default_category}
    <p class="pageinput">
      <select id="dfltcat" name="{$actionid}default_category">
        {html_options options=$categorylist selected=$default_category}
      </select>
    </p>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="expint">{$title_expiry_interval}:</label>
    {cms_help realm=$_module key='help_opt_expiry_interval' title=$title_expiry_interval}
    <p class="pageinput">
       <input type="text" id="expint" name="{$actionid}expiry_interval" value="{$expiry_interval}" size="4" maxlength="4" />
    </p>
  </div>
  <div class="pageoverflow">
    <label class="pagetext" for="retid">{$title_detail_returnid}:</label>
    {cms_help realm=$_module key='info_detail_returnid' title=$title_detail_returnid}
    <p class="pageinput">
       <input type="text" id="retid" name="{$actionid}detail_returnid" value="{*$detail_returnid*}" size="30" maxlength="40" />
    </p>
  </div>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}alert_drafts" value="0" />
    <label class="pagetext" for="alert_drafts">{$mod->Lang('prompt_alert_drafts')}:</label>
    {cms_help realm=$_module key='help_opt_alert_drafts' title=$mod->Lang('prompt_alert_drafts')}
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}alert_drafts" id="alert_drafts" value="1"{if $alert_drafts} checked="checked"{/if} />
    </p>
  </div>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}expired_viewable" value="0" />
    <label class="pagetext" for="doview">{$title_expired_viewable}:</label>
    {cms_help realm=$_module key='info_expired_viewable' title=$title_expired_viewable}
    <p class="pageinput">
      <input type="checkbox" id="doview" name="{$actionid}expired_viewable" value="1"{if $expired_viewable} checked="checked"{/if} />
    </p>
  </div>
  <div class="pageoverflow">
    <input type="hidden" name="{$actionid}expired_searchable" value="0" />
    <label class="pagetext" for="dosearch">{$title_expired_searchable}:</label>
    {cms_help realm=$_module key='info_expired_searchable' title=$title_expired_searchable}
    <p class="pageinput">
      <input type="checkbox" id="dosearch" name="{$actionid}expired_searchable" value="1"{if $expired_searchable} checked="checked"{/if} />
    </p>
  </div>
  <div class="pageoverflow pregap">
    <p class="pageinput">
      <button type="submit" name="{$actionid}optionssubmitbutton" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
    </p>
  </div>
</form>
