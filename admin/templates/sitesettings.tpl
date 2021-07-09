{tab_header name='general' label=lang('general_settings') active=$tab}
{tab_header name='editcontent' label=lang('editcontent_settings') active=$tab}
{tab_header name='sitedown' label=lang('sitedown_settings') active=$tab}
{* tab_header name='mail' label=lang('mail_settings') active=$tab *}
{tab_header name='advanced' label=lang('advanced') active=$tab}
{if !empty($externals)}
{tab_header name='external' label=lang('external_settings') active=$tab}
{/if}
{* +++++ *}
{tab_start name='general'}
<form id="siteprefform_general" action="{$selfurl}" enctype="multipart/form-data" method="post">
  <div class="hidden">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <input type="hidden" name="active_tab" value="general" />
  </div>
  <div class="pageinput">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('sitename')}
      <label for="sitename">{$t}:</label>
      {cms_help key2='settings_sitename' title=$t}
    </div>
    <div class="pageinput">
      <input type="text" id="sitename" name="sitename" size="30" value="{$sitename}" />
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('sitelogo')}
      <label for="sitelogo">{$t}:</label>
      {cms_help key2='settings_sitelogo' title=$t}
    </div>
    <div class="pageinput">
      {$logoselect}
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('frontendlang')}
      <label for="frontendlang">{$t}:</label>
      {cms_help key2='settings_frontendlang' title=$t}
    </div>
    <div class="pageinput">
      <select id="frontendlang" name="frontendlang" style="vertical-align: middle;">
        {html_options options=$languages selected=$frontendlang}
      </select>
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('globalmetadata')}
      <label for="globalmetadata">{$t}:</label>
      {cms_help key2='settings_globalmetadata' title=$t}
    </div>
{*  <div class="pageinput">
      <textarea id="globalmetadata" class="pagesmalltextarea" name="metadata" rows="20" cols="80">{$metadata}</textarea>
    </div>
*}
    <div class="pageinput">{$textarea_metadata}</div>
  </div>
  {if isset($themes)}
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('master_admintheme')}
      <label for="logintheme">{$t}:</label>
      {cms_help key2='settings_logintheme' title=$t}
    </div>
    <div class="pageinput">
      <select id="logintheme" name="logintheme">
       {html_options options=$themes selected=$logintheme}
      </select>
    </div>
  </div>
  {/if}
  {if isset($themes) || !empty($modtheme)}
   <div class="pageoptions">
   {if !empty($modtheme)}
     <a id="importbtn">{admin_icon icon='import.gif'} {lang('importtheme')}</a>
    {if count($themes) > 1}
    &nbsp;<a id="deletebtn">{admin_icon icon='delete.gif'} {lang('deletetheme')}</a>
    {/if}
   {/if}
   {if isset($themes)}
     {if !empty($exptheme)}&nbsp;{/if}
     <a id="exportbtn">{admin_icon icon='export.gif'} {lang('exporttheme')}</a>
   {/if}
   </div>
  {/if}
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('date_format_string')}
      <label for="defaultdateformat">{$t}:</label>
      {cms_help key2='settings_dateformat' title=$t}
    </div>
    <div class="pageinput">
      <input class="pagenb" id="defaultdateformat" type="text" name="defaultdateformat" size="20" maxlength="255" value="{$defaultdateformat}" />
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('thumbnail_width')}
       <label for="thumbnail_width">{$t}:</label>
       {cms_help key2='settings_thumbwidth' title=$t}
    </div>
    <div class="pageinput">
      <input class="pagenb" id="thumbnail_width" type="text" name="thumbnail_width" size="3" maxlength="3" value="{$thumbnail_width}" />
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('thumbnail_height')}
      <label for="thumbnail_height">{$t}:</label>
      {cms_help key2='settings_thumbheight' title=$t}
    </div>
    <div class="pageinput">
      <input id="thumbnail_height" class="pagenb" type="text" name="thumbnail_height" size="3" maxlength="3" value="{$thumbnail_height}" />
    </div>
  </div>
  {if !empty($wysiwyg_opts)}
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('backendwysiwyg')}
        <label>{$t}:</label>
        {cms_help key2='settings_backendwysiwyg' title=$t}
      </div>
      {$t=lang('about')}
      {foreach $wysiwyg_opts as $i=>$one}
       <input type="radio" name="backendwysiwyg" id="edt{$i}"{if !empty($one->themekey)} data-themehelp-key="{$one->themekey}"{/if} value="{$one->value}"{if !empty($one->checked)} checked="checked"{/if}>
       <label for="edt{$i}">{$one->label}</label>
       {if !empty($one->mainkey)}
       <span class="cms_help" data-cmshelp-key="{$one->mainkey}" data-cmshelp-title="{$t} {$one->label}">{$helpicon}</span>
       {/if}{if !$one@last}<br />{/if}
      {/foreach}
      <div class="pagetext">{$t=lang('wysiwyg_deftheme')}
        <label for="wysiwygtheme">{$t}:</label>
        {cms_help key2='settings_wysiwygtheme' title=$t}
      </div>
      <div class="pageinput">
        <input id="wysiwygtheme" type="text" name="wysiwygtheme" size="30" maxlength="40" value="{$wysiwygtheme}" />
      </div>
    </div>
  {/if}
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('frontendwysiwyg')}
      <label for="frontendwysiwyg">{$t}:</label>
      {cms_help key2='settings_frontendwysiwyg' title=$t}
    </div>
    <div class="pageinput">
      <select id="frontendwysiwyg" name="frontendwysiwyg">
        {html_options options=$wysiwyg selected=$frontendwysiwyg}
      </select>
    </div>
  </div>
  {if !empty($search_modules)}
  <div class="pagetext">{$t=lang('search_module')}
     <label for="search_module">{$t}:</label>
     {cms_help key2='settings_searchmodule' title=$t}
  </div>
  <div class="pageinput">
    <select id="search_module" name="search_module">
     {html_options options=$search_modules selected=$search_module}
    </select>
  </div>
  {/if}
  <fieldset>
    <legend>{lang('credential_settings')}</legend>
  {if $login_modules}
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('admin_login_module')}
      <label for="login_module">{$t}:</label>
      {cms_help key2='settings_login_module' title=$t}
    </div>
    <div class="pageinput">
      <select id="login_module" name="login_module">
        {html_options options=$login_modules selected=$login_module}
      </select>
    </div>
  </div>{/if}
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('admin_login_processor')}
      <label>{$t}:</label>
      {cms_help key2='settings_login_processor' title=$t}
    </div>
    {foreach $login_handlers as $i=>$one}
     <input type="radio" name="login_processor" id="lp{$i}" value="{$i}"{if ($i==$login_handler)} checked{/if}>
     <label for="lp{$i}">{$one}</label>{if !$one@last}<br />{/if}
    {/foreach}
  </div>
  {if isset($pass_levels)}
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('password_level')}
      <label for="passlevel">{$t}:</label>
      {cms_help key2='settings_password_level' title=$t}
    </div>
    <div class="pageinput">
      <select id="passlevel" name="password_level">
        {html_options options=$pass_levels selected=$passwordlevel}
      </select>
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('username_settings')}
      <label for="unamelevel">{$t}:</label>
      {cms_help key2='settings_username_settings' title=$t}
    </div>
    <div class="pageinput">
      <select id="unamelevel" name="username_settings">
        {html_options options=$uname_levels selected=$usernamelevel}
      </select>
    </div>
  </div>
  {/if}{* pass_;evels *}
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{if !empty($modtheme)}
<div id="importdlg" title="{lang('importtheme')}" style="display:none;">
 <form id="importform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
  <div class="pageinput">
   <input type="file" id="xml_upload" title="{lang('help_themeimport')|escape:'javascript'}" name="import" accept="text/xml" />
  </div>
 </form>
