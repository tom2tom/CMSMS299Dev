<h3>{$mod->Lang('edit_profile')}: <em>{$data.label}</em></h3>

{form_start}
  <input type="hidden" name="{$actionid}profile" value="{$profile}"/>
  <input type="hidden" name="{$actionid}origname" value="{$data.name}"/>

  {if $data.system}<div class="pageinfo">{$tmp='profiledesc_'|cat:$data.name}{$mod->Lang($tmp)}</div>{/if}
  <br />

  {if !$data.system}
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=$mod->Lang('profile_name')}<label for="profile_name">*{$t}:</label>
        {cms_help realm=$_module key2='mthelp_profilename' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" size="40" id="profile_name" name="{$actionid}profile_name" value="{$data.name}" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        {$t=$mod->Lang('profile_label')}<label for="profile_label">*{$t}:</label>
        {cms_help realm=$_module key2='mthelp_profilelabel' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" size="80" id="profile_label" name="{$actionid}profile_label" value="{$data.label}" />
      </p>
    </div>
  {/if}

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$mod->Lang('profile_menubar')}<label for="profile_menubar">{$t}:</label>
      {cms_help realm=$_module key2='mthelp_profilemenubar' title=$t}
    </p>
    <input type="hidden" name="{$actionid}profile_menubar" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_menubar" id="profile_menubar" value="1"{if $data.menubar} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$mod->Lang('profile_allowimages')}<label for="profile_allowimages">{$t}:</label>
      {cms_help realm=$_module key2='mthelp_profileallowimages' title=$t}
    </p>
    <input type="hidden" name="{$actionid}profile_allowimages" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowimages" id="profile_allowimages" value="1"{if $data.allowimages} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$mod->Lang('profile_allowtables')}<label for="profile_allowtables">{$t}:</label>
      {cms_help realm=$_module key2='mthelp_profileallowtables' title=$t}
    </p>
    <input type="hidden" name="{$actionid}profile_allowtables" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowtables" id="profile_allowtables" value="1"{if $data.allowtables} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$mod->Lang('profile_showstatusbar')}<label for="profile_showstatusbar">{$t}:</label>
      {cms_help realm=$_module key2='mthelp_profilestatusbar' title=$t}
    </p>
    <input type="hidden" name="{$actionid}profile_showstatusbar" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_showstatusbar" id="profile_showstatusbar" value="1"{if $data.showstatusbar} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$mod->Lang('profile_allowresize')}<label for="profile_allowresize">{$t}:</label>
      {cms_help realm=$_module key2='mthelp_profileresize' title=$t}
    </p>
    <input type="hidden" name="{$actionid}profile_allowresize" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowresize" id="profile_allowresize" value="1"{if $data.allowresize} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$mod->Lang('profile_dfltstylesheet')}<label for="profile_dfltstylesheet">{$t}:</label>
      {cms_help realm=$_module key2='mthelp_dfltstylesheet' title=$t}
    </p>
    <p class="pageinput">
      <select id="profile_dfltstylesheet" name="{$actionid}profile_dfltstylesheet">
        {html_options options=$stylesheets selected=$data.dfltstylesheet}
      </select>
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=$mod->Lang('profile_allowcssoverride')}<label for="profile_allowcssoverride">{$t}:</label>
      {cms_help realm=$_module key2='mthelp_allowcssoverride' title=$t}
    </p>
    <input type="hidden" name="{$actionid}profile_allowcssoverride" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowcssoverride" id="profile_allowcssoverride" value="1"{if $data.allowcssoverride} checked="checked"{/if} />
    </p>
  </div>
  <div class="pageoverflow pregap">
    <p class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
    </p>
  </div>
</form>
