{if isset($message)}<p>{$message}</p>{/if}
{tab_header name='settings' label=$mod->Lang('settings') active=$tab}
{tab_header name='test' label=$mod->Lang('test') active=$tab}
{tab_start name='settings'}
<p class="pageinfo">{$mod->Lang('info_cmsmailer1')}</p>
{$startform}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}" />
{/foreach}
  <div class="pageinput postgap">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </div>
  <fieldset><legend>{$mod->Lang('core')}</legend>
  <div class="pageoverflow">{$t=$title_mailer}
    <label class="pagetext" for="mailer">{$t}:</label>
    {cms_help realm=$_module key2=$help_mailer title=$t}
    <div class="pageinput">
      <select id="mailer" name="{$actionid}mailer">
      {html_options options=$opts_mailer selected=$value_mailer}
      </select>
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_from}
    <label class="pagetext" for="from">{$t}:</label>
    {cms_help realm=$_module key2=$help_from title=$t}
    <div class="pageinput">
      <input type="text" id="from" name="{$actionid}from" value="{$value_from}" size="30" maxlength="80" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_fromuser}
    <label class="pagetext" for="fromuser">{$t}:</label>
    {cms_help realm=$_module key2=$help_fromuser title=$t}
    <div class="pageinput">
      <input type="text" id="fromuser" name="{$actionid}fromuser" value="{$value_fromuser}" size="30" maxlength="80" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_charset}
    <label class="pagetext" for="charset">{$t}:</label>
    {cms_help realm=$_module key2=$help_charset title=$t}
    <div class="pageinput">
      <input type="text" id="charset" name="{$actionid}charset" value="{$value_charset}" size="10" maxlength="20" />
    </div>
  </div>
  <fieldset class="set_smtp"><legend >{$mod->Lang('smtp_legend')}</legend>
  <div class="pageoverflow">{$t=$title_secure}
    <label class="pagetext" for="secure">{$t}:</label>
    {cms_help realm=$_module key2=$help_secure title=$t}
    <div class="pageinput">
      <select id="secure" name="{$actionid}secure">
      {html_options options=$opts_secure selected=$value_secure}
      </select>
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_host}
    <label class="pagetext" for="host">{$t}:</label>
    {cms_help realm=$_module key2=$help_host title=$t}
    <div class="pageinput">
      <input type="text" id="host" name="{$actionid}host" value="{$value_host}" size="50" maxlength="80" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_port}
    <label class="pagetext" for="port">{$t}:</label>
    {cms_help realm=$_module key2=$help_port title=$t}
    <div class="pageinput">
      <input type="text" id="port" name="{$actionid}port" value="{$value_port}" size="5" maxlength="5" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_timeout}
    <label class="pagetext" for="timeout">{$t}:</label>
    {cms_help realm=$_module key2=$help_timeout title=$t}
    <div class="pageinput">
      <input type="text" id="timeout" name="{$actionid}timeout" value="{$value_timeout}" size="3" maxlength="5" />
    </div>
  </div>
  <input type="hidden" name="{$actionid}smtpauth" value="0" />
  <div class="pageoverflow">{$t=$title_smtpauth}
    <label class="pagetext" for="">{$t}:</label>
    {cms_help realm=$_module key2=$help_smtpauth title=$t}
    <div class="pageinput">
      <input type="checkbox" id="smtpauth" name="{$actionid}" value="1"{if $value_smtpauth} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_username}
    <label class="pagetext" for="username">{$t}:</label>
    {cms_help realm=$_module key2=$help_username title=$t}
    <div class="pageinput">
      <input type="text" id="username" name="{$actionid}username" value="{$value_username}" size="30" maxlength="64" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_password}
    <label class="pagetext" for="password">{$t}:</label>
    {cms_help realm=$_module key2=$help_password title=$t}
    <div class="pageinput">
      <input type="text" id="password" name="{$actionid}password" value="{$value_password}" size="30" maxlength="64" />
    </div>
  </div>
  </fieldset>
  <fieldset class="set_sendmail"><legend>{$mod->Lang('sendmail_legend')}</legend>
  <div class="pageoverflow">{$t=$title_sendmail}
    <label class="pagetext" for="sendmail">{$t}:</label>
    {cms_help realm=$_module key2=$help_sendmail title=$t}
    <div class="pageinput">
      <input type="text" id="sendmail" name="{$actionid}sendmail" value="{$value_sendmail}" size="50" maxlength="255" />
    </div>
  </div>
  </fieldset>
  </fieldset>
  <fieldset><legend>{$mod->Lang('module')}</legend>
  <fieldset class="set_smtp"><legend >{$mod->Lang('smtp_legend')}</legend>
    MODULE-SPECIFIC PROPS HERE
  </fieldset>
  <fieldset class="set_sendmail"><legend>{$mod->Lang('sendmail_legend')}</legend>
    MODULE-SPECIFIC PROPS HERE
  </fieldset>
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}submit" class="adminsubmit icon check">{$mod->Lang('submit')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </div>
</form>
{tab_start name='test'}
<p class="pageinfo">{$mod->Lang('info_cmsmailer2')}</p>
{$startform}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}" />
{/foreach}
  <div class="pageoverflow">{$t=$title_testaddress}
    <label class="pagetext" for="testaddress">{$t}:</label>
    {cms_help realm=$_module key2=$help_testaddress title=$t}
    <div class="pageinput">
      <input type="text" id="testaddress" name="{$actionid}testaddress" value="" size="40" maxlength="255" />
      <div class="pregap">
        <button type="submit" name="{$actionid}sendtest" class="adminsubmit icon do">{$mod->Lang('sendtest')}</button>
      </div>
    </div>
  </div>
</form>
{tab_end}
