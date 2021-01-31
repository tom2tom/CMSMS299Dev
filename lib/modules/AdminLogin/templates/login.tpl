<!doctype html>
<html lang="{$lang_code}" dir="{$lang_dir}">
 <head>
  <title>{$mod->Lang('loginto',{sitename})}</title>
  <base href="{$admin_url}/" />
  <meta charset="{$encoding}" />
  <meta name="generator" content="CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0" />
  <meta name="HandheldFriendly" content="true" />
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
  {$header_includes|default:''}
 </head>
 <body>
  <div id="login-container">
   <div id="login-box"{if !empty($error)} class="error"{/if}>
    <div id="logo">
     <a href="https://www.cmsmadesimple.org"><img src="themes/assets/images/cmsms-logotext-dark.svg" alt="CMS Made Simple&trade;" /></a>
    </div>
    {if empty($smarty.get.forgotpw)}
    <a id="show-info" href="javascript:void()" title="{$mod->Lang('open')}"></a>
    <div id="maininfo">
      <a id="hide-info" href="javascript:void()" title="{$mod->Lang('close')}"></a>
      <h2>{$mod->Lang('login_info_title')}</h2>
      <p>{$mod->Lang('login_info')}</p>
      {$mod->Lang('login_info_params')}
      <p><strong>({$smarty.server.HTTP_HOST})</strong></p>
      <p class="warning">{$mod->Lang('login_info_ipandcookies')}</p>
    </div>
    {/if}
    <h1>{$mod->Lang('login_sitetitle',{sitename})}</h1>
    {$start_form}
     <fieldset>
      <label for="lbusername">{$mod->Lang('username')}</label>
      <input id="lbusername" class="focus" placeholder="{$mod->Lang('username')}" name="{$actionid}username" type="text" size="15" value="" autofocus="autofocus" />
      {if empty($smarty.get.forgotpw) }
       <label for="lbpassword">{$mod->Lang('password')}</label>
       <input id="lbpassword" class="focus" placeholder="{$mod->Lang('password')}" name="{$actionid}password" type="password" size="15" maxlength="64" />
      {/if}
      {if !empty($changepwhash)}
       <label for="lbpasswordagain">{$mod->Lang('passwordagain')}</label>
       <input id="lbpasswordagain" name="{$actionid}passwordagain" type="password" size="15" placeholder="{$mod->Lang('passwordagain')}" maxlength="64" />
       <input type="hidden" name="{$actionid}forgotpwchangeform" value="1" />
       <input type="hidden" name="{$actionid}changepwhash" value="{$changepwhash}" />
      {elseif !empty($smarty.get.forgotpw)}
       <input type="hidden" name="{$actionid}forgotpwform" value="1" />
      {/if}
     </fieldset>
    {if !empty($smarty.get.forgotpw)}
     <div class="message info">
      {$mod->Lang('info_recover')}
     </div>
    {/if}
    {if !empty($error)}
     <div class="message error">
      {$error}
     </div>
    {else if !empty($warning)}
     <div class="message warning">
      {$warning}
     </div>
    {elseif !empty($message)}
     <div class="message success">
      {$message}
     </div>
    {/if}
    {if !empty($changepwhash)}
     <div class="message info">
      {$mod->Lang('passwordchangedlogin')}
     </div>
    {/if}
    <button type="submit" class="loginsubmit" name="{$actionid}submit">{$mod->Lang('submit')}</button>
    {if !empty($smarty.get.forgotpw)}
    <button type="submit" class="loginsubmit" name="{$actionid}cancel">{$mod->Lang('cancel')}</button>
    {/if}
    </form>
    <a id="goback" href="{root_url}" title="{$mod->Lang('goto',{sitename})}"></a>
    {if empty($smarty.get.forgotpw)}
    <span id="forgotpw" title="{$mod->Lang('recover_start')}">
     <a href="{$forgot_url}">{$mod->Lang('lostpw')}</a>
    </span>
    {/if}
   </div>
  </div>{* login-container *}
  {$bottom_includes|default:''}
 </body>
</html>
