<h3>{$mod->Lang('edit_profile')}: <em>{$data.label}</em></h3>

{form_start}
  <input type="hidden" name="{$actionid}profile" value="{$profile}"/>
  <input type="hidden" name="{$actionid}origname" value="{$data.name}"/>

  {if $data.system}<div class="information">{$tmp='profiledesc_'|cat:$data.name}{$mod->Lang($tmp)}</div>{/if}

  <div class="pageoverflow">
    <p class="pagetext"></p>
    <p class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit iconcheck">{$mod->Lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" class="adminsubmit iconcancel">{$mod->Lang('cancel')}</button>
    </p>
  </div>

  {if !$data.system}
    <div class="pageoverflow">
      <p class="pagetext">
        <label for="profile_name">*{$mod->Lang('profile_name')}:</label>
        {cms_help realm=$_module key2='mthelp_profilename' title=$mod->Lang('profile_name')}
      </p>
      <p class="pageinput">
        <input type="text" size="40" id="profile_name" name="{$actionid}profile_name" value="{$data.name}" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        <label for="profile_label">*{$mod->Lang('profile_label')}:</label>
        {cms_help realm=$_module key2='mthelp_profilelabel' title=$mod->Lang('profile_label')}
      </p>
      <p class="pageinput">
        <input type="text" size="80" id="profile_label" name="{$actionid}profile_label" value="{$data.label}" />
      </p>
    </div>
  {/if}

  <div class="pageoverflow">
    <p class="pagetext">
      <label for="profile_menubar">{$mod->Lang('profile_menubar')}:</label>
      {cms_help realm=$_module key2='mthelp_profilemenubar' title=$mod->Lang('profile_menubar')}
    </p>
    <input type="hidden" name="{$actionid}profile_menubar" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_menubar" id="profile_menubar" value="1"{if $data.menubar} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      <label for="profile_allowimages">{$mod->Lang('profile_allowimages')}:</label>
      {cms_help realm=$_module key2='mthelp_profileallowimages' title=$mod->Lang('profile_allowimages')}
    </p>
    <input type="hidden" name="{$actionid}profile_allowimages" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowimages" id="profile_allowimages" value="1"{if $data.allowimages} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      <label for="profile_allowtables">{$mod->Lang('profile_allowtables')}:</label>
      {cms_help realm=$_module key2='mthelp_profileallowtables' title=$mod->Lang('profile_allowtables')}
    </p>
    <input type="hidden" name="{$actionid}profile_allowtables" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowtables" id="profile_allowtables" value="1"{if $data.allowtables} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      <label for="profile_showstatusbar">{$mod->Lang('profile_showstatusbar')}:</label>
      {cms_help realm=$_module key2='mthelp_profilestatusbar' title=$mod->Lang('profile_showstatusbar')}
    </p>
    <input type="hidden" name="{$actionid}profile_showstatusbar" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_showstatusbar" id="profile_showstatusbar" value="1"{if $data.showstatusbar} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      <label for="profile_allowresize">{$mod->Lang('profile_allowresize')}:</label>
      {cms_help realm=$_module key2='mthelp_profileresize' title=$mod->Lang('profile_allowresize')}
    </p>
    <input type="hidden" name="{$actionid}profile_allowresize" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowresize" id="profile_allowresize" value="1"{if $data.allowresize} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      <label for="profile_dfltstylesheet">{$mod->Lang('profile_dfltstylesheet')}:</label>
      {cms_help realm=$_module key2='mthelp_dfltstylesheet' title=$mod->Lang('profile_dfltstylesheet')}
    </p>
    <p class="pageinput">
      <select id="profile_dfltstylesheet" name="{$actionid}profile_dfltstylesheet">
        {html_options options=$stylesheets selected=$data.dfltstylesheet}
      </select>
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      <label for="profile_allowcssoverride">{$mod->Lang('profile_allowcssoverride')}:</label>
      {cms_help realm=$_module key2='mthelp_allowcssoverride' title=$mod->Lang('profile_allowcssoverride')}
    </p>
    <input type="hidden" name="{$actionid}profile_allowcssoverride" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="{$actionid}profile_allowcssoverride" id="profile_allowcssoverride" value="1"{if $data.allowcssoverride} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext"></p>
    <p class="pageinput">
      <button type="submit" name="{$actionid}submit" class="adminsubmit iconcheck">{$mod->Lang('submit')}</button>
      <button type="submit" name="{$actionid}cancel" class="adminsubmit iconcancel">{$mod->Lang('cancel')}</button>
    </p>
  </div>
{form_end}
