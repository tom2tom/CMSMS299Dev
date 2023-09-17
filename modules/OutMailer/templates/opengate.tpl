<h4>{$pagetitle}</h4>
{if isset($message)}<p>{$message}</p>{/if}
{$startform}
  <input type="hidden" name="{$actionid}gate_id" value="{$gateid}">
  <div class="pageoverflow">
    {$t=$title_title}<label class="pagetext" for="title">{$t}:</label>
    {cms_help realm=$_module key='info_title' title=$t}
    <div class="pageinput">
      <input type="text" id="title" name="{$actionid}title" value="{$value_title}" size="50" maxlength="128">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_alias}<label class="pagetext" for="alias">{$t}:</label>
    {cms_help realm=$_module key='info_alias' title=$t}
    <div class="pageinput">
      <input type="text" id="alias" name="{$actionid}alias" value="{$value_alias}" size="30" maxlength="48">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_desc}<label class="pagetext" for="desc">{$t}:</label>
    {cms_help realm=$_module key='info_desc' title=$t}
{*  <div class="pageinput">
      <textarea id="desc" name="{$actionid}desc" class="pegeinput">{$value_desc}</textarea>
    </div> *}
    <div class="pageinput">{$textarea_desc}</div>
  </div>
  {include 'module_file_tpl:OutMailer;gatedata_mod.tpl'}
  <input type="hidden" name="{$actionid}enabled" value="0">
  <div class="pageoverflow">
    {$t=$title_enabled}<label class="pagetext" for="enabled">{$t}:</label>
    {cms_help realm=$_module key='info_enabled' title=$t}
    <div class="pageinput">
      <input type="checkbox" id="enabled" name="{$actionid}enabled" value="1"{if $value_enabled} checked{/if}>
    </div>
  </div>
  <input type="hidden" name="{$actionid}active" value="0">
  <div class="pageoverflow">
    {$t=$title_active}<label class="pagetext" for="active">{$t}:</label>
    {cms_help realm=$_module key='info_active' title=$t}
    <div class="pageinput">
      <input type="checkbox" id="active" name="{$actionid}active" value="1"{if $value_active} checked{/if}>
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon submit">{_ld($_module,'submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </div>
</form>
