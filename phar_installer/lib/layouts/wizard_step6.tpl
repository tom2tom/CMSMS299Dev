{* wizard step 6 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = tr('title_step6')}
  {$current_step = '6'}
{/block}

{strip}
{function err_info}
{if count($arg) > 2}
 {$arg.0}<br>
 <ul>{foreach $arg as $msg}{if !$msg@first}
 <li>{$msg}</li>
 {/if}{/foreach}</ul>
{elseif count($arg) > 1}
 {$arg.0}<br>
  &nbsp;{$arg.1}
{elseif count($arg) > 0}
 {$arg.0}
{/if}
{/function}
{/strip}

{block name='contents'}
<div class="installer-form">
 {wizard_form_start}
  <p>{tr('info_adminaccount')}</p>
  {if isset($doerr)}
  <input type="hidden" name="warndone" value="1">
  <div class="message red" style="margin-top:0.5em">
   {if $tellname}{err_info arg=$tellname}{if $tellpass}<br>{/if}{/if}
   {if $tellpass}{err_info arg=$tellpass}{/if}
  </div>
  {/if}
  <fieldset>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="name">{tr('username')}</label>
      </div>
      <div class="cell col-8 must">
        <input type="text" class="form-field full-width max20 mustchild" id="name" name="username" value="{$account.username}" required>
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="pass">{tr('password')}</label>
      </div>
      <div class="cell col-8 must">
        <input type="password" class="form-field full-width max40 mustchild" id="pass" name="password" value="{$account.password}" required autocomplete="off">
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="again">{tr('repeatpw')}</label>
      </div>
      <div class="cell col-8 must">
        <input type="password" class="form-field full-width max40 mustchild" id="again" name="repeatpw" value="{$account.password}" required autocomplete="off">
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="email">{tr('emailaddr')}</label>
      </div>
      <div class="cell col-8{* must*}">
{*      {if $verbose} *}
        <input type="email" class="form-field full-width max40" id="email" name="emailaddr" value="{$account.emailaddr}">
{*      {else}
        <input type="email" class="form-field full-width max40 mustchild" id="email" name="emailaddr" value="{$account.emailaddr}" required>
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      {/if} *}
      </div>
    </div>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="sendmail">{tr('emailaccountinfo')}</label>
      </div>
      <div class="cell col-2">
        <input type="checkbox" id="sendmail" name="emailsend" value="1"{if $account.emailsend} checked{/if}>
      </div>
    </div>
    <div class="message yellow">{tr('warn_email')}</div>
  </fieldset>
  <div id="bottom_nav">
    <button type="submit" class="action-button positive" name="next">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('next')}{else}{tr('next')} <i class="icon-next-left"></i>{/if}</button>
  </div>
{*  {else}<a href="{$retry_url}" class="action-button negative" title="{tr('retry')}">{if !empty($lang_rtl)}<i class="icon-refresh"></i> {tr('retry')}{else}{tr('retry')} <i class="icon-refresh"></i>{/if}</a>
  {/if*}
 </form>
</div>
{/block}
