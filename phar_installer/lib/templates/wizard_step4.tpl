{* wizard step 4 *}

{extends file='wizard_step.tpl'}
{block name='logic'}
  {$subtitle = 'title_step4'|tr}
  {$current_step = '4'}
{/block}

{strip}
{function warn_info}
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

  <h3>{'prompt_dbinfo'|tr}</h3>
  <p class="info">{'info_dbinfo'|tr}</p>
  {if isset($dowarn)}
  <div class="message yellow" style="margin-top:0.5em;">
   {if $loosepass}{warn_info arg=$loosepass}{if $looseperms}<br />{/if}{/if}
   {if $looseperms}{warn_info arg=$looseperms}{/if}
  </div>
  <input type="hidden" name="warndone" value="1" />
  {/if}
  <fieldset>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label for="host">{'prompt_dbhost'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field required full-width" type="text" id="host" name="db_hostname" value="{$config.db_hostname}" required="required" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label for="name">{'prompt_dbname'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field required full-width" type="text" id="name" name="db_name" value="{$config.db_name}" required="required" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label for="user">{'prompt_dbuser'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field required full-width" type="text" id="user" name="db_username" value="{$config.db_username}" required="required" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label for="pass">{'prompt_dbpass'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field required full-width" type="password" id="pass" name="db_password" value="{$config.db_password}" required="required" autocomplete="off" />
        <div class="corner red">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
{*    {if $verbose} *}
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label{if !$verbose} class="disabled"{/if} for="port">{'prompt_dbport'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field full-width{if !$verbose} disabled{/if}" type="text" id="port" name="db_port" value="{$config.db_port}"{if !$verbose} disabled="disabled"{/if} />
      </div>
    </div>
    <div class="flexrow form-row">
      <div class="cell cols_4">
        <label{if !$verbose} class="disabled"{/if} for="prefix">{'prompt_dbprefix'|tr}</label>
      </div>
      <div class="cell cols_8">
        <input class="form-field full-width{if !$verbose} disabled{/if}" type="text" id="prefix" name="db_prefix" value="{$config.db_prefix}"{if !$verbose} disabled="disabled"{/if} />
      </div>
    </div>
{*    {else}
     <input type="hidden" name="db_port" value="{$config.db_port}" />
     <input type="hidden" name="db_prefix" value="{$config.db_prefix}" />
    {/if}
*}
  </fieldset>

  <h3>{'prompt_timezone'|tr}</h3>
  <p>{'info_timezone'|tr}</p>
  <div class="row">
    <select id="zone" class="form-field" name="timezone" required="required">
      {html_options options=$timezones selected=$config.timezone}
    </select>
  </div>

  <h3{if !$verbose} class="disabled"{/if}>{'prompt_queryvar'|tr}</h3>
  {if $verbose}<p>{'info_queryvar'|tr}</p>{/if}
  <div class="row">
    <input class="form-field quarter-width{if !$verbose} disabled{/if}" type="text" id="qvar" name="query_var" value="{$config.query_var}"{if !$verbose} disabled="disabled"{/if} />
  </div>

  <h3{if !$verbose} class="disabled"{/if}>{'prompt_adminpath'|tr}</h3>
  {if $verbose}<p>{'info_adminpath'|tr}</p>{/if}
  <div class="row">
    <input class="form-field half-width{if !$verbose} disabled{/if}" type="text" id="adminp" name="admin_path" value="{$config.admin_path}"{if !$verbose} disabled="disabled"{/if} />
  </div>

  <h3{if !$verbose} class="disabled"{/if}>{'prompt_assetspath'|tr}</h3>
  {if $verbose}<p>{'info_assetspath'|tr}</p>{/if}
  <div class="row">
    <input class="form-field half-width{if !$verbose} disabled{/if}" type="text" id="assetp" name="assets_path" value="{$config.assets_path}"{if !$verbose} disabled="disabled"{/if} />
  </div>

  <h3{if !$verbose} class="disabled"{/if}>{'prompt_plugspath'|tr}</h3>
  {if $verbose}<p>{'info_plugspath'|tr}</p>{/if}
  <div class="row">
    <input class="form-field half-width{if !$verbose} disabled{/if}" type="text" id="udtp" name="userplugins_path" value="{$config.userplugins_path}"{if !$verbose} disabled="disabled"{/if} />
  </div>

  <div id="bottom_nav">
    <button class="action-button positive" type="submit" name="next"><i class='icon-cog'></i> {'next'|tr}</button>
  </div>

{wizard_form_end}
</div>
{/block}
