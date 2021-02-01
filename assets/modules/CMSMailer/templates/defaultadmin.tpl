{if isset($message)}<p>{$message}</p>{/if}
{tab_header name='internal' label=$mod->Lang('internal') active=$tab}
{if $pgates}
{tab_header name='gates' label=$mod->Lang('external') active=$tab}
{/if}
{tab_header name='test' label=$mod->Lang('test') active=$tab}
{if $pmod}
{tab_header name='settings' label=$mod->Lang('module') active=$tab}
{/if}
{tab_start name='internal'}
<p class="pageinfo">{$mod->Lang('info_cmsmailer1')}</p>
{$startform}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}" />
{/foreach}
  <div class="pageinput postgap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </div>
  <fieldset><legend>{$mod->Lang('core')}</legend>
  <div class="pageoverflow">{$t=$title_mailer}
    <label class="pagetext" for="mailer">{$t}:</label>
    {cms_help realm=$_module key2='info_mailer' title=$t}
    <div class="pageinput">
      <select id="mailer" name="{$actionid}mailer">
      {html_options options=$opts_mailer selected=$value_mailer}
      </select>
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_from}
    <label class="pagetext" for="from">{$t}:</label>
    {cms_help realm=$_module key2='info_from' title=$t}
    <div class="pageinput">
      <input type="text" id="from" name="{$actionid}from" value="{$value_from}" size="30" maxlength="80" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_fromuser}
    <label class="pagetext" for="fromuser">{$t}:</label>
    {cms_help realm=$_module key2='info_fromuser' title=$t}
    <div class="pageinput">
      <input type="text" id="fromuser" name="{$actionid}fromuser" value="{$value_fromuser}" size="30" maxlength="80" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_charset}
    <label class="pagetext" for="charset">{$t}:</label>
    {cms_help realm=$_module key2='info_charset' title=$t}
    <div class="pageinput">
      <input type="text" id="charset" name="{$actionid}charset" value="{$value_charset}" size="10" maxlength="20" />
    </div>
  </div>
  <fieldset class="set_smtp"><legend >{$mod->Lang('smtp_legend')}</legend>
  <div class="pageoverflow">{$t=$title_secure}
    <label class="pagetext" for="secure">{$t}:</label>
    {cms_help realm=$_module key2='info_secure' title=$t}
    <div class="pageinput">
      <select id="secure" name="{$actionid}secure">
      {html_options options=$opts_secure selected=$value_secure}
      </select>
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_host}
    <label class="pagetext" for="host">{$t}:</label>
    {cms_help realm=$_module key2='info_host' title=$t}
    <div class="pageinput">
      <input type="text" id="host" name="{$actionid}host" value="{$value_host}" size="50" maxlength="80" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_port}
    <label class="pagetext" for="port">{$t}:</label>
    {cms_help realm=$_module key2='info_port' title=$t}
    <div class="pageinput">
      <input type="text" id="port" name="{$actionid}port" value="{$value_port}" size="5" maxlength="5" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_timeout}
    <label class="pagetext" for="timeout">{$t}:</label>
    {cms_help realm=$_module key2='info_timeout' title=$t}
    <div class="pageinput">
      <input type="text" id="timeout" name="{$actionid}timeout" value="{$value_timeout}" size="3" maxlength="5" />
    </div>
  </div>
  <input type="hidden" name="{$actionid}smtpauth" value="0" />
  <div class="pageoverflow">{$t=$title_smtpauth}
    <label class="pagetext" for="smtpauth">{$t}:</label>
    {cms_help realm=$_module key2='info_smtpauth' title=$t}
    <div class="pageinput">
      <input type="checkbox" id="smtpauth" name="{$actionid}smtpauth" value="1"{if $value_smtpauth} checked="checked"{/if} />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_username}
    <label class="pagetext" for="username">{$t}:</label>
    {cms_help realm=$_module key2='info_username' title=$t}
    <div class="pageinput">
      <input type="text" id="username" name="{$actionid}username" value="{$value_username}" size="30" maxlength="64" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_password}
    <label class="pagetext" for="password">{$t}:</label>
    {cms_help realm=$_module key2='info_password' title=$t}
    <div class="pageinput">
      <input type="text" id="password" class="cloaked" name="{$actionid}password" value="{$value_password}" size="30" maxlength="64" />
    </div>
  </div>
  </fieldset>
  <fieldset class="set_sendmail"><legend>{$mod->Lang('sendmail_legend')}</legend>
  <div class="pageoverflow">{$t=$title_sendmail}
    <label class="pagetext" for="sendmail">{$t}:</label>
    {cms_help realm=$_module key2='info_sendmail' title=$t}
    <div class="pageinput">
      <input type="text" id="sendmail" name="{$actionid}sendmail" value="{$value_sendmail}" size="50" maxlength="255" />
    </div>
  </div>
  </fieldset>
  </fieldset>
  <fieldset><legend>{$mod->Lang('specific_legend')}</legend>
  <input type="hidden" name="{$actionid}single" value="0" />
  <div class="pageoverflow">{$t=$title_single}
    <label class="pagetext" for="single0">{$t}:</label>
    {cms_help realm=$_module key2='info_single' title=$t}
    <div class="pageinput">
      {foreach $opts_single as $i=>$one}
      <input type="radio" name="{$actionid}single" id="single{$i}" value="{$one.value}"{if !empty($one.checked)} checked="checked"{/if}>
      <label for="single{$i}">{$one.label}</label>{if !$one@last}<br />{/if}
{/foreach}
    </div>
  </div>
{*
  <div class="pageoverflow">{$t=$title_batchsize}
    <label class="pagetext" for="batchsize">{$t}:</label>
    {cms_help realm=$_module key2='info_batchsize' title=$t}
    <div class="pageinput">
      <input type="text" id="batchsize" name="{$actionid}batchsize" value="{$value_batchsize}" size="5" maxlength="8" />
    </div>
  </div>
  <div class="pageoverflow">{$t=$title_batchgap}
    <label class="pagetext" for="batchgap">{$t}:</label>
    {cms_help realm=$_module key2='info_batchgap' title=$t}
    <div class="pageinput">
      <select id="batchgap" name="{$actionid}batchgap">
      {html_options options=$opts_batchgap selected=$value_batchgap}
      </select>
    </div>
  </div>
  <fieldset class="set_smtp"><legend >{$mod->Lang('smtp_legend')}</legend>
    MODULE-SPECIFIC PROPS HERE
  </fieldset>
  <fieldset class="set_sendmail"><legend>{$mod->Lang('sendmail_legend')}</legend>
    MODULE-SPECIFIC PROPS HERE
  </fieldset>
*}
  </fieldset>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </div>
