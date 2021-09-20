<!doctype html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
<head>
 <title>{_ld('admin','loginto', {sitename})}</title>
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
      <a id="toggle-info" href="javascript:void()" title="{_ld('admin','open')}/{_ld('admin','close')}">&nbsp;</a>
      {if empty($sitelogo)}
       <a id="goto" href="{root_url}" title="{_ld('admin','goto',{sitename})}">&nbsp;</a>
      {else}
       <a href="{root_url}">
        <img id="sitelogo" src="{$sitelogo}" title="{_ld('admin','goto',{sitename})}" alt="{sitename}" />
       </a>
      {/if}
      <h1>{if isset($smarty.get.forgotpw)}
       {_ld('admin','forgotpwtitle',{sitename})}
      {elseif isset($renewpw)}
       {_ld('admin','renewpwtitle',{sitename})}
      {elseif !empty($sitelogo)}
       {_ld('admin','login_admin')}
      {else}{_ld('admin','login_sitetitle',{sitename})}{/if}</h1>
      {$form}
      {if !empty($smarty.get.forgotpw)}
       <div class="login-info">{_ld('admin','forgotpwprompt')}</div>
      {elseif isset($renewpw)}
       <div class="login-info">{_ld('admin','renewpwprompt')}</div>
      {/if}
      {if !empty($errmessage)}<div class="pageerror">{$errmessage}</div>{/if}
      {if !empty($warnmessage)}<div class="pagewarn">{$warnmessage}</div>{/if}
      {if !empty($infomessage)}<div class="pagesuccess">{$infomessage}</div>{/if}
      {if !empty($changepwhash)}<div class="pageinfo">{_ld('admin','passwordchange')}</div>{/if}
      <div id="info-wrapper" class="login-info">
       {_ld('admin','login_info')}
       {_ld('admin','login_info_params')}
       <p>{$smarty.server.HTTP_HOST}</p>
       <div class="pagewarn">{_ld('admin','warn_admin_ipandcookies')}</div>
      </div>
    </div>
    <div id="cmslogo">
     <span id="logotext">{_ld('admin','power_by')}</span><span id="cms-logo"></span>
    </div>
  </div>
 </div>
 {$bottom_includes|default:''}
</body>
</html>
