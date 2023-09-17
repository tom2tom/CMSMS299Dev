<h3>{_ld($_module,'edit_profile')}: <em>{$data.label}</em></h3>

{form_start}
  <input type="hidden" name="{$actionid}profile" value="{$profile}">
  <input type="hidden" name="{$actionid}origname" value="{$data.name}">

  {if $data.system}<div class="pageinfo">{$tmp='profiledesc_'|cat:$data.name}{_ld($_module,$tmp)}</div>{/if}
  <br>

  {if !$data.system}
    <div class="pageoverflow">
      {$t=_ld($_module,'profile_name')}<label class="pagetext" for="profile_name">*{$t}:</label>
      {cms_help realm=$_module key='help_profilename' title=$t}
      <div class="pageinput">
        <input type="text" size="40" id="profile_name" name="{$actionid}profile_name" value="{$data.name}">
      </div>
    </div>

    <div class="pageoverflow">
      {$t=_ld($_module,'profile_label')}<label class="pagetext" for="profile_label">*{$t}:</label>
      {cms_help realm=$_module key='help_profilelabel' title=$t}
      <div class="pageinput">
        <input type="text" size="80" id="profile_label" name="{$actionid}profile_label" value="{$data.label}">
      </div>
    </div>
  {/if}

  <div class="pageoverflow">
    {$t=_ld($_module,'profile_menubar')}<label class="pagetext" for="profile_menubar">{$t}:</label>
    {cms_help realm=$_module key='help_profilemenubar' title=$t}
    <input type="hidden" name="{$actionid}profile_menubar" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}profile_menubar" id="profile_menubar" value="1"{if $data.menubar} checked{/if}>
    </div>
  </div>

  <div class="pageoverflow">
    {$t=_ld($_module,'profile_allowimages')}<label class="pagetext" for="profile_allowimages">{$t}:</label>
    {cms_help realm=$_module key='help_profileallowimages' title=$t}
    <input type="hidden" name="{$actionid}profile_allowimages" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowimages" id="profile_allowimages" value="1"{if $data.allowimages} checked{/if}>
    </div>
  </div>

  <div class="pageoverflow">
    {$t=_ld($_module,'profile_allowtables')}<label class="pagetext" for="profile_allowtables">{$t}:</label>
    {cms_help realm=$_module key='help_profileallowtables' title=$t}
    <input type="hidden" name="{$actionid}profile_allowtables" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowtables" id="profile_allowtables" value="1"{if $data.allowtables} checked{/if}>
    </div>
  </div>
{*
  <div class="pageoverflow">
    {$t=_ld($_module,'profile_showstatusbar')}<label class="pagetext" for="profile_showstatusbar">{$t}:</label>
    {cms_help realm=$_module key='help_profilestatusbar' title=$t}
    <input type="hidden" name="{$actionid}profile_showstatusbar" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}profile_showstatusbar" id="profile_showstatusbar" value="1"{if $data.showstatusbar} checked{/if}>
    </div>
  </div>
*}
  <div class="pageoverflow">
    {$t=_ld($_module,'profile_allowresize')}<label class="pagetext" for="profile_allowresize">{$t}:</label>
    {cms_help realm=$_module key='help_profileresize' title=$t}
    <input type="hidden" name="{$actionid}profile_allowresize" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowresize" id="profile_allowresize" value="1"{if $data.allowresize} checked{/if}>
    </div>
  </div>

  <div class="pageoverflow">
    {$t=_ld($_module,'profile_dfltstylesheet')}<label class="pagetext" for="profile_dfltstylesheet">{$t}:</label>
    {cms_help realm=$_module key='help_dfltstylesheet' title=$t}
    <div class="pageinput">
      <select id="profile_dfltstylesheet" name="{$actionid}profile_dfltstylesheet">
        {html_options options=$stylesheets selected=$data.dfltstylesheet}      </select>
    </div>
  </div>

  <div class="pageoverflow">
    {$t=_ld($_module,'profile_allowcssoverride')}<label class="pagetext" for="profile_allowcssoverride">{$t}:</label>
    {cms_help realm=$_module key='help_allowcssoverride' title=$t}
    <input type="hidden" name="{$actionid}profile_allowcssoverride" value="0">
    <div class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowcssoverride" id="profile_allowcssoverride" value="1"{if $data.allowcssoverride} checked{/if}>
    </div>
  </div>

  <div class="pregap pageinput">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{_ld($_module,'submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </div>
</form>
