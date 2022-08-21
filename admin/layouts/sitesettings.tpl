{tab_header name='general' label=_la('general_settings') active=$tab}
{tab_header name='editcontent' label=_la('editcontent_settings') active=$tab}
{tab_header name='sitedown' label=_la('sitedown_settings') active=$tab}
{* tab_header name='mail' label=_la('mail_settings') active=$tab *}
{tab_header name='advanced' label=_la('advanced') active=$tab}
{if !empty($externals)}
{tab_header name='external' label=_la('external_settings') active=$tab}
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
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
  <div class="pageoverflow">
    {$t=_la('sitename')}<label class="pagetext" for="sitename">{$t}:</label>
    {cms_help 0='help' key='settings_sitename' title=$t}
    <div class="pageinput">
      <input type="text" id="sitename" name="sitename" size="30" value="{$sitename}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('sitelogo')}<label class="pagetext" for="sitelogo">{$t}:</label>
    {cms_help 0='help' key='settings_sitelogo' title=$t}
    <div class="pageinput">
      {$logoselect}
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('frontendlang')}<label class="pagetext" for="frontendlang">{$t}:</label>
    {cms_help 0='help' key='settings_frontendlang' title=$t}
    <div class="pageinput">
    <select id="frontendlang" name="frontendlang" style="vertical-align: middle;">
      {html_options options=$languages selected=$frontendlang}    </select>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('globalmetadata')}<label class="pagetext" for="globalmetadata">{$t}:</label>
    {cms_help 0='help' key='settings_globalmetadata' title=$t}
{*  <div class="pageinput">
      <textarea id="globalmetadata" class="pagesmalltextarea" name="metadata" rows="20" cols="80">{$metadata}</textarea>
    </div>
*}
    <div class="pageinput">{$textarea_metadata}</div>
  </div>
  {if isset($themes)}
  <div class="pageoverflow">
    {$t=_la('master_admintheme')}<label class="pagetext" for="logintheme">{$t}:</label>
    {cms_help 0='help' key='settings_logintheme' title=$t}
    <div class="pageinput">
    <select id="logintheme" name="logintheme">
      {html_options options=$themes selected=$logintheme}    </select>
    </div>
  </div>
  {/if}
  {if isset($themes) || !empty($modtheme)}
   <div class="pageoptions">
   {if !empty($modtheme)}
     <a id="importbtn">{admin_icon icon='import.gif'} {_la('importtheme')}</a>
    {if count($themes) > 1}
    &nbsp;<a id="deletebtn">{admin_icon icon='delete.gif'} {_la('deletetheme')}</a>
    {/if}
   {/if}
   {if isset($themes)}
     {if !empty($exptheme)}&nbsp;{/if}
     <a id="exportbtn">{admin_icon icon='export.gif'} {_la('exporttheme')}</a>
   {/if}
   </div>
  {/if}
  <div class="pageoverflow">
    {$t=_la('date_format')}<label class="pagetext" for="dateformat">{$t}:</label>
    {cms_help 0='help' key='settings_dateformat' title=$t}
    <div class="pageinput">
      <input class="pagenb" id="dateformat" type="text" name="date_format" size="20" maxlength="30" value="{$date_format}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('datetime_format')}<label class="pagetext" for="dtformat">{$t}:</label>
    {cms_help 0='help' key='settings_datetimeformat' title=$t}
    <div class="pageinput">
      <input class="pagenb" id="dtformat" type="text" name="datetime_format" size="20" maxlength="30" value="{$datetime_format}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('thumbnail_width')}<label for="thumbnail_width">{$t}:</label>
    {cms_help 0='help' key='settings_thumbwidth' title=$t}
    <div class="pageinput">
      <input class="pagenb" id="thumbnail_width" type="text" name="thumbnail_width" size="3" maxlength="3" value="{$thumbnail_width}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('thumbnail_height')}<label class="pagetext" for="thumbnail_height">{$t}:</label>
    {cms_help 0='help' key='settings_thumbheight' title=$t}
    <div class="pageinput">
      <input id="thumbnail_height" class="pagenb" type="text" name="thumbnail_height" size="3" maxlength="3" value="{$thumbnail_height}" />
    </div>
  </div>
  {if !empty($wysiwyg_opts)}
    <div class="pageoverflow">
      {$t=_la('backendwysiwyg')}<label class="pagetext">{$t}:</label>
      {cms_help 0='help' key='settings_backendwysiwyg' title=$t}
      <div class="pageinput">{$t=_la('about')}
      {foreach $wysiwyg_opts as $i=>$one}
       <input type="radio" name="backendwysiwyg" id="edt{$i}"{if !empty($one->themekey)} data-themehelp-key="{$one->themekey}"{/if} value="{$one->value}"{if !empty($one->checked)} checked="checked"{/if} />
       <label for="edt{$i}">{$one->label}</label>
       {if !empty($one->mainkey)}
       <span class="cms_help" data-cmshelp-key="{$one->mainkey}" data-cmshelp-title="{$t} {$one->label}">{$helpicon}</span>
       {/if}{if !$one@last}<br />{/if}
      {/foreach}
      </div>
    </div>
    <div class="pageoverflow">
      {$t=_la('wysiwyg_deftheme')}<label class="pagetext" for="wysiwygtheme">{$t}:</label>
      {cms_help 0='help' key='settings_wysiwygtheme' title=$t}
      <div class="pageinput">
        <input id="wysiwygtheme" type="text" name="wysiwygtheme" size="30" maxlength="40" value="{$wysiwygtheme}" />
      </div>
    </div>
  {/if}
  <div class="pageoverflow">
    {$t=_la('frontendwysiwyg')}<label class="pagetext" for="frontendwysiwyg">{$t}:</label>
    {cms_help 0='help' key='settings_frontendwysiwyg' title=$t}
    <div class="pageinput">
    <select id="frontendwysiwyg" name="frontendwysiwyg">
      {html_options options=$wysiwyg selected=$frontendwysiwyg}    </select>
    </div>
  </div>
  {if !empty($search_modules)}
  <div class="pageoverflow">
    {$t=_la('search_module')}<label class="pagetext" for="search_module">{$t}:</label>
    {cms_help 0='help' key='settings_searchmodule' title=$t}
    <div class="pageinput">
    <select id="search_module" name="search_module">
      {html_options options=$search_modules selected=$search_module}    </select>
    </div>
  </div>
  {/if}
  <fieldset>
    <legend>{_la('credential_settings')}</legend>
  {if $login_modules}
  <div class="pageoverflow">
    {$t=_la('admin_login_module')}<label class="pagetext" for="login_module">{$t}:</label>
    {cms_help 0='help' key='settings_login_module' title=$t}
    <div class="pageinput">
    <select id="login_module" name="login_module">
      {html_options options=$login_modules selected=$login_module}    </select>
    </div>
  </div>{/if}
  <div class="pageoverflow">
    {$t=_la('admin_login_processor')}<label class="pagetext">{$t}:</label>
    {cms_help 0='help' key='settings_login_processor' title=$t}<br />
    <div class="pageinput">
    {foreach $login_handlers as $i=>$one}
     <input type="radio" name="login_processor" id="lp{$i}" value="{$i}"{if ($i==$login_handler)} checked{/if}>
     <label for="lp{$i}">{$one}</label>{if !$one@last}<br />{/if}
    {/foreach}
    </div>
  </div>
  {if isset($pass_levels)}
  <div class="pageoverflow">
    {$t=_la('password_level')}<label class="pagetext" for="passlevel">{$t}:</label>
    {cms_help 0='help' key='settings_password_level' title=$t}
    <div class="pageinput">
    <select id="passlevel" name="password_level">
      {html_options options=$pass_levels selected=$passwordlevel}    </select>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('username_settings')}<label class="pagetext" for="unamelevel">{$t}:</label>
    {cms_help 0='help' key='settings_username_settings' title=$t}
    <div class="pageinput">
    <select id="unamelevel" name="username_settings">
      {html_options options=$uname_levels selected=$usernamelevel}    </select>
    </div>
  </div>
  {/if}{* pass_;evels *}
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
{if !empty($modtheme)}
<div id="importdlg" title="{_la('importtheme')}" style="display:none;">
 <form id="importform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
  <div class="pageinput">
   <input type="file" id="xml_upload" title="{_la('help_themeimport')|escape:'javascript'}" name="import" accept="text/xml" />
  </div>
 </form>