</form>
{if $pgates}
{tab_start name='gates'}
{$startform}
 {if $gatesdata}
 {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}" />
{/foreach}
{* js hides all except current
  {if count($gatesdata) > 2}
   <div class="pageinput postgap">
   <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
   <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
   </div>
  {/if}
*}
  {$t=$mod->Lang('default_gateway')}
  <label class="pagetext" for="currentgate">{$t}:</label>
  {cms_help realm=$_module key2='info_currentgate' title=$t}
  <div class="pageinput">
    <select id="currentgate" name="{$actionid}currentgate">
    {html_options options=$gatesnames selected=$gatecurrent}
    </select>
  </div>
  <p class="pagewarn pregap">{$mod->Lang('info_sure')}</p>
  <p class="pageinfo">{$mod->Lang('info_dnd')}</p>
  {foreach $gatesdata as $alias => $one}
   <div id="{$alias}" class="gateway_panel" style="margin:0.5em 0;">
   {$one}
   </div>
  {/foreach}
  <div class="pageoptions">{$t=$mod->Lang('add_gate')}
  <a href="{$addurl}" title="{$mod->Lang('info_addgate')}">{admin_icon icon='newobject.gif' class='systemicon' alt=$t}&nbsp;&nbsp;{$t}</a>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </div>
 {else}
  <p class="pageinfo">{$mod->Lang('no_gates')}</p>
  <div class="pageoptions">{$t=$mod->Lang('add_gate')}
  <a href="{$addurl}" title="{$mod->Lang('info_addgate')}">{admin_icon icon='newobject.gif' class='systemicon alt=$t'}&nbsp;&nbsp;{$t}</a>
  </div>
 {/if}
</form>
{/if}{* $pgates *}
{tab_start name='test'}
{$startform}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}" />
{/foreach}
  <div class="pageoverflow">{$t=$title_testaddress}
    <label class="pagetext" for="testaddress">{$t}:</label>
    {cms_help realm=$_module key2='info_testaddress' title=$t}
    <div class="pageinput">
      <input type="text" id="testaddress" name="{$actionid}testaddress" value="" size="40" maxlength="255" />
      <div class="pregap">
        <button type="submit" name="{$actionid}sendtest" class="adminsubmit icon do">{$mod->Lang('sendtest')}</button>
      </div>
      <p class="pageinfo">{$mod->Lang('info_cmsmailer2')}</p>
    </div>
  </div>
</form>
{if $pmod}
{tab_start name='settings'}
{$startform}
  {foreach $extraparms as $key => $val}<input type="hidden" name="{$actionid}{$key}" value="{$val}" />
{/foreach}
  <div class="pageoverflow">{$t=$title_modpassword}
    <label class="pagetext" for="modpassword">{$t}:</label>
    {cms_help realm=$_module key2='info_modpassword' title=$t}
    <br />
    <textarea id="modpassword" name="{$actionid}masterpass" class="cloaked" rows="2" cols="40">{$value_modpassword}</textarea>
  </div>
  <div class="pageinput pregap">
    <button type="submit" name="{$actionid}apply" class="adminsubmit icon apply">{$mod->Lang('apply')}</button>
    <button type="submit" name="{$actionid}cancel" class="adminsubmit icon cancel">{$mod->Lang('cancel')}</button>
  </div>
</form>
{/if}{* $pmod *}
{tab_end}
