<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
 <head>
  <title>{['loginto',{sitename}]|lang}</title>
  <base href="{$admin_url}/" />
  <meta charset="{$encoding}" />
  <meta name="generator" content="CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
  <meta name="HandheldFriendly" content="true"/>
  <meta name="msapplication-TileColor" content="#f89938" />
  <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png" />
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
 {$font_includes}
 {$header_includes|default:''}
 </head>
 <body id="login">
  <div id="wrapper">
   <div class="login-container">
    <div class="login-box cf"{if !empty($error)} id="error"{/if}>
     <div class="logo">
      <img src="themes/assets/images/cmsms-logotext-dark.svg" onerror="this.onerror=null;this.src='themes/assets/images/cmsms-logotext-dark.png';" style="height:36px" alt="CMS Made Simple" />
     </div>
     <div class="info-wrapper open">
     <aside class="info">
     <h2>{'login_info_title'|lang}</h2>
      <p>{'login_info'|lang}</p>
       {'login_info_params'|lang}
       <p><strong>({$smarty.server.HTTP_HOST})</strong></p>
      <p class="warning">{'warn_admin_ipandcookies'|lang}</p>
     </aside>
     <a href="javascript:void()" title="{'open'|lang}/{'close'|lang}" class="toggle-info">{'open'|lang}/{'close'|lang}</a>
     </div>
     <header>
      <a style="float:right;" href="{root_url}" title="{['goto',{sitename}]|lang}"><img class="goback" width="16" height="16" src="themes/Marigold/images/layout/goback.png" alt="{['goto',{sitename}]|lang}" /></a>
      {$lost=isset($smarty.get.forgotpw)}
      <h1>{if $lost}{['forgotpwtitle',{sitename}]|lang}
      {elseif isset($renewpw)}{['renewpwtitle',{sitename}]|lang}
      {elseif !empty($sitelogo)}{'login_admin'|lang}
      {else}{['login_sitetitle',{sitename}]|lang}{/if}</h1>
     </header>
     {$form}
     {if $lost}<div class="message information">{'forgotpwprompt'|lang}</div>
     {elseif isset($renewpw)}<div class="message warning">{'renewpwprompt'|lang}</div>
{*   {elseif !empty($changepwhash)}<div class="message information">{'passwordchange'|lang}</div>*}{/if}
     {if !empty($errmessage)}<div class="message error">{$errmessage}</div>{/if}
     {if !empty($warnmessage)}<div class="message warning">{$warnmessage}</div>{/if}
     {if !empty($infomessage)}<div class="message information">{$infomessage}</div>{/if}
    </div>
   </div>
  </div>
  {$bottom_includes|default:''}
 </body>
</html>
