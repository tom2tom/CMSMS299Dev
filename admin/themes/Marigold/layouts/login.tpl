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
  <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png">
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap">
 {$font_includes}
 {$header_includes|default:''}
 </head>
 <body id="login">
   <div id="login-wrapper">
     <div id="login-container">
       <div id="login-box" class="cf{if isset($error)} error{/if}">
         <noscript>
           <div class="message error">{lang('login_info_needjs')}</div>
         </noscript>
         {if !empty($sitelogo)}
           <img id="sitelogo" src="{$sitelogo}" title="{sitename}" alt="{sitename}">
         {/if}
         <header>{$lost=isset($smarty.get.forgotpw)}
           <h1>{if $lost}{lang('forgotpwtitle',{sitename})}
           {elseif isset($renewpw)}{lang('renewpwtitle',{sitename})}
           {else}{lang('login_sitetitle',{sitename}])}{/if}</h1>
         </header>
         <div>
           {if isset($form)}{$form}{else}{include file='form.tpl'}{block name=form}{/block}{/if}
         </div>
         {if $lost}<div class="message information">{lang('forgotpwprompt')}</div>
         {elseif isset($renewpw)}<div class="message warning">{lang('renewpwprompt')}</div>
         {/if}
         {if !empty($error)}<div class="message error">{$error}</div>{/if}
         {if !empty($warning)}<div class="message warning">{$warning}</div>{/if}
         {if !empty($message)}<div class="message information">{$message}</div>{/if}
         {if !$lost}
         <a id="toggle-info" href="javascript:void()" title="{lang('open')}/{lang('close')}"><i class="fa fa-info" aria-hidden="true"></i> {lang('login_info_title')}</a>
         <br>
         {/if}
         <a id="goto" href="{root_url}" title="{lang('goto',{sitename})}"><i class="cfi-mainsite" aria-hidden="true"></i> {lang('viewsite')}</a>
         {if !$lost}
         <div id="info-wrapper" class="information">
{*         <p>{lang('login_info_params',"<strong>{$smarty.server.HTTP_HOST}</strong>")}</p>*}
           <p>{lang('login_info_params')}</p>
           <p>{lang('info_cookies')}</p>
         </div>
         {/if}
       </div>
     </div>
     <a id="cms-logo" href="http://www.cmsmadesimple.org" rel="external">
       <img src="themes/assets/images/cmsms-logotext-dark.svg" onerror="this.onerror=null;this.src='themes/assets/images/cmsms-logotext-dark.png';" alt="CMS Made Simple">
     </a>
   </div>
 {$bottom_includes|default:''}
 </body>
</html>
