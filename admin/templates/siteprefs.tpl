{tab_header name='general' label=lang('general_settings') active=$tab}
{tab_header name='editcontent' label=lang('editcontent_settings') active=$tab}
{tab_header name='sitedown' label=lang('sitedown_settings') active=$tab}
{tab_header name='mail' label=lang('mail_settings') active=$tab}
{tab_header name='advanced' label=lang('advanced') active=$tab}
{* +++++++++++++++++++++++++++++++++++++++++++ *}
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
    <p class="pagetext">
      {$t=lang('sitename')}<label for="sitename">{$t}:</label>
      {cms_help key2='siteprefs_sitelogo' title=$t}
    </p>
    <p class="pageinput">
      <input type="text" id="sitename" class="pagesmalltextarea" name="sitename" size="30" value="{$sitename}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('sitelogo')}<label for="sitelogo">{$t}:</label>
      {cms_help key2='siteprefs_sitelogo' title=$t}
    </p>
    <p class="pageinput">
      {$logoselect}
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('frontendlang')}<label for="frontendlang">{$t}:</label>
      {cms_help key2='siteprefs_frontendlang' title=$t}
    </p>
    <p class="pageinput">
      <select id="frontendlang" name="frontendlang" style="vertical-align: middle;">
        {html_options options=$languages selected=$frontendlang}
      </select>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('globalmetadata')}<label for="globalmetadata">{$t}:</label>
      {cms_help key2='siteprefs_globalmetadata' title=$t}
    </p>
    <p class="pageinput"><textarea id="globalmetadata" class="pagesmalltextarea" name="metadata" cols="80" rows="20">{$metadata}</textarea></p>
  </div>
  {if isset($themes)}
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('master_admintheme')}<label for="logintheme">{$t}:</label>
      {cms_help key2='siteprefs_logintheme' title=$t}
    </p>
    <p class="pageinput">
      <select id="logintheme" name="logintheme">
       {html_options options=$themes selected=$logintheme}
      </select>
    </p>
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
    <p class="pagetext">
      {$t=lang('date_format_string')}<label for="defaultdateformat">{$t}:</label>
      {cms_help key2='siteprefs_dateformat' title=$t}
    </p>
    <p class="pageinput">
      <input class="pagenb" id="defaultdateformat" type="text" name="defaultdateformat" size="20" maxlength="255" value="{$defaultdateformat}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
       {$t=lang('thumbnail_width')}<label for="thumbnail_width">{$t}:</label>
       {cms_help key2='siteprefs_thumbwidth' title=$t}
   </p>
    <p class="pageinput">
      <input class="pagenb" id="thumbnail_width" type="text" name="thumbnail_width" size="3" maxlength="3" value="{$thumbnail_width}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('thumbnail_height')}<label for="thumbnail_height">{$t}:</label>
      {cms_help key2='siteprefs_thumbheight' title=$t}
    </p>
    <p class="pageinput">
      <input id="thumbnail_height" class="pagenb" type="text" name="thumbnail_height" size="3" maxlength="3" value="{$thumbnail_height}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('frontendwysiwygtouse')}<label for="frontendwysiwyg">{$t}:</label>
      {cms_help key2='siteprefs_frontendwysiwyg' title=$t}
    </p>
    <p class="pageinput">
      <select id="frontendwysiwyg" name="frontendwysiwyg">
        {html_options options=$wysiwyg selected=$frontendwysiwyg}
      </select>
    </p>
  </div>
  {if !empty($search_modules)}
  <p class="pagetext">
     {$t=lang('search_module')}<label for="search_module">{$t}:</label>
     {cms_help key2='settings_searchmodule' title=$t}
  </p>
  <p class="pageinput">
    <select id="search_module" name="search_module">
     {html_options options=$search_modules selected=$search_module}
    </select>
  </p>
  {/if}
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{if !empty($modtheme)}
<div id="importdlg" title="{lang('importtheme')}" style="display:none;">
 <form id="importform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
  <p class="pageinput">
   <input type="file" id="xml_upload" title="{lang('help_themeimport')|escape:"javascript"}" name="import" accept="text/xml" />
  </p>
 </form>
</div>
{if isset($themes) && count($themes) > 1}
<div id="deletedlg" title="{lang('deletetheme')}" style="display:none;">
 <form id="deleteform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
  <p class="pageinput">
   <select name="delete">
    {html_options options=$themes}
   </select>
  </p>
 </form>
