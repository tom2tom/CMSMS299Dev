<!doctype html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
<head>
 <title>{lang('login_sitetitle', {sitename})}</title>
 <meta charset="{$encoding}" />
 <meta name="generator" content="CMS Made Simple" />
 <meta name="robots" content="noindex, nofollow" />
 <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
 <meta name="HandheldFriendly" content="true" />
 <meta name="msapplication-TileColor" content="#f89938" />
 <meta name="msapplication-TileImage" content="{$admin_url}/themes/assets/images/favicon/ms-application-icon.png" />
 <base href="{$admin_url}/" />
 <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
 <link rel="stylesheet" href="themes/Ghostgum/css/style{if $lang_dir=='rtl'}-rtl{/if}.css" />
{$header_includes|default:''}
 <script type="text/javascript" src="themes/Ghostgum/js/login.js"></script>
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
      {$form}
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
      <img src="{$admin_url}/themes/assets/images/CMSMS-logotext-dark.svg" onerror="this.onerror=null;this.src='{$admin_url}/themes/assets/images/CMSMS-logotext-dark.png';" alt="CMS Made Simple" height="30" />
    </div>
  </div>
 </div>
 {$bottom_includes|default:''}
</body>
</html>
