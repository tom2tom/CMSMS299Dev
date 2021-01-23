<!doctype html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
<head>
 <title>{lang('login_sitetitle', {sitename})}</title>
 <base href="{$admin_url}/" />
 <meta charset="{$encoding}" />
 <meta name="generator" content="CMS Made Simple" />
 <meta name="robots" content="noindex, nofollow" />
 <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
 <meta name="HandheldFriendly" content="true" />
 <meta name="msapplication-TileColor" content="#f89938" />
 <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png" />
 <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
 <link rel="stylesheet" href="themes/Ebonne/css/style{if $lang_dir=='rtl'}-rtl{/if}.css" />
{$header_includes|default:''}
 <script type="text/javascript" src="themes/Ebonne/js/login.min.js"></script>
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
       {lang('forgotpwtitle',{sitename})}
      {elseif isset($renewpw)}
       {lang('renewpwtitle',{sitename})}
      {elseif !empty($sitelogo)}
       {lang('login_admin')}
      {else}{lang('login_sitetitle',{sitename})}{/if}</h1>
      {$form}
      {if !empty($smarty.get.forgotpw)}
       <div class="login-info">{lang('forgotpwprompt')}</div>
      {elseif isset($renewpw)}
       <div class="login-info">{lang('renewpwprompt')}</div>
      {/if}
      {if !empty($errmessage)}<div class="pageerror">{$errmessage}</div>{/if}
      {if !empty($warnmessage)}<div class="pagewarn">{$warnmessage}</div>{/if}
      {if !empty($infomessage)}<div class="pagesuccess">{$infomessage}</div>{/if}
      {if !empty($changepwhash)}<div class="pageinfo">{lang('passwordchange')}</div>{/if}
      <div id="info-wrapper" class="login-info">
       {lang('login_info')}
       {lang('login_info_params')}
       <p>{$smarty.server.HTTP_HOST}</p>
       <div class="pagewarn">{lang('warn_admin_ipandcookies')}</div>
      </div>
    </div>
    <div id="cmslogo">
     <span id="logotext">{lang('power_by')}</span><span id="cms-logo"></span>
    </div>
  </div>
 </div>
 {$bottom_includes|default:''}
</body>
</html>