</div>
{/if}
{/if}
{if !empty($exptheme)}
<div id="exportdlg" title="{lang('exporttheme')}" style="display:none;">
 <form id="exportform" action="themeoperation.php{$urlext}" enctype="multipart/form-data" method="post">
   <p class="pageinput">
    <select title="{lang('help_themeexport')}" name="export">
     {html_options options=$themes}
    </select>
   </p>
 </form>
</div>
{/if}
{* +++++++++++++++++++++++++++++++++++++++++++ *}
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
    <p class="pagetext">
      {$t=lang('content_autocreate_urls')}<label for="content_autocreate_urls">{$t}:</label>
      {cms_help key2='settings_autocreate_url' title=$t}
    </p>
    <input type="hidden" name="content_autocreate_urls" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="content_autocreate_urls" id="content_autocreate_urls" value="1"{if $content_autocreate_urls} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('content_autocreate_flaturls')}<label for="content_autocreate_flaturls">{$t}:</label>
      {cms_help key2='settings_autocreate_flaturls' title=$t}
    </p>
    <input type="hidden" name="content_autocreate_flaturls" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="content_autocreate_flaturls" id="content_autocreate_flaturls" value="1"{if $content_autocreate_flaturls} checked="checked"{/if} />
    </p>
  </div>

  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('content_mandatory_urls')}<label for="content_mandatory_urls">{$t}:</label>
      {cms_help key2='settings_mandatory_urls' title=$t}
    </p>
    <input type="hidden" name="content_mandatory_urls" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="content_mandatory_urls" id="content_mandatory_urls" value="1"{if $content_mandatory_urls} checked="checked"{/if} />
    </p>
  </div>
  {/if}
  <div class="pageoverflow">
    <p class="pagetext">
     {$t=lang('disallowed_contenttypes')}<label for="disallowed_contenttypes">{$t}:</label>
     {cms_help key2='settings_badtypes' title=$t}
    </p>
    <p class="pageinput">
      <select id="disallowed_contenttypes" name="disallowed_contenttypes[]" multiple="multiple" size="5">
        {html_options options=$all_contenttypes selected=$disallowed_contenttypes}
      </select>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('basic_attributes')}<label for="basic_attributes">{$t}:</label>
      {cms_help key2='settings_basicattribs2' title=$t}
    </p>
    <p class="pageinput">
      <select id="basic_attributes" class="multicolumn" name="basic_attributes[]" multiple="multiple" size="5">
        {cms_html_options options=$all_attributes selected=$basic_attributes}
      </select>
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('content_imagefield_path')}<label for="imagefield_path">{$t}:</label>
      {cms_help key2='settings_imagefield_path' title=$t}
    </p>
    <p class="pageinput">
      <input id="imagefield_path" type="text" name="content_imagefield_path" size="50" maxlength="255" value="{$content_imagefield_path}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('content_thumbnailfield_path')}<label for="thumbfield_path">{$t}:</label>
      {cms_help key2='settings_thumbfield_path' title=$t}
    </p>
    <p class="pageinput">
      <input id="thumbfield_path" type="text" name="content_thumbnailfield_path" size="50" maxlength="255" value="{$content_thumbnailfield_path}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('contentimage_path')}<label for="contentimage_path">{$t}:</label>
      {cms_help key2='settings_contentimage_path' title=$t}
    </p>
    <p class="pageinput">
      <input type="text" id="contentimage_path" name="contentimage_path" size="50" maxlength="255" value="{$contentimage_path}" />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('cssnameisblockname')}<label for="cssnameisblockname">{$t}:</label>
      {cms_help key2='settings_cssnameisblockname' title=$t}
    </p>
    <input type="hidden" name="content_cssnameisblockname" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="content_cssnameisblockname" id="cssnameisblockname" value="1"{if $content_cssnameisblockname} checked="checked"{/if} />
    </p>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{* +++++++++++++++++++++++++++++++++++++++++++ *}
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
    <p class="pagetext">
      {$t=lang('enablesitedown')}<label for="enablesitedown">{$t}:</label>
      {cms_help key2='settings_enablesitedown' title=$t}
    </p>
    <input type="hidden" name="enablesitedownmessage" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="enablesitedownmessage" id="enablesitedown" value="1"{if $enablesitedownmessage} checked="checked"{/if} />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
       {$t=lang('sitedownmessage')}<label for="sitedownmessage">{$t}:</label>
       {cms_help key2='settings_sitedownmessage' title=$t}
    </p>
    <p class="pageinput">{$textarea_sitedownmessage}</p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('sitedownexcludeadmins')}<label for="sitedownexcludeadmins">{$t}:</label>
      {cms_help key2='settings_sitedownexcludeadmins' title=$t}
    </p>
    <input type="hidden" name="sitedownexcludeadmins" value="0" />
    <p class="pageinput">
      <input type="checkbox" name="sitedownexcludeadmins" id="sitedownexcludeadmins" value="1"{if $sitedownexcludeadmins} checked="checked"{/if} />
    </p>
  </div>
  <div class="pageoverflow">
    <p class="pagetext">
      {$t=lang('sitedownexcludes')}<label for="sitedownexcludes">{$t}:</label>
      {cms_help key2='settings_sitedownexcludes' title=$t}
    </p>
    <p class="pageinput">
      <input type="text" name="sitedownexcludes" id="sitedownexcludes" size="50" maxlength="255" value="{$sitedownexcludes}" />
      <br />{lang('info_sitedownexcludes')}
      <br />
      <strong>{lang('your_ipaddress')}:</strong>&nbsp;<span style="color:red;">{cms_utils::get_real_ip()}</span>
    </p>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{* +++++++++++++++++++++++++++++++++++++++++++ *}
{tab_start name='mail'}
<div id="testpopup" title="{lang('title_mailtest')}" style="display:none;">
  <form id="siteprefform_mailtest" action="{$selfurl}" enctype="multipart/form-data" method="post">
    <div class="hidden">
      {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
      <input type="hidden" name="active_tab" value="mail" />
    </div>
    <div class="pageinfo">{lang('info_mailtest')}</div>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('settings_testaddress')}<label for="testaddress">{$t}:</label>
        {cms_help key2='settings_mailtest_testaddress' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="testaddress" name="mailtest_testaddress" size="50" maxlength="255" />
      </p>
    </div>
    <div class="pageinput pregap">
      <button type="submit" name="testmail" id="testsend" class="adminsubmit icon do">{lang('sendtest')}</button>
    </div>
  </form>
</div>

<form id="siteprefform_mail" action="{$selfurl}" enctype="multipart/form-data" method="post">
  <div class="hidden">
    {foreach $extraparms as $key => $val}<input type="hidden" name="{$key}" value="{$val}" />
{/foreach}
    <input type="hidden" name="active_tab" value="mail" />
  </div>
  <div class="pageinput postgap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
    <button type="submit" name="testemail" id="mailertest" class="adminsubmit icon do">{lang('test')}</button>
  </div>

  <fieldset id="set_general">
    <legend>{lang('general_settings')}</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('settings_mailer')}<label for="mailer">{$t}:</label>
        {cms_help key2='settings_mailprefs_mailer' title=$t}
      </p>
        <p class="pageinput">
          <select id="mailer" name="mailprefs_mailer">
            {html_options options=$maileritems selected=$mailprefs.mailer}
          </select>
        </p>
      </div>
      <div class="pageoverflow">
        <p class="pagetext">
          {$t=lang('settings_mailfrom')}<label for="from">{$t}:</label>
          {cms_help key2='settings_mailprefs_from' title=$t}
        </p>
      <p class="pageinput">
        <input type="text" id="from" name="mailprefs_from" value="{$mailprefs.from}" size="50" maxlength="255" />
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('settings_mailfromuser')}<label for="fromuser">{$t}:</label>
        {cms_help key2='settings_mailprefs_fromuser' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="fromuser" name="mailprefs_fromuser" value="{$mailprefs.fromuser}" size="50" maxlength="255" />
      </p>
    </div>
  </fieldset>

  <fieldset id="set_smtp">
    <legend>{lang('smtp_settings')}</legend>
    <div class="pageoverflow">
      <p class="pagetext">
         {$t=lang('settings_smtphost')}<label for="host">{$t}:</label>
         {cms_help key2='settings_mailprefs_smtphost' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="host" name="mailprefs_host" value="{$mailprefs.host}" size="50" maxlength="255" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('settings_smtpport')}<label for="port">{$t}:</label>
        {cms_help key2='settings_mailprefs_smtpport' title=$t}
    </p>
      <p class="pageinput">
        <input type="text" id="port" name="mailprefs_port" value="{$mailprefs.port}" size="6" maxlength="8" />
      </p>
    </div>

    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('settings_smtptimeout')}<label for="timeout">{$t}:</label>
        {cms_help key2='settings_mailprefs_smtptimeout' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="timeout" name="mailprefs_timeout" value="{$mailprefs.timeout}" size="6" maxlength="8" />
      </p>
    </div>

    <fieldset>
      <legend>{lang('settings_authentication')}</legend>
      <div class="pageoverflow">
        <p class="pagetext">
          {$t=lang('settings_smtpauth')}<label for="smtpauth">{$t}:</label>
          {cms_help key2='settings_mailprefs_smtpauth' title=$t}
        </p>
        <input type="hidden" name="mailprefs_smtpauth" value="0" />
        <p class="pageinput">
          <input type="checkbox" name="mailprefs_smtpauth" id="smtpauth" value="1"{if $mailprefs.smtpauth} checked="checked"{/if} />
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext">
          {$t=lang('settings_authsecure')}<label for="secure">{$t}:</label>
          {cms_help key2='settings_mailprefs_smtpsecure' title=$t}
        </p>
        <p class="pageinput">
          <select id="secure" name="mailprefs_secure">
            {html_options options=$secure_opts selected=$mailprefs.secure}
          </select>
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext">
          {$t=lang('settings_authusername')}<label for="username">{$t}:</label>
          {cms_help key2='settings_mailprefs_smtpusername' title=$t}
        </p>
        <p class="pageinput">
          <input type="text" id="username" name="mailprefs_username" value="{$mailprefs.username}" size="50" maxlength="255" />
        </p>
      </div>

      <div class="pageoverflow">
        <p class="pagetext">
          {$t=lang('settings_authpassword')}<label for="password">{$t}:</label>
          {cms_help key2='settings_mailprefs_smtppassword' title=$t}
        </p>
        <p class="pageinput">
          <input type="password" id="password" name="mailprefs_password" value="{$mailprefs.password}" size="50" maxlength="50" />
        </p>
      </div>
    </fieldset>
  </fieldset>

  <fieldset id="set_sendmail">
    <legend>{lang('sendmail_settings')}</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('settings_sendmailpath')}<label for="sendmail">{$t}:</label>
        {cms_help key2='settings_mailprefs_sendmail' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="sendmail" name="mailprefs_sendmail" value="{$mailprefs.sendmail}" size="50" maxlength="255" />
      </p>
    </div>
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{* +++++++++++++++++++++++++++++++++++++++++++ *}
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
      <p class="pagetext">
        {$t=lang('allow_browser_cache')}<label for="allow_browser_cache">{$t}:</label>
        {cms_help key2='settings_browsercache' title=$t}
      </p>
      <input type="hidden" name="allow_browser_cache" value="0" />
      <p class="pageinput">
        <input type="checkbox" name="allow_browser_cache" id="allow_browser_cache" value="1"{if $allow_browser_cache} checked="checked"{/if} />
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('browser_cache_expiry')}<label for="browser_expiry">{$t}:</label>
        {cms_help key2='settings_browsercache_expiry' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="browser_expiry" name="browser_cache_expiry" value="{$browser_cache_expiry}" size="6" maxlength="10" />
      </p>
    </div>
  </fieldset>

  <fieldset>
    <legend>{lang('server_cache_settings')}</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('autoclearcache2')}<label for="autoclearcache2">{$t}:</label>
        {cms_help key2='settings_autoclearcache' title=$t}
      </p>
      <p class="pageinput">
        <input id="autoclearcache2" type="text" name="auto_clear_cache_age" size="4" value="{$auto_clear_cache_age}" maxlength="4" />
      </p>
    </div>
  </fieldset>

  <fieldset>
    <legend>{lang('smarty_settings')}</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('smarty_cachelife')}<label for="cache_life">{$t}:</label>
        {cms_help key2='settings_smartycachelife' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="cache_life" name="smarty_cachelife" value="{$smarty_cachelife}" size="6" maxlength="6" />
      </p>
      <p class="pagetext">
        {$t=lang('smarty_compilecheck')}<label for="compilecheck">{$t}:</label>
        {cms_help key2='settings_smartycompilecheck' title=$t}
      </p>
      <input type="hidden" name="use_smartycompilecheck" value="0" />
      <p class="pageinput">
        <input type="checkbox" name="use_smartycompilecheck" id="compilecheck" value="1"{if $use_smartycompilecheck} checked="checked"{/if} />
      </p>
    </div>
  </fieldset>

  <fieldset>
    <legend>{lang('duration_settings')}</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('admin_lock_timeout')}<label for="lock_timeout">{$t}:</label>
        {cms_help key2='settings_lock_timeout' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="lock_timeout" name="lock_timeout" size="3" value="{$lock_timeout}" />
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('admin_lock_refresh')}<label for="lock_refresh">{$t}:</label>
        {cms_help key2='settings_lock_refresh' title=$t}
      </p>
      <p class="pageinput">
        <input type="text" id="lock_refresh" name="lock_refresh" size="4" value="{$lock_refresh}" />
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('adminlog_lifetime')}<label for="adminlog">{$t}:</label>
        {cms_help key2='settings_adminlog_lifetime' title=$t}
      </p>
      <p class="pageinput">
        <select id="adminlog" name="adminlog_lifetime">
          {html_options options=$adminlog_options selected=$adminlog_lifetime}
        </select>
      </p>
    </div>
  </fieldset>

  {if $editors}
  <fieldset>
    <legend>{lang('text_editor_settings')}</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('default_editor')}<label>{$t}:</label>
        {cms_help key2='settings_editor' title=$t}
      </p>
      {$t=lang('about')}
      {foreach $editors as $i=>$one}
       <input type="radio" name="editortype" id="edt{$i}"{if !empty($one->themekey)} data-themehelp-key="{$one->themekey}"{/if} value="{$one->value}"{if !empty($one->checked)} checked{/if}>
       <label for="edt{$i}">{$one->label}</label>
       {if !empty($one->mainkey)}
       <span class="cms_help" data-cmshelp-key="{$one->mainkey}" data-cmshelp-title="{$t} {$one->label}">{$helpicon}</span>
       {/if}<br />
      {/foreach}
      <p class="pagetext">
        <label for="editortheme">{lang('text_editor_deftheme')}:</label>
        <span id="theme_help">{$helpicon}</span>
      </p>
      <p class="pageinput">
        <input id="editortheme" type="text" name="editortheme" size="30" value="{$editortheme}" maxlength="40" />
      </p>
    </div>
  </fieldset>
 {/if}
  <fieldset>
    <legend>{lang('general_operation_settings')}</legend>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('admin_login_module')}<label for="login_module">{$t}:</label>
        {cms_help key2='settings_login_module' title=$t}
      </p>
      <p class="pageinput">
        <select id="login_module" name="login_module">
          {html_options options=$login_modules selected=$login_module}
        </select>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
      {$t=lang('global_umask')}<label for="umask">{$t}:</label>
        {cms_help key2='settings_umask' title=$t}
      </p>
      <p class="pageinput">
        <input id="umask" type="text" class="pagesmalltextarea" name="global_umask" size="4" value="{$global_umask}" />
      </p>
    </div>
    {if isset($testresults)}
    <div class="pageoverflow">
      <p class="pagetext">{lang('results')}</p>
      <p class="pageinput"><strong>{$testresults}</strong></p>
    </div>
    {/if}
    <br />
    <div class="pageoverflow">
      <p class="pageinput">
        <button type="submit" name="testumask" class="adminsubmit icon do">{lang('test')}</button>
      </p>
    </div>
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('checkversion')}<label for="checkversion">{$t}:</label>
        {cms_help key2='settings_checkversion' title=$t}
      </p>
      <input type="hidden" name="checkversion" value="0" />
      <p class="pageinput">
        <input type="checkbox" name="checkversion" id="checkversion" value="1"{if $checkversion} checked="checked"{/if} />
      </p>
    </div>
{if isset($help_url)}
    <div class="pageoverflow">
      <p class="pagetext">
        {$t=lang('adminhelpurl')}<label for="help_url">{$t}:</label>
        {cms_help key2='settings_help_url' title=$t}
      </p>
      <p class="pageinput">
        <input id="help_url" type="text" name="help_url" size="50" value="{$help_url}" maxlength="80" />
      </p>
    </div>
{/if}
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="submit" class="adminsubmit icon apply">{lang('apply')}</button>
    <button type="submit" name="cancel" class="adminsubmit icon cancel">{lang('cancel')}</button>
  </div>
</form>
{tab_end}
