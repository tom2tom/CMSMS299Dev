{* wizard step 6 *}

{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = 'title_step6'|tr}
  {$current_step = '6'}
{/block}

{block name='contents'}

<div class="installer-form">
{wizard_form_start}
  <p>{'info_adminaccount'|tr}</p>

  <fieldset>
    <div class="row form-row">
      <div class="four-col">
        <label for="name">{'username'|tr}</label>
      </div>
      <div class="eight-col">
        <input class="form-field required full-width" type="text" id="name" name="username" value="{$account.username}" required="required" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row form-row">
      <div class="four-col">
        <label for="pass">{'password'|tr}</label>
      </div>
      <div class="eight-col">
        <input class="form-field required full-width" type="password" id="pass" name="password" value="{$account.password}" required="required" autocomplete="off" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row form-row">
      <div class="four-col">
        <label for="again">{'repeatpw'|tr}</label>
      </div>
      <div class="eight-col">
        <input class="form-field required full-width" type="password" id="again" name="repeatpw" value="{$account.password}" required="required" autocomplete="off" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row form-row">
      <div class="four-col">
        <label for="email">{'emailaddr'|tr}</label>
      </div>
      <div class="eight-col">
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
