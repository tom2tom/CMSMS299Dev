<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
<head>
 <title>{_la('loginto', {sitename})}</title>
 <base href="{$admin_url}/">
 <meta charset="{$encoding}">
 <meta name="generator" content="CMS Made Simple">
 <meta name="robots" content="noindex, nofollow">
 <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no">
 <meta name="HandheldFriendly" content="true">
 <meta name="msapplication-TileColor" content="#f89938">
 <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png">
 <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico">
 <link rel="preconnect" href="https://fonts.googleapis.com">
 <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
 <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap">
{$header_includes|default:''}
</head>
<body>
 <div id="login">
  <div id="login-container">
    <div id="login-box">
      <noscript>
        <div class="pageerror">{_la('login_info_needjs')}</div>
      </noscript>{$lost=isset($smarty.get.forgotpw)}{*$sitelogo=''DEBUG*}
      {if !empty($sitelogo)}
       <img id="site-logo" src="{$sitelogo}" alt="{sitename}">
      {/if}
      {if !$lost}
      <a id="toggle-info" href="javascript:void()" title="{_la('open')}/{_la('close')}">&nbsp;</a>
      {/if}
      <a id="goto" href="{root_url}" title="{_la('goto',{sitename})}">&nbsp;</a>
      <h1>{if $lost}{_la('forgotpwtitle',{sitename})}
      {elseif isset($renewpw)}{_la('renewpwtitle',{sitename})}
      {else}{_la('login_sitetitle',{sitename})}{/if}</h1>
      {$form}
      {if $lost}<div class="pageinfo">{_la('forgotpwprompt')}</div>
      {elseif isset($renewpw)}<div class="pagewarn">{_la('renewpwprompt')}</div>
{*    {elseif !empty($changepwhash)}<div class="pageinfo">{_la('passwordchange')}</div>*}{/if}
      {if !empty($errmessage)}<div class="pageerror">{$errmessage}</div>{/if}
      {if !empty($warnmessage)}<div class="pagewarn">{$warnmessage}</div>{/if}
      {if !empty($infomessage)}<div class="pageinfo">{$infomessage}</div>{/if}
      <div id="info-wrapper" class="dialog-information login-info">
{*     <p>{_la('login_info_params',"<strong>{$smarty.server.HTTP_HOST}</strong>")}</p>*}
       <p>{_la('login_info_params')}</p>
       <p>{_la('info_cookies')}</p>
      </div>
    </div>
    <div id="cmslogo">
     <span id="cms-logo"></span><span id="logo-text">{_la('power_by')}</span>
    </div>
  </div>
 </div>
{$bottom_includes|default:''}
</body>
</html>
