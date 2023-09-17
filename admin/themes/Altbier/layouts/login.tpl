<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
 <head>
  <title>{lang('loginto',{sitename})}</title>
  <base href="{$admin_url}/">
  <meta charset="{$encoding}">
  <meta name="generator" content="CMS Made Simple">
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="origin">
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no">
  <meta name="HandheldFriendly" content="True">
  <meta name="msapplication-TileColor" content="#f89938">
  <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png">
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap">
  {$font_includes}
  <link rel="stylesheet" href="themes/Altbier/styles/bootstrap_reboot-grid.min.css">
  {$header_includes|default:''}
 </head>{$lost=isset($smarty.get.forgotpw)}{if 0}{$c=''}{elseif !$lost}{$c=' class="login"'}{else}{$c=' class="forgotpw"'}{/if}
 <body id="login"{$c}>
  <div class="container pt-5" style="height:100%">
   <div class="row align-center" style="height:100%">
    <div class="mx-auto">
     <div class="login-box p-2 p-sm-3"{if isset($error)} id="error"{/if}>
      <noscript>
         message error mt-2 pt-2">
         {lang('login_info_needjs')}
        </div>
      </noscript>
      <div id="info-wrapper" class="cell col-12 open">
       <aside class="message information p-4">
{*       <p>{lang('login_info_params',"<strong>{$smarty.server.HTTP_HOST}</strong>")}</p>*}
         <p>{lang('login_info_params')}</p>
         <p>{lang('info_cookies')}</p>
       </aside>
      </div>
      <div class="row go-gutter between">
        {if $lost}
        <span></span>
        {else}
        <a id="toggle-info" class="cell" href="javascript:void()" title="{lang('open')}/{lang('close')}"><span tabindex="0" role="note" aria-title="{lang('login_info_title')}" class="fas fa-info-circle"></span><span class="sr-only">{lang('login_info_title')}</span></a>
        {/if}
        {if empty($sitelogo)}
        <span></span>
        {else}
        <img id="sitelogo" class="cell" src="{$sitelogo}" title="{sitename}" alt="{sitename}">
        {/if}
      </div>
      <header class="cell col-12 text-center">
      <h1>{if $lost}{lang('forgotpwtitle',{sitename})}
      {elseif isset($renewpw)}{lang('renewpwtitle',{sitename})}
      {else}{lang('login_sitetitle',{sitename})}{/if}</h1>
     </header>
      <div class="cell col-12 mx-auto text-center">
      {if isset($form)}{$form}{else}{include file='form.tpl'}{block name=form}{/block}{/if}
      </div>
      {if $lost}
       <div tabindex="0" role="alertdialog" class="cell col-12 message information mt-2 pt-2">
        {lang('forgotpwprompt')}
       </div>
      {elseif isset($renewpw)}
       <div tabindex="0" role="alertdialog" class="cell col-12 message warning mt-2 pt-2">
        {lang('renewpwprompt')}
       </div>
{*      {elseif !empty($changepwhash)}
       <div tabindex="0" role="alertdialog" class="cell col-12 message information mt-2 pt-2">
        {lang('passwordchange')}
       </div>*}
      {/if}
      {if !empty($error)}
       <div tabindex="0" role="alertdialog" class="cell col-12 message error mt-2 pt-2">
        {$error}
       </div>
      {/if}
      {if !empty($warning)}
       <div tabindex="0" role="alertdialog" class="cell col-12 message warning mt-2 pt-2">
        {$warning}
       </div>
      {/if}
      {if !empty($message)}
       <div tabindex="0" role="alertdialog" class="cell col-12 message information mt-2 pt-2">
        {$message}
       </div>
      {/if}
      <div class="cell col-12 mt-3 px-0">
       <div class="row alt-actions">
        <a class="cell col-12 small" href="{root_url}" title="{lang('goto',{sitename})}"><span class="fas fa-chevron-circle-left" aria-hidden="true"></span> {lang('viewsite')}</a>
        {if !($lost || isset($renewpw))}
        <a href="login.php?forgotpw=1" title="{lang('recover_start')}" class="cell col-12 small"><span class="fas fa-question-circle" aria-hidden="true"></span> {lang('lostpw')}</a>
        {/if}
       </div>
      </div>
     </div>
     <div class="cell col-12 mx-auto text-center">
      <a rel="external" href="http://www.cmsmadesimple.org">
       <img class="img-fluid" src="themes/assets/images/cmsms-logotext-dark.svg" onerror="this.onerror=null;this.src='themes/assets/images/cmsms-logotext-dark.png';" style="width:11em;margin-top:3px" alt="CMS Made Simple">
      </a>
     </div>
    </div>
   </div>
  </div>
  {$bottom_includes|default:''}
 </body>
</html>
