{if isset($message)}<p>{$message}</p>{/if}
{if $pgates}
{tab_header name='internal' label=_ld($_module,'internal') active=$tab}
{tab_header name='test' label=_ld($_module,'test') active=$tab}
{tab_header name='gates' label=_ld($_module,'external') active=$tab}
{else}
{tab_header name='internal' label=_ld($_module,'operation') active=$tab}
{tab_header name='test' label=_ld($_module,'test') active=$tab}
{/if}
{if $pmod}
{tab_header name='settings' label=_ld($_module,'module') active=$tab}
{/if}
{tab_start name='internal'}
<p class="pageinfo">{_ld($_module,'info_outmailer1')}</p>
{$startform}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}">
{/foreach}
  <div class="pageinput postgap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_ld($_module,'apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </div>
  <fieldset><legend>{_ld($_module,'core')}</legend>
  <div class="pageoverflow">
    {$t=$title_mailer}<label class="pagetext" for="mailer">{$t}:</label>
    {cms_help realm=$_module key='info_mailer' title=$t}
    <div class="pageinput">
      <select id="mailer" name="{$actionid}mailer">
      {html_options options=$opts_mailer selected=$value_mailer}      </select>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_from}<label class="pagetext" for="from">{$t}:</label>
    {cms_help realm=$_module key='info_from' title=$t}
    <div class="pageinput">
      <input type="text" id="from" name="{$actionid}from" value="{$value_from}" size="40" maxlength="80">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_fromuser}<label class="pagetext" for="fromuser">{$t}:</label>
    {cms_help realm=$_module key='info_fromuser' title=$t}
    <div class="pageinput">
      <input type="text" id="fromuser" name="{$actionid}fromuser" value="{$value_fromuser}" size="40" maxlength="80">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_charset}<label class="pagetext" for="charset">{$t}:</label>
    {cms_help realm=$_module key='info_charset' title=$t}
    <div class="pageinput">
      <input type="text" id="charset" name="{$actionid}charset" value="{$value_charset}" size="10" maxlength="20">
    </div>
  </div>
  <fieldset class="set_smtp"><legend >{_ld($_module,'smtp_legend')}</legend>
  <div class="pageoverflow">
    {$t=$title_secure}<label class="pagetext" for="secure">{$t}:</label>
    {cms_help realm=$_module key='info_secure' title=$t}
    <div class="pageinput">
      <select id="secure" name="{$actionid}secure">
      {html_options options=$opts_secure selected=$value_secure}      </select>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_host}<label class="pagetext" for="host">{$t}:</label>
    {cms_help realm=$_module key='info_host' title=$t}
    <div class="pageinput">
      <input type="text" id="host" name="{$actionid}host" value="{$value_host}" size="50" maxlength="80">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_port}<label class="pagetext" for="port">{$t}:</label>
    {cms_help realm=$_module key='info_port' title=$t}
    <div class="pageinput">
      <input type="text" id="port" name="{$actionid}port" value="{$value_port}" size="5" maxlength="5">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_timeout}<label class="pagetext" for="timeout">{$t}:</label>
    {cms_help realm=$_module key='info_timeout' title=$t}
    <div class="pageinput">
      <input type="text" id="timeout" name="{$actionid}timeout" value="{$value_timeout}" size="3" maxlength="5">
    </div>
  </div>
  <input type="hidden" name="{$actionid}smtpauth" value="0">
  <div class="pageoverflow">
    {$t=$title_smtpauth}<label class="pagetext" for="smtpauth">{$t}:</label>
    {cms_help realm=$_module key='info_smtpauth' title=$t}
    <div class="pageinput">
      <input type="checkbox" id="smtpauth" name="{$actionid}smtpauth" value="1"{if $value_smtpauth} checked{/if}>
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_username}<label class="pagetext" for="username">{$t}:</label>
    {cms_help realm=$_module key='info_username' title=$t}
    <div class="pageinput">
      <input type="text" id="username" name="{$actionid}username" value="{$value_username}" size="40" maxlength="64">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_password}<label class="pagetext" for="password">{$t}:</label>
    {cms_help realm=$_module key='info_password' title=$t}
    <div class="pageinput">
      <input type="text" id="password" class="cloaked" name="{$actionid}password" value="{$value_password}" size="40" maxlength="80">
    </div>
  </div>
  </fieldset>
  <fieldset class="set_sendmail"><legend>{_ld($_module,'sendmail_legend')}</legend>
  <div class="pageoverflow">
    {$t=$title_sendmail}<label class="pagetext" for="sendmail">{$t}:</label>
    {cms_help realm=$_module key='info_sendmail' title=$t}
    <div class="pageinput">
      <input type="text" id="sendmail" name="{$actionid}sendmail" value="{$value_sendmail}" size="50" maxlength="255">
    </div>
  </div>
  </fieldset>
  <fieldset class="set_oauth"><legend>{_ld($_module,'oauth_legend')}</legend>
  <div class="pageoverflow">
    {$t=$title_provider}<label class="pagetext" for="oprovider">{$t}:</label>
    {cms_help realm=$_module key='info_provider' title=$t}
    <div class="pageinput">
      <select id="oprovider" name="{$actionid}oauthprovider">
      {html_options options=$opts_provider selected=$value_provider}      </select>
    </div>
    <label class="pagetext" for="cprovider">{$title_otherprovider}:</label><br>
    <div class="pageinput">
      <input type="text" id="cprovider" name="{$actionid}ouathprovider2" value="{$value_otherprovider}" size="24" maxlength="64">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_oclient}<label class="pagetext" for="oclient">{$t}:</label>
    {cms_help realm=$_module key='info_client' title=$t}
    <div class="pageinput">
      <input type="text" id="oclient" name="{$actionid}ouathclient" value="{$value_oclient}" size="40" maxlength="64">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_osecret}<label class="pagetext" for="osecret">{$t}:</label>
    {cms_help realm=$_module key='info_secret' title=$t}
    <div class="pageinput">
      <input type="text" id="osecret" class="cloaked" name="{$actionid}ouathsecret" value="{$value_osecret}" size="64" maxlength="128">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_oemail}<label class="pagetext" for="oemail">{$t}:</label>
    {cms_help realm=$_module key='info_email' title=$t}
    <div class="pageinput">
      <input type="text" id="oemail" name="{$actionid}ouathemail" value="{$value_oemail}" size="40" maxlength="64">
    </div>
  </div>
  </fieldset>
  </fieldset>
  <fieldset><legend>{_ld($_module,'specific_legend')}</legend>
  <input type="hidden" name="{$actionid}single" value="0">
  <div class="pageoverflow">
    {$t=$title_single}<label class="pagetext" for="single0">{$t}:</label>
    {cms_help realm=$_module key='info_single' title=$t}
    <div class="pageinput">
      {foreach $opts_single as $i=>$one}
      <input type="radio" name="{$actionid}single" id="single{$i}" value="{$one.value}"{if !empty($one.checked)} checked{/if}>
      <label for="single{$i}">{$one.label}</label>{if !$one@last}<br>{/if}
{/foreach}
    </div>
  </div>
{*
  <div class="pageoverflow">
    {$t=$title_batchsize}<label class="pagetext" for="batchsize">{$t}:</label>
    {cms_help realm=$_module key='info_batchsize' title=$t}
    <div class="pageinput">
      <input type="text" id="batchsize" name="{$actionid}batchsize" value="{$value_batchsize}" size="5" maxlength="8">
    </div>
  </div>
  <div class="pageoverflow">
    {$t=$title_batchgap}<label class="pagetext" for="batchgap">{$t}:</label>
    {cms_help realm=$_module key='info_batchgap' title=$t}
    <div class="pageinput">
      <select id="batchgap" name="{$actionid}batchgap">
        {html_options options=$opts_batchgap selected=$value_batchgap}      </select>
    </div>
  </div>
  <fieldset class="set_smtp"><legend >{_ld($_module,'smtp_legend')}</legend>
    MODULE-SPECIFIC PROPS HERE
  </fieldset>
  <fieldset class="set_sendmail"><legend>{_ld($_module,'sendmail_legend')}</legend>
    MODULE-SPECIFIC PROPS HERE
  </fieldset>
*}
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_ld($_module,'apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </div>
</form>
{if $pgates}
{tab_start name='gates'}
{$startform}
 {if $gatesdata}
 {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}">
{/foreach}
{* js hides all except current
  {if count($gatesdata) > 2}
   <div class="pageinput postgap">
   <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_ld($_module,'apply')}</button>
   <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
   </div>
  {/if}
*}
  {$t=_ld($_module,'default_platform')}<label class="pagetext" for="platform">{$t}:</label>
  {cms_help realm=$_module key='info_platform' title=$t}
  <div class="pageinput">
    <select id="platform" name="{$actionid}platform">
      {html_options options=$gatesnames selected=$current}    </select>
  </div>
  <p class="pagewarn pregap">{_ld($_module,'info_sure')}</p>
  <p class="pageinfo">{_ld($_module,'info_dnd')}</p>
  {foreach $gatesdata as $alias => $one}
   <div id="{$alias}" class="platform_panel" style="margin:0.5em 0">
   {$one}
   </div>
  {/foreach}
  <div class="pageoptions">{$t=_ld($_module,'add_gate')}
  <a href="{$addurl}" title="{_ld($_module,'info_addgate')}">{admin_icon icon='newobject.gif' class='systemicon' alt=$t}&nbsp;&nbsp;{$t}</a>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_ld($_module,'apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </div>
 {else}
  <p class="pageinfo">{_ld($_module,'no_platforms')}</p>
  <div class="pageoptions">{$t=_ld($_module,'add_gate')}
  <a href="{$addurl}" title="{_ld($_module,'info_addgate')}">{admin_icon icon='newobject.gif' class='systemicon alt=$t'}&nbsp;&nbsp;{$t}</a>
  </div>
 {/if}
</form>
{/if}{* $pgates *}
{tab_start name='test'}
{$startform}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}">
{/foreach}
  <div class="pageoverflow">
    {$t=$title_testaddress}<label class="pagetext" for="testaddress">{$t}:</label>
    {cms_help realm=$_module key='info_testaddress' title=$t}
    <div class="pageinput">
      <input type="text" id="testaddress" name="{$actionid}testaddress" value="" size="40" maxlength="255">
      <div class="pregap">
        <button type="submit" name="{$actionid}sendtest" class="adminsubmit icon do">{_ld($_module,'sendtest')}</button>
      </div>
      <p class="pageinfo">{_ld($_module,'info_outmailer2')}</p>
    </div>
  </div>
</form>
{if $pmod}
{tab_start name='settings'}
{$startform}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}">
{/foreach}
  <div class="pageoverflow">
    {$t=$title_modpassword}<label class="pagetext" for="modpassword">{$t}:</label>
    {cms_help realm=$_module key='info_modpassword' title=$t}
    <br>
    <textarea id="modpassword" name="{$actionid}masterpass" class="cloaked" rows="2" cols="40">{$value_modpassword}</textarea>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{_ld($_module,'apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{_ld($_module,'cancel')}</button>
  </div>
</form>
{/if}{* $pmod *}
{tab_end}
