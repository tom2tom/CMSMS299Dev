{* wizard step 4 *}
{extends file='wizard_step.tpl'}

{block name='logic'}
  {$subtitle = tr('title_step4')}
  {$current_step = '4'}
{/block}

{strip}
{function warn_info}
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
{if $action == 'install'}{*TODO OR != 'freshen' i.e. upgrade too*}
  <h3>{tr('prompt_dbinfo')}</h3>
  <p class="info">{tr('info_dbinfo')}</p>
  {if isset($dowarn)}
  <div class="message yellow" style="margin-top:0.5em">
   {if $loosepass}{warn_info arg=$loosepass}{if $looseperms}<br>{/if}{/if}
   {if $looseperms}{warn_info arg=$looseperms}{/if}
  </div>
  <input type="hidden" name="warndone" value="1">
  {/if}
  <fieldset>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="host">{tr('prompt_dbhost')}</label>
      </div>
      <div class="cell col-8 must">
        <input type="text" class="form-field full-width max20 mustchild" id="host" name="db_hostname" value="{$config.db_hostname}" required>
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="name">{tr('prompt_dbname')}</label>
      </div>
      <div class="cell col-8 must">
        <input type="text" class="form-field full-width max20 mustchild" id="name" name="db_name" value="{$config.db_name}" required>
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="user">{tr('prompt_dbuser')}</label>
      </div>
      <div class="cell col-8 must">
        <input type="text" class="form-field full-width max20 mustchild" id="user" name="db_username" value="{$config.db_username}" required>
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label for="pass">{tr('prompt_dbpass')}</label>
      </div>
      <div class="cell col-8 must">
        <input type="password" class="form-field full-width max40 mustchild" id="pass" name="db_password" value="{$config.db_password}" required autocomplete="off">
        <div class="corner red mustchild">
          <i class="icon-asterisk"></i>
        </div>
      </div>
    </div>
{*    {if $verbose} *}
    <div class="row btm-margin">
      <div class="cell col-4">
        <label{if !$verbose} class="disabled"{/if} for="port">{tr('prompt_dbport')}</label>
      </div>
      <div class="cell col-8">
        <input type="text" class="form-field half-width max20{if !$verbose} disabled{/if}" id="port" name="db_port" value="{$config.db_port}"{if !$verbose} disabled{/if}>
      </div>
    </div>
    <div class="row btm-margin">
      <div class="cell col-4">
        <label{if !$verbose} class="disabled"{/if} for="prefix">{tr('prompt_dbprefix')}</label>
      </div>
      <div class="cell col-8">
        <input type="text" class="form-field half-width max20{if !$verbose} disabled{/if}" id="prefix" name="db_prefix" value="{$config.db_prefix}"{if !$verbose} disabled{/if}>
      </div>
    </div>
{*    {else}
     <input type="hidden" name="db_port" value="{$config.db_port}">
     <input type="hidden" name="db_prefix" value="{$config.db_prefix}">
    {/if}
*}
  </fieldset>
{/if}
  <h3>{tr('prompt_timezone')}</h3>
  <p>{tr('info_timezone')}</p>
  <div class="page-row">
    <select id="zone" class="form-field" name="timezone" required>
      {html_options options=$timezones selected=$config.timezone}
    </select>
  </div>
{if $action == 'install'}{*TODO OR != 'freshen' i.e. upgrade too*}
  <h3{if !$verbose} class="disabled"{/if}>{tr('prompt_queryvar')}</h3>
  {if $verbose}<p>{tr('info_queryvar')}</p>{/if}
  <div class="page-row">
    <input type="text" class="form-field half-width max20{if !$verbose} disabled{/if}" id="qvar" name="query_var" value="{$config.query_var}"{if !$verbose} disabled{/if}>
  </div>

  <h3{if !$verbose} class="disabled"{/if}>{tr('prompt_adminpath')}</h3>
  {if $verbose}<p>{tr('info_adminpath')}</p>{/if}
  <div class="page-row">
    <input type="text" class="form-field full-width max40{if !$verbose} disabled{/if}" id="adminp" name="admin_path" value="{$config.admin_path}"{if !$verbose} disabled{/if}>
  </div>

  <h3{if !$verbose} class="disabled"{/if}>{tr('prompt_assetspath')}</h3>
  {if $verbose}<p>{tr('info_assetspath')}</p>{/if}
  <div class="page-row">
    <input type="text" class="form-field full-width max40{if !$verbose} disabled{/if}" id="assetp" name="assets_path" value="{$config.assets_path}"{if !$verbose} disabled{/if}>
  </div>

  <h3{if !$verbose} class="disabled"{/if}>{tr('prompt_plugspath')}</h3>
  {if $verbose}<p>{tr('info_plugspath')}</p>{/if}
  <div class="page-row">
    <input type="text" class="form-field full-width max40{if !$verbose} disabled{/if}" id="udtp" name="userplugins_path" value="{$config.userplugins_path}"{if !$verbose} disabled{/if}>
  </div>
{/if}
  {if empty($error)}
  <div id="bottom_nav">
    <button type="submit" class="action-button positive" name="next">{if empty($lang_rtl)}<i class="icon-next-right"></i> {tr('next')}{else}{tr('next')} <i class="icon-next-left"></i>{/if}</button>
  </div>
{*  {else}<a href="{$retry_url}" class="action-button negative" title="{tr('retry')}">{if !empty($lang_rtl)}<i class="icon-refresh"></i> {tr('retry')}{else}{tr('retry')} <i class="icon-refresh"></i>{/if}</a>*}
  {/if}
 </form>
</div>
{/block}