</div>
{if isset($themes) && count($themes) > 1}
<div id="deletedlg" title="{_la('deletetheme')}" style="display:none;">
 <form id="deleteform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
  <div class="pageinput">
  <select name="delete">
   {html_options options=$themes}  </select>
  </div>
 </form>
</div>
{/if}
{/if}
{if !empty($exptheme)}
<div id="exportdlg" title="{_la('exporttheme')}" style="display:none;">
 <form id="exportform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
   <div class="pageinput">
   <select title="{_la('help_themeexport')}" name="export">
     {html_options options=$themes}   </select>
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
    {$t=_la('warn_nosefurl')}{$t}
    {cms_help 0='help' key='settings_nosefurl' title=$t}
  </div>
  {/if}
  <div class="pageinput">
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
  {if $pretty_urls}
  <div class="pageoverflow">
    {$t=_la('content_autocreate_urls')}<label class="pagetext" for="create_urls">{$t}:</label>
    {cms_help 0='help' key='settings_autocreate_url' title=$t}
    <input type="hidden" name="content_autocreate_urls" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="content_autocreate_urls" id="create_urls" value="1"{if $content_autocreate_urls} checked="checked"{/if} />
    </div>
  </div>

  <div class="pageoverflow">
    {$t=_la('content_autocreate_flaturls')}<label class="pagetext" for="create_flaturls">{$t}:</label>
    {cms_help 0='help' key='settings_autocreate_flaturls' title=$t}
    <input type="hidden" name="content_autocreate_flaturls" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="content_autocreate_flaturls" id="create_flaturls" value="1"{if $content_autocreate_flaturls} checked="checked"{/if} />
    </div>
  </div>

  <div class="pageoverflow">
    {$t=_la('content_mandatory_urls')}<label class="pagetext" for="mandatory_urls">{$t}:</label>
    {cms_help 0='help' key='settings_mandatory_urls' title=$t}
    <input type="hidden" name="content_mandatory_urls" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="content_mandatory_urls" id="mandatory_urls" value="1"{if $content_mandatory_urls} checked="checked"{/if} />
    </div>
  </div>
  {/if}
  <div class="pageoverflow">
    {$t=_la('disallowed_contenttypes')}<label class="pagetext" for="disallowed_types">{$t}:</label>
    {cms_help 0='help' key='settings_badtypes' title=$t}
    <div class="pageinput">
      <select id="disallowed_types" name="disallowed_contenttypes[]" multiple="multiple" size="5">
        {html_options options=$all_contenttypes selected=$disallowed_contenttypes}    </select>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('basic_attributes')}<label class="pagetext" for="basic_attributes">{$t}:</label>
    {cms_help 0='help' key='settings_basicattribs2' title=$t}
    <div class="pageinput">
    <select id="basic_attributes" class="multicolumn" name="basic_attributes[]" multiple="multiple" size="5">
      {cms_html_options options=$all_attributes selected=$basic_attributes}    </select>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('content_imagefield_path')}<label class="pagetext" for="imagefield_path">{$t}:</label>
    {cms_help 0='help' key='settings_imagefield_path' title=$t}
    <div class="pageinput">
      <input id="imagefield_path" type="text" name="content_imagefield_path" size="50" maxlength="255" value="{$content_imagefield_path}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('content_thumbnailfield_path')}<label class="pagetext" for="thumbfield_path">{$t}:</label>
    {cms_help 0='help' key='settings_thumbfield_path' title=$t}
    <div class="pageinput">
      <input id="thumbfield_path" type="text" name="content_thumbnailfield_path" size="50" maxlength="255" value="{$content_thumbnailfield_path}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('contentimage_path')}<label class="pagetext" for="contentimage_path">{$t}:</label>
    {cms_help 0='help' key='settings_contentimage_path' title=$t}
    <div class="pageinput">
      <input type="text" id="contentimage_path" name="contentimage_path" size="50" maxlength="255" value="{$contentimage_path}" />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('cssnameisblockname')}<label class="pagetext" for="cssnameisblockname">{$t}:</label>
    {cms_help 0='help' key='settings_cssnameisblockname' title=$t}
    <input type="hidden" name="content_cssnameisblockname" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="content_cssnameisblockname" id="cssnameisblockname" value="1"{if $content_cssnameisblockname} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
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
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
  <div class="pageoverflow">
    {$t=_la('enablesitedown')}<label class="pagetext" for="sitedownnow">{$t}:</label>
    {cms_help 0='help' key='settings_enablesitedown' title=$t}
    <input type="hidden" name="site_downnow" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="site_downnow" id="sitedownnow" value="1"{if $sitedown} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('sitedownmessage')}<label class="pagetext" for="sitedownmessage">{$t}:</label>
    {cms_help 0='help' key='settings_sitedownmessage' title=$t}
    <div class="pageinput">{$textarea_sitedownmessage}</div>
  </div>
  <div class="pageoverflow">
    {$t=_la('sitedownexcludeadmins')}<label class="pagetext" for="sitedownexcludeadmins">{$t}:</label>
    {cms_help 0='help' key='settings_sitedownexcludeadmins' title=$t}
    <input type="hidden" name="sitedownexcludeadmins" value="0" />
    <div class="pageinput">
      <input type="checkbox" name="sitedownexcludeadmins" id="sitedownexcludeadmins" value="1"{if $sitedownexcludeadmins} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow">
    {$t=_la('sitedownexcludes')}<label class="pagetext" for="sitedownexcludes">{$t}:</label>
    {cms_help 0='help' key='settings_sitedownexcludes' title=$t}
    <div class="pageinput">
      <input type="text" name="sitedownexcludes" id="sitedownexcludes" size="50" maxlength="255" value="{$sitedownexcludes}" />
      <br />{_la('info_sitedownexcludes')}
      <br />
      <strong>{_la('your_ipaddress')}:</strong>&nbsp;<span style="color:red;">{cms_utils::get_real_ip()}</span>
    </div>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
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
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
  <fieldset>
    <legend>{_la('browser_cache_settings')}</legend>
    <div class="pageoverflow">
      {$t=_la('allow_browser_cache')}<label class="pagetext" for="allow_browser_cache">{$t}:</label>
      {cms_help 0='help' key='settings_browsercache' title=$t}
      <input type="hidden" name="allow_browser_cache" value="0" />
      <div class="pageinput">
        <input type="checkbox" name="allow_browser_cache" id="allow_browser_cache" value="1"{if $allow_browser_cache} checked="checked"{/if} />
      </div>
    </div>
    <div class="pageoverflow">
      {$t=_la('browser_cache_expiry')}<label class="pagetext" for="browser_expiry">{$t}:</label>
      {cms_help 0='help' key='settings_browsercache_expiry' title=$t}
      <div class="pageinput">
        <input type="text" id="browser_expiry" name="browser_cache_expiry" size="6" maxlength="10" value="{$browser_cache_expiry}" />
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>{_la('server_cache_settings')}</legend>
    <div class="pageoverflow">
      {$t=_la('autoclearcache2')}<label class="pagetext" for="autoclearcache2">{$t}:</label>
      {cms_help 0='help' key='settings_autoclearcache' title=$t}
      <div class="pageinput">
        <input id="autoclearcache2" type="text" name="auto_clear_cache_age" size="4" maxlength="4" value="{$auto_clear_cache_age}" />
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>{_la('smarty_settings')}</legend>
    <div class="pageoverflow">
      {$t=_la('smarty_cachelife')}<label class="pagetext" for="cache_life">{$t}:</label>
      {cms_help 0='help' key='settings_smartycachelife' title=$t}
      <div class="pageinput">
        <input type="text" id="cache_life" name="smarty_cachelife" value="{$smarty_cachelife}" size="6" maxlength="6" />
      </div>
      {$t=_la('smarty_cachemodules')}<label class="pagetext" for="cachemodules">{$t}:</label>
      {cms_help 0='help' key='settings_smarty_cachemodules' title=$t}
      <div class="pageinput">
      {foreach $smarty_cachemodules as $i=>$one}
        <input type="radio" name="smarty_cachemodules" id="smc{$i}" value="{$one->value}"{if !empty($one->checked)} checked="checked"{/if}>
        <label for="smc{$i}">{$one->label}</label>{if !$one@last}<br />{/if}
      {/foreach}
      </div>
      {$t=_la('smarty_cacheusertags')}<label class="pagetext" for="cacheusertags">{$t}:</label>
      {cms_help 0='help' key='settings_smarty_cacheusertags' title=$t}
      <input type="hidden" name="smarty_cacheusertags" value="0" />
      <div class="pageinput">
        <input type="checkbox" name="smarty_cacheusertags" id="cacheusertags" value="1"{if $smarty_cacheusertags} checked="checked"{/if} />
      </div>
      {$t=_la('smarty_compilecheck')}<label class="pagetext" for="compilecheck">{$t}:</label>
      {cms_help 0='help' key='settings_smartycompilecheck' title=$t}
      <input type="hidden" name="smarty_compilecheck" value="0" />
      <div class="pageinput">
        <input type="checkbox" name="smarty_compilecheck" id="compilecheck" value="1"{if $smarty_compilecheck} checked="checked"{/if} />
      </div>
    </div>
  </fieldset>

  <fieldset>
    <legend>{_la('duration_settings')}</legend>
    <div class="pageoverflow">
      {$t=_la('admin_lock_timeout')}<label class="pagetext" for="lock_timeout">{$t}:</label>
      {cms_help 0='help' key='settings_lock_timeout' title=$t}
      <div class="pageinput">
        <input type="text" id="lock_timeout" name="lock_timeout" size="3" value="{$lock_timeout}" />
      </div>
    </div>
    <div class="pageoverflow">
      {$t=_la('admin_lock_refresh')}<label class="pagetext" for="lock_refresh">{$t}:</label>
      {cms_help 0='help' key='settings_lock_refresh' title=$t}
      <div class="pageinput">
        <input type="text" id="lock_refresh" name="lock_refresh" size="4" value="{$lock_refresh}" />
      </div>
    </div>
    <div class="pageoverflow">
      {$t=_la('adminlog_lifetime')}<label class="pagetext" for="adminlog">{$t}:</label>
      {cms_help 0='help' key='settings_adminlog_lifetime' title=$t}
      <div class="pageinput">
      <select id="adminlog" name="adminlog_timeout">
        {html_options options=$adminlog_options selected=$adminlog_timeout}    </select>
      </div>
    </div>
    <div class="pageoverflow">
      {$t=_la('login_timeout')}<label class="pagetext" for="loginlife">{$t}:</label>
      {cms_help 0='help' key='settings_login_timeout' title=$t}
      <div class="pageinput">
        <input type="text" id="loginlife" name="login_duration" size="3" value="{$logintimeout}" />
      </div>
    </div>
  </fieldset>
  <fieldset>
  <legend>{_la('jobs_settings')}</legend>
   <div class="pageoverflow">
     {$t=_la('prompt_frequency')}<label  class="pagetext" for="interval">{$t}:</label>
     {cms_help 0='help' key='settings_job_frequency' title=$t}
     <div class="pageinput">
       <input type="text" id="interval" name="jobinterval" value="{$jobinterval}" size="4" maxlength="2" />
     </div>
   </div>
   <div class="pageoverflow">
     {$t=_la('prompt_timelimit')}<label class="pagetext" for="timeout">{$t}:</label>
     {cms_help 0='help' key='settings_job_timelimit' title=$t}
     <div class="pageinput">
       <input type="text" id="timeout" name="jobtimeout" value="{$jobtimeout}" size="4" maxlength="4" />
     </div>
   </div>
{*
   <div class="pageoverflow">
     {$t=_la('prompt_joburl')}<label class="pagetext" for="url">{$t}:</label>
     {cms_help 0='help' key='settings_job_url' title=$t}
     <div class="pageinput">
       <input type="text" id="url" name="joburl" size="50" maxlength="80" value="{$joburl}" />
     </div>
   </div>
*}
  </fieldset>

 {if !empty($syntax_opts)}
  <fieldset>
    <legend>{_la('syntax_editor_settings')}</legend>
    <div class="pageoverflow">
      {$t=_la('default_editor')}<label class="pagetext">{$t}:</label>
      {cms_help 0='help' key='settings_syntax' title=$t}
      <div class="pageinput">{$t=_la('about')}
      {foreach $syntax_opts as $i=>$one}
       <input type="radio" name="syntaxtype" id="edt{$i}"{if !empty($one->themekey)} data-themehelp-key="{$one->themekey}"{/if} value="{$one->value}"{if !empty($one->checked)} checked="checked"{/if}>
       <label for="edt{$i}">{$one->label}</label>
       {if !empty($one->mainkey)}
       <span class="cms_help" data-cmshelp-key="{$one->mainkey}" data-cmshelp-title="{$t} {$one->label}">{$helpicon}</span>
       {/if}{if !$one@last}<br />{/if}
      {/foreach}
      </div>
      {$t=_la('syntax_editor_deftheme')}<label class="pagetext" for="syntaxtheme">{$t}:</label>
      {cms_help 0='help' key='settings_syntaxtheme' title=$t}
      <div class="pageinput">
        <input id="syntaxtheme" type="text" name="syntaxtheme" size="30" maxlength="40" value="{$syntaxtheme}" />
      </div>
    </div>
  </fieldset>
 {/if}
  <fieldset>
    <legend>{_la('general_operation_settings')}</legend>
    <div class="pageoverflow">
      {$t=_la('global_umask')}<label class="pagetext" for="umask">{$t}:</label>
      {cms_help 0='help' key='settings_umask' title=$t}
      <div class="pageinput">
        <input id="umask" type="text" name="global_umask" size="4" maxlength="5" value="{$global_umask}" />
      </div>
    </div>
    {if isset($testresults)}
    <div class="pageoverflow">
      <div class="pagetext">{_la('results')}</div>
      <div class="pageinput"><strong>{$testresults}</strong></div>
    </div>
    {/if}
    <br />
    <div class="pageoverflow">
      <div class="pageinput">
        <button type="submit" name="testumask" class="adminsubmit icon do">{_la('test')}</button>
      </div>
    </div>
    <div class="pageoverflow">
      {$t=_la('checkversion')}<label class="pagetext" for="checkversion">{$t}:</label>
      {cms_help 0='help' key='settings_checkversion' title=$t}
      <input type="hidden" name="checkversion" value="0" />
      <div class="pageinput">
        <input type="checkbox" name="checkversion" id="checkversion" value="1"{if $checkversion} checked="checked"{/if} />
      </div>
    </div>
{if isset($help_url)}
    <div class="pageoverflow">
      {$t=_la('adminhelpurl')}<label class="pagetext" for="help_url">{$t}:</label>
      {cms_help 0='help' key='settings_help_url' title=$t}
      <div class="pageinput">
        <input id="help_url" type="text" name="help_url" size="50" maxlength="80" value="{$help_url}" />
      </div>
    </div>
{/if}
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{_la('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{_la('cancel')}</button>
  </div>
</form>
{* +++++ *}
{if !empty($externals)}
{tab_start name='external'}
<p class="pageinfo">{_la('external_setdesc')}</p>
{foreach $externals as $val}
  <label class="pagetext" for="ext{$val@index}" style="display:block;">{$val.title}</label>
  {if !empty($val.desc)}<p class="pageinput">{$val.desc}</p>{/if}
  {if !empty($val.url)}
   <a id="ext{$val@index}" class="pageinput postgap" href="{$val.url}" target="_blank">{$val.text}</a>
  {else}
  {$val.text}
  {/if}
{/foreach}
{/if}
{tab_end}
