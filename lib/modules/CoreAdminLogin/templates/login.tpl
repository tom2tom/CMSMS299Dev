<!doctype html>
<html lang="{$lang_code}" dir="{$lang_dir}">
<head>
  <meta charset="{$encoding}" />
  <title>{lang('login_sitetitle', {sitename})}</title>
  <base href="{$admin_url}/" />
  <meta name="copyright" content="Ted Kulp, CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0" />
  <meta name="HandheldFriendly" content="true" />
  <link rel="shortcut icon" href="{$admin_url}/themes/assets/images/cmsms-favicon.ico" />
  <link rel="stylesheet" type="text/css" href="{$admin_url}/themes/Ghostgum/css/style{if $lang_dir == 'rtl'}-rtl{/if}.css" />
  {$header_includes|default:''}
</head>
<body>
 <div id="login">
  <div id="login-container">
    <div id="login-box">
      <a id="toggle-info" href="#" title="{lang('open')}/{lang('close')}">&nbsp;</a>
      {if empty($sitelogo)}
       <a id="goto" href="{root_url}" title="{lang('goto')} {sitename}">&nbsp;</a>
      {else}
       <a href="{root_url}">
        <img id="sitelogo" src="{$sitelogo}" title="{lang('goto')} {{sitename}}" alt="{sitename}" />
       </a>
      {/if}
      <h1>{if isset($smarty.get.forgotpw)}
       {lang('recoversitetitle',{sitename})}
      {elseif !empty($sitelogo)}
       {lang('login_admin')}
      {else}{lang('login_sitetitle',{sitename})}{/if}</h1>
      {if isset($smarty.get.forgotpw)}{$usernamefld='forgottenusername'}{else}{$usernamefld='username'}{/if}
      <form action="login.php" method="post">
        <input type="hidden" name="csrf" value="{$csrf}" />
        <input type="text" name="{$actionid}{$usernamefld}"{if !isset($smarty.post.lbusername)} class="focus"{/if} placeholder="{lang('username')}" size="25" value="" autofocus="autofocus" />
        {if !empty($smarty.get.forgotpw)}
        <input type="hidden" name="{$actionid}forgotpwform" value="1" />
        {else}
        <input type="password" name="{$actionid}password"{if !isset($smarty.post.lbpassword) || isset($error)} class="focus"{/if} placeholder="{lang('password')}" size="25" maxlength="100" />
        {/if} {if !empty($changepwhash)}
        <input type="password" name="{$actionid}passwordagain" size="25" placeholder="{lang('passwordagain')}" maxlength="100" />
        <input type="hidden" name="{$actionid}forgotpwchangeform" value="1" />
        <input type="hidden" name="{$actionid}changepwhash" value="{$changepwhash}" />
        {/if}
        <div class="pageinput pregap">
          <button type="submit" name="{$actionid}submit" class="loginsubmit">{lang('submit')}</button>
        {if isset($smarty.get.forgotpw)}
          <button type="submit" name="{$actionid}cancel" class="loginsubmit">{lang('cancel')}</button>
        {/if}
        {if !isset($smarty.get.forgotpw)}<span id="forgotpw">
          <a href="login.php?forgotpw=1" title="{lang('recover_start')}">{lang('lostpw')}</a>
          </span>{/if}
        </div>
      </form>
      {if !empty($smarty.get.forgotpw)}
       <div class="pageinfo">{lang('forgotpwprompt')}</div>
      {/if}
      {if !empty($error)}<div class="pageerror">{$error}</div>{/if}
      {if !empty($warning)}<div class="pagewarn">{$warning}</div>{/if}
      {if !empty($message)}<div class="pagesuccess">{$message}</div>{/if}
      {if !empty($changepwhash)}<div class="pageinfo">{lang('passwordchange')}</div>{/if}
      <div id="info-wrapper">
       {lang('login_info')}
       {lang('login_info_params')}
       <p>{$smarty.server.HTTP_HOST}</p>
       <div class="pagewarn">{lang('warn_admin_ipandcookies')}</div>
      </div>
    </div>
    <div id="cmslogo">
      <span id="logotext">{lang('power_by')}</span>
      <img src="{root_url}/admin/themes/assets/images/cmsms-logotext-dark-small.png" height="30" width="154" alt="CMS Made Simple" />
    </div>
  </div>
 </div>
</body>
{$bottom_includes|default:''}
</html>
