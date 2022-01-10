<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
<head>
 <title>{_la('loginto', {sitename})}</title>
 <base href="{$admin_url}/" />
 <meta charset="{$encoding}" />
 <meta name="generator" content="CMS Made Simple" />
 <meta name="robots" content="noindex, nofollow" />
 <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
 <meta name="HandheldFriendly" content="true" />
 <meta name="msapplication-TileColor" content="#f89938" />
 <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png" />
 <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
{$header_includes|default:''}
</head>
<body>
 <div id="login">
  <div id="login-container">
    <div id="login-box">
      <a id="toggle-info" href="javascript:void()" title="{_la('open')}/{_la('close')}">&nbsp;</a>
      {if empty($sitelogo)}
       <a id="goto" href="{root_url}" title="{_la('goto',{sitename})}">&nbsp;</a>
      {else}
       <a href="{root_url}">
        <img id="sitelogo" src="{$sitelogo}" title="{_la('goto',{sitename})}" alt="{sitename}" />
       </a>
      {/if}
      {$lost=isset($smarty.get.forgotpw)}
      <h1>{if $lost}{_la('forgotpwtitle',{sitename})}
      {elseif isset($renewpw)}{_la('renewpwtitle',{sitename})}
      {elseif !empty($sitelogo)}{_la('login_admin')}
      {else}{_la('login_sitetitle',{sitename})}{/if}</h1>
      {$form}
      {if $lost}<div class="pageinfo">{_la('forgotpwprompt')}</div>
      {elseif isset($renewpw)}<div class="pagewarn">{_la('renewpwprompt')}</div>
{*    {elseif !empty($changepwhash)}<div class="pageinfo">{_la('passwordchange')}</div>*}{/if}
      {if !empty($errmessage)}<div class="pageerror">{$errmessage}</div>{/if}
      {if !empty($warnmessage)}<div class="pagewarn">{$warnmessage}</div>{/if}
      {if !empty($infomessage)}<div class="pageinfo">{$infomessage}</div>{/if}
      <div id="info-wrapper" class="login-info">
       {_la('login_info')}
       {_la('login_info_params')}
       <p>{$smarty.server.HTTP_HOST}</p>
       <div class="pagewarn">{_la('warn_admin_ipandcookies')}</div>
      </div>
    </div>
    <div id="cmslogo">
     <span id="logotext">{_la('power_by')}</span><span id="cms-logo"></span>
    </div>
  </div>
 </div>
{$bottom_includes|default:''}
</body>
</html>