</div>
{if isset($themes) && count($themes) > 1}
<div id="deletedlg" title="{lang('deletetheme')}" style="display:none;">
 <form id="deleteform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
  <div class="pageinput">
   <select name="delete">
    {html_options options=$themes}
   </select>
  </div>
 </form>
</div>
{/if}
{/if}
{if !empty($exptheme)}
<div id="exportdlg" title="{lang('exporttheme')}" style="display:none;">
 <form id="exportform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
   <div class="pageinput">
    <select title="{lang('help_themeexport')}" name="export">
     {html_options options=$themes}
    </select>
   </div>
 </form>
</div>
{/if}
{* +++++ *}
{tab_start name='editcontent'}
<form id="siteprefform_editcontent" action="{$selfurl}" enctype="multipart/form-data" method="post">
  <div class="hidden">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <input type="hidden" name="active_tab" value="editcontent" />
  </div>
  {if !$pretty_urls}
  <div class="pagewarn postgap">
    {$t=lang('warn_nosefurl')}{$t}
    {cms_help key2='settings_nosefurl' title=$t}
  </div>
  {/if}
  <div class="pageinput">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
  {if $pretty_urls}
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('content_autocreate_urls')}
      <label for="content_autocreate_urls">{$t}:</label>
      {cms_help key2='settings_autocreate_url' title=$t}
    </div>
    <input type="hidden" name="content_autocreate_urls" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="content_autocreate_urls" id="content_autocreate_urls" value="1"{if $content_autocreate_urls} checked="checked"{/if} />
    </div>
  </div>

  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('content_autocreate_flaturls')}
      <label for="content_autocreate_flaturls">{$t}:</label>
      {cms_help key2='settings_autocreate_flaturls' title=$t}
    </div>
    <input type="hidden" name="content_autocreate_flaturls" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="content_autocreate_flaturls" id="content_autocreate_flaturls" value="1"{if $content_autocreate_flaturls} checked="checked"{/if} />
    </div>
  </div>

  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('content_mandatory_urls')}
      <label for="content_mandatory_urls">{$t}:</label>
      {cms_help key2='settings_mandatory_urls' title=$t}
    </div>
    <input type="hidden" name="content_mandatory_urls" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="content_mandatory_urls" id="content_mandatory_urls" value="1"{if $content_mandatory_urls} checked="checked"{/if} />
    </div>
  </div>
  {/if}
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('disallowed_contenttypes')}
     <label for="disallowed_contenttypes">{$t}:</label>
     {cms_help key2='settings_badtypes' title=$t}
    </div>
    <div class="pageinput">
      <select id="disallowed_contenttypes" name="disallowed_contenttypes[]" multiple="multiple" size="5">
        {html_options options=$all_contenttypes selected=$disallowed_contenttypes}
      </select>
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('basic_attributes')}
      <label for="basic_attributes">{$t}:</label>
      {cms_help key2='settings_basicattribs2' title=$t}
    </div>
    <div class="pageinput">
      <select id="basic_attributes" class="multicolumn" name="basic_attributes[]" multiple="multiple" size="5">
        {cms_html_options options=$all_attributes selected=$basic_attributes}
      </select>
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('content_imagefield_path')}
      <label for="imagefield_path">{$t}:</label>
      {cms_help key2='settings_imagefield_path' title=$t}
    </div>
    <div class="pageinput">
      <input id="imagefield_path" type="text" name="content_imagefield_path" size="50" maxlength="255" value="{$content_imagefield_path}" />
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('content_thumbnailfield_path')}
      <label for="thumbfield_path">{$t}:</label>
      {cms_help key2='settings_thumbfield_path' title=$t}
    </div>
    <div class="pageinput">
      <input id="thumbfield_path" type="text" name="content_thumbnailfield_path" size="50" maxlength="255" value="{$content_thumbnailfield_path}" />
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('contentimage_path')}
      <label for="contentimage_path">{$t}:</label>
      {cms_help key2='settings_contentimage_path' title=$t}
    </div>
    <div class="pageinput">
      <input type="text" id="contentimage_path" name="contentimage_path" size="50" maxlength="255" value="{$contentimage_path}" />
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('cssnameisblockname')}
      <label for="cssnameisblockname">{$t}:</label>
      {cms_help key2='settings_cssnameisblockname' title=$t}
    </div>
    <input type="hidden" name="content_cssnameisblockname" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="content_cssnameisblockname" id="cssnameisblockname" value="1"{if $content_cssnameisblockname} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{* +++++ *}
{tab_start name='sitedown'}
<form id="siteprefform_sitedown" action="{$selfurl}" enctype="multipart/form-data" method="post">
  <div class="hidden">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <input type="hidden" name="active_tab" value="sitedown" />
  </div>
  <div class="pageinput">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('enablesitedown')}
      <label for="sitedownnow">{$t}:</label>
      {cms_help key2='settings_enablesitedown' title=$t}
    </div>
    <input type="hidden" name="site_downnow" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="site_downnow" id="sitedownnow" value="1"{if $sitedown} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('sitedownmessage')}
       <label for="sitedownmessage">{$t}:</label>
       {cms_help key2='settings_sitedownmessage' title=$t}
    </div>
    <div class="pageinput">{$textarea_sitedownmessage}</div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('sitedownexcludeadmins')}
      <label for="sitedownexcludeadmins">{$t}:</label>
      {cms_help key2='settings_sitedownexcludeadmins' title=$t}
    </div>
    <input type="hidden" name="sitedownexcludeadmins" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="sitedownexcludeadmins" id="sitedownexcludeadmins" value="1"{if $sitedownexcludeadmins} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow">
    <div class="pagetext">{$t=lang('sitedownexcludes')}
      <label for="sitedownexcludes">{$t}:</label>
      {cms_help key2='settings_sitedownexcludes' title=$t}
    </div>
    <div class="pageinput">
      <input type="text" name="sitedownexcludes" id="sitedownexcludes" size="50" maxlength="255" value="{$sitedownexcludes}" />
      <br />{lang('info_sitedownexcludes')}
      <br />
      <strong>{lang('your_ipaddress')}:</strong>&nbsp;<span style="color:red;">{cms_utils::get_real_ip()}</span>
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{* +++++ *}
{tab_start name='advanced'}
<form id="siteprefform_advanced" action="{$selfurl}" enctype="multipart/form-data" method="post">
  <div class="hidden">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <input type="hidden" name="active_tab" value="advanced" />
  </div>
  <div class="pageinput postgap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
  <fieldset>
    <legend>{lang('browser_cache_settings')}</legend>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('allow_browser_cache')}
        <label for="allow_browser_cache">{$t}:</label>
        {cms_help key2='settings_browsercache' title=$t}
      </div>
      <input type="hidden" name="allow_browser_cache" value="0" />
      <div class="pageinput">
        <input type="checkbox" name="allow_browser_cache" id="allow_browser_cache" value="1"{if $allow_browser_cache} checked="checked"{/if} />
      </div>
    </div>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('browser_cache_expiry')}
        <label for="browser_expiry">{$t}:</label>
        {cms_help key2='settings_browsercache_expiry' title=$t}
      </div>
      <div class="pageinput">
        <input type="text" id="browser_expiry" name="browser_cache_expiry" size="6" maxlength="10" value="{$browser_cache_expiry}" />
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>{lang('server_cache_settings')}</legend>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('autoclearcache2')}
        <label for="autoclearcache2">{$t}:</label>
        {cms_help key2='settings_autoclearcache' title=$t}
      </div>
      <div class="pageinput">
        <input id="autoclearcache2" type="text" name="auto_clear_cache_age" size="4" maxlength="4" value="{$auto_clear_cache_age}" />
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>{lang('smarty_settings')}</legend>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('smarty_cachelife')}
        <label for="cache_life">{$t}:</label>
        {cms_help key2='settings_smartycachelife' title=$t}
      </div>
      <div class="pageinput">
        <input type="text" id="cache_life" name="smarty_cachelife" value="{$smarty_cachelife}" size="6" maxlength="6" />
      </div>
      <div class="pagetext">{$t=lang('smarty_cachemodules')}
        <label for="cachemodules">{$t}:</label>
        {cms_help key2='settings_smarty_cachemodules' title=$t}
      </div>
      <div class="pageinput">
      {foreach $smarty_cachemodules as $i=>$one}
        <input type="radio" name="smarty_cachemodules" id="smc{$i}" value="{$one->value}"{if !empty($one->checked)} checked="checked"{/if}>
        <label for="smc{$i}">{$one->label}</label>{if !$one@last}<br />{/if}
      {/foreach}
      </div>
      <div class="pagetext">{$t=lang('smarty_cacheusertags')}
        <label for="cacheusertags">{$t}:</label>
        {cms_help key2='settings_smarty_cacheusertags' title=$t}
      </div>
      <input type="hidden" name="smarty_cacheusertags" value="0" />
      <div class="pageinput">
        <input type="checkbox" name="smarty_cacheusertags" id="cacheusertags" value="1"{if $smarty_cacheusertags} checked="checked"{/if} />
      </div>
      <div class="pagetext">{$t=lang('smarty_compilecheck')}
        <label for="compilecheck">{$t}:</label>
        {cms_help key2='settings_smartycompilecheck' title=$t}
      </div>
      <input type="hidden" name="smarty_compilecheck" value="0" />
      <div class="pageinput">
        <input type="checkbox" name="smarty_compilecheck" id="compilecheck" value="1"{if $smarty_compilecheck} checked="checked"{/if} />
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>{lang('duration_settings')}</legend>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('admin_lock_timeout')}
        <label for="lock_timeout">{$t}:</label>
        {cms_help key2='settings_lock_timeout' title=$t}
      </div>
      <div class="pageinput">
        <input type="text" id="lock_timeout" name="lock_timeout" size="3" value="{$lock_timeout}" />
      </div>
    </div>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('admin_lock_refresh')}
        <label for="lock_refresh">{$t}:</label>
        {cms_help key2='settings_lock_refresh' title=$t}
      </div>
      <div class="pageinput">
        <input type="text" id="lock_refresh" name="lock_refresh" size="4" value="{$lock_refresh}" />
      </div>
    </div>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('adminlog_lifetime')}
        <label for="adminlog">{$t}:</label>
        {cms_help key2='settings_adminlog_lifetime' title=$t}
      </div>
      <div class="pageinput">
        <select id="adminlog" name="adminlog_lifetime">
          {html_options options=$adminlog_options selected=$adminlog_lifetime}
        </select>
      </div>
    </div>
  </fieldset>

 {if !empty($syntax_opts)}
  <fieldset>
    <legend>{lang('syntax_editor_settings')}</legend>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('default_editor')}
        <label>{$t}:</label>
        {cms_help key2='settings_syntax' title=$t}
      </div>
      {$t=lang('about')}
      {foreach $syntax_opts as $i=>$one}
       <input type="radio" name="syntaxtype" id="edt{$i}"{if !empty($one->themekey)} data-themehelp-key="{$one->themekey}"{/if} value="{$one->value}"{if !empty($one->checked)} checked="checked"{/if}>
       <label for="edt{$i}">{$one->label}</label>
       {if !empty($one->mainkey)}
       <span class="cms_help" data-cmshelp-key="{$one->mainkey}" data-cmshelp-title="{$t} {$one->label}">{$helpicon}</span>
       {/if}{if !$one@last}<br />{/if}
      {/foreach}
      <div class="pagetext">{$t=lang('syntax_editor_deftheme')}
        <label for="syntaxtheme">{$t}:</label>
        {cms_help key2='settings_syntaxtheme' title=$t}
      </div>
      <div class="pageinput">
        <input id="syntaxtheme" type="text" name="syntaxtheme" size="30" maxlength="40" value="{$syntaxtheme}" />
      </div>
    </div>
  </fieldset>
 {/if}
  <fieldset>
  <legend>{lang('jobs_settings')}</legend>
   <div class="pageoverflow">
     <div class="pagetext">{$t=lang('prompt_frequency')}
       <label for="interval">{$t}:</label>
       {cms_help key2='settings_job_frequency' title=$t}
     </div>
     <div class="pageinput">
         <input type="text" id="interval" name="jobinterval" value="{$jobinterval}" size="4" maxlength="2" />
     </div>
   </div>
   <div class="pageoverflow">
     <div class="pagetext">{$t=lang('prompt_timelimit')}
       <label for="timeout">{$t}:</label>
       {cms_help key2='settings_job_timelimit' title=$t}
     </div>
     <div class="pageinput">
       <input type="text" id="timeout" name="jobtimeout" value="{$jobtimeout}" size="4" maxlength="4" />
     </div>
   </div>
{*
   <div class="pageoverflow">
     <div class="pagetext">{$t=lang('prompt_joburl')}
       <label for="url">{$t}:</label>
       {cms_help key2='settings_job_url' title=$t}
     </div>
     <div class="pageinput">
       <input type="text" id="url" name="joburl" size="50" maxlength="80" value="{$joburl}" />
     </div>
   </div>
*}
  </fieldset>
  <fieldset>
    <legend>{lang('general_operation_settings')}</legend>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('global_umask')}
        <label for="umask">{$t}:</label>
        {cms_help key2='settings_umask' title=$t}
      </div>
      <div class="pageinput">
        <input id="umask" type="text" name="global_umask" size="4" maxlength="5" value="{$global_umask}" />
      </div>
    </div>
    {if isset($testresults)}
    <div class="pageoverflow">
      <div class="pagetext">{lang('results')}</div>
      <div class="pageinput"><strong>{$testresults}</strong></div>
    </div>
    {/if}
    <br />
    <div class="pageoverflow">
      <div class="pageinput">
        <button type="submit" name="testumask" class="adminsubmit icon do">{lang('test')}</button>
      </div>
    </div>
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('checkversion')}
        <label for="checkversion">{$t}:</label>
        {cms_help key2='settings_checkversion' title=$t}
      </div>
      <input type="hidden" name="checkversion" value="0" />
      <div class="pageinput">
        <input type="checkbox" name="checkversion" id="checkversion" value="1"{if $checkversion} checked="checked"{/if} />
      </div>
    </div>
{if isset($help_url)}
    <div class="pageoverflow">
      <div class="pagetext">{$t=lang('adminhelpurl')}
        <label for="help_url">{$t}:</label>
        {cms_help key2='settings_help_url' title=$t}
      </div>
      <div class="pageinput">
        <input id="help_url" type="text" name="help_url" size="50" maxlength="80" value="{$help_url}" />
      </div>
    </div>
{/if}
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{* +++++ *}
{if !empty($externals)}
{tab_start name='external'}
<p class="pageinfo">{lang('external_setdesc')}</p>
{foreach $externals as $val}
  <label for="ext{$val@index}" class="pagetext" style="display:block;">{$val.title}</label>
  {if !empty($val.desc)}<p class="pageinput">{$val.desc}</p>{/if}
  {if !empty($val.url)}
   <a id="ext{$val@index}" class="pageinput postgap" href="{$val.url}" target="_blank">{$val.text}</a>
  {else}
  {$val.text}
  {/if}
{/foreach}
{/if}
{tab_end}
