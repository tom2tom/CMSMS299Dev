<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
 <head>
  <title>{lang('loginto',{sitename})}</title>
  <base href="{$admin_url}/">
  <meta charset="{$encoding}">
  <meta name="generator" content="CMS Made Simple">
  <meta name="robots" content="noindex, nofollow">
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no">
  <meta name="HandheldFriendly" content="true">
  <meta name="msapplication-TileColor" content="#f89938">
  <meta name="msapplication-TileImage" content="themes/OneEleven/images/favicon/ms-application-icon.png">
  <link rel="shortcut icon" href="themes/OneEleven/images/favicon/cmsms-favicon.ico">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
  {$header_includes|default:''}
 </head>
 <body id="login">
  <div id="wrapper">
   <div class="login-container">
    <div class="login-box cf"{if !empty($error)} id="error"{/if}>
     <div class="logo">
      <a rel="external" href="http://www.cmsmadesimple.org">
      <img src="themes/OneEleven/images/layout/cmsms_login_logo.png" width="180" alt="CMS Made Simple">
      </a>
     </div>
     <noscript>
      <div class="message error">{lang('login_info_needjs')}</div>
     </noscript>
     <div id="info-wrapper" class="open">
      <aside class="message information">
{*     <p>{lang('login_info_params',"<strong>{$smarty.server.HTTP_HOST}</strong>")}</p>*}
       <p>{lang('login_info_params')}</p>
       <p>{lang('info_cookies')}</p>
      </aside>
     </div>{$t=}
     <a href="javascript:void()" id="toggle-info" title="{lang('open')}/{lang('close')}">{lang('open')}/{lang('close')}</a>
     <header>{assign var='lost' value=isset($smarty.get.forgotpw)}
      <h1>{if $lost}{lang('forgotpwtitle',{sitename})}
      {elseif isset($renewpw)}{lang('renewpwtitle',{sitename})}
      {elseif !empty($sitelogo)}{lang('login_admin')}
      {else}{lang('login_sitetitle',{sitename})}{/if}</h1>
     </header>
     {if isset($form)}{$form}{else}{include file='form.tpl'}{block name=form}{/block}{/if}
     {if $lost}<div class="message information">{lang('forgotpwprompt')}</div>
     {elseif isset($renewpw)}<div class="message warning">{lang('renewpwprompt')}
{*   {elseif !empty($changepwhash)}<div class="message information">{lang('passwordchange')}</div>*}{/if}
     {if !empty($error)}<div class="message error">{$error}</div>{/if}
     {if !empty($warning)}<div class="message warning">{$warning}</div>{/if}
     {if !empty($message)}<div class="message information">{$message}</div>{/if}
     <a href="{root_url}" title="{lang('goto',{sitename})}"><img class="goback" width="16" height="16" src="themes/OneEleven/images/layout/goback.png" alt="{lang('goto',{sitename})}"></a>
     {if !($lost || isset($renewpw))}
     <div class="forgotpw">
      <a href="login.php?forgotpw=1" title="{lang('recover_start')}">{lang('lostpw')}</a>
     </div>
     {/if}
    </div>
   </div>
  </div>
  {$bottom_includes|default:''}
 </body>
</html>
