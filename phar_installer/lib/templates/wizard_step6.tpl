{* wizard step 6 *}

{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step6'|tr}
  {$current_step = '6'}
{/block}

{strip}
{function err_info}
{if count($arg) > 2}
 {$arg.0}<br/>
 <ul>{foreach $arg as $msg}{if !$msg@first}
 <li>{$msg}</li>
 {/if}{/foreach}</ul>
{elseif count($arg) > 1}
 {$arg.0}<br />
  &nbsp;{$arg.1}
{elseif count($arg) > 0}
 {$arg.0}
{/if}
{/function}
{/strip}

{block name='contents'}

<div class="installer-form">
{wizard_form_start}
  <p>{'info_adminaccount'|tr}</p>
  {if isset($doerr)}
  <input type="hidden" name="warndone" value="1" />
  <div class="message red" style="margin-top:0.5em;">
   {if $tellname}{err_info arg=$tellname}{if $tellpass}<br />{/if}{/if}
   {if $tellpass}{err_info arg=$tellpass}{/if}
  </div>
  {/if}
  <fieldset>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label for="name">{'username'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field required full-width" type="text" id="name" name="username" value="{$account.username}" required="required" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label for="pass">{'password'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field required full-width" type="password" id="pass" name="password" value="{$account.password}" required="required" autocomplete="off" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label for="again">{'repeatpw'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field required full-width" type="password" id="again" name="repeatpw" value="{$account.password}" required="required" autocomplete="off" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label for="email">{'emailaddr'|tr}</label>
      </div>
      <div class="cell cols_8">
{*      {if $verbose} *}
        <input class="form-field full-width" type="email" id="email" name="emailaddr" value="{$account.emailaddr}" />
{*      {else}
        <input class="form-field required full-width" type="email" id="email" name="emailaddr" value="{$account.emailaddr}" required="required" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      {/if} *}
      </div>
    </div>

  <div id="bottom_nav">
    <button class="action-button positive" type="submit" name="next"><i class='icon-cog'></i> {'next'|tr}</button>
  </div>

{wizard_form_end}
</div>

{/block}
