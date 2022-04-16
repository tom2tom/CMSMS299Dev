<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
 <head>
  <title>{['loginto',{sitename}]|lang}</title>
  <base href="{$admin_url}/" />
  <meta charset="{$encoding}" />
  <meta name="generator" content="CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="referrer" content="origin" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
  <meta name="HandheldFriendly" content="true" />
  <meta name="msapplication-TileColor" content="#f89938" />
  <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png" />
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&display=swap" />
  {$font_includes}
  <link rel="stylesheet" href="themes/Altbier/styles/bootstrap_reboot-grid.min.css" />
  {$header_includes|default:''}
 </head>{$lost=isset($smarty.get.forgotpw)}{if 0}{$c=''}{elseif !$lost}{$c=' class="login"'}{else}{$c=' class="forgotpw"'}{/if}
 <body id="login"{$c}>
  <div class="container pt-5" style="height:100%;">
   <div class="row align-center" style="height:100%;">
    <div class="mx-auto">
     <div class="login-box p-2 p-sm-3"{if isset($error)} id="error"{/if}>
      <noscript>
         message error mt-2 pt-2">
         {'login_info_needjs'|lang}
        </div>
      </noscript>
      <div id="info-wrapper" class="cell col-12 open">
       <aside class="message information p-4">
{*       <p>{['login_info_params',"<strong>{$smarty.server.HTTP_HOST}</strong>"]|lang}</p>*}
         <p>{'login_info_params'|lang}</p>
         <p>{'info_cookies'|lang}</p>
       </aside>
      </div>
      <div class="row go-gutter between">
        {if $lost}
        <span></span>
        {else}
        <a id="toggle-info" class="cell" href="javascript:void()" title="{'open'|lang}/{'close'|lang}"><span tabindex="0" role="note" aria-title="{'login_info_title'|lang}" class="fas fa-info-circle"></span><span class="sr-only">{'login_info_title'|lang}</span></a>
        {/if}
        {if empty($sitelogo)}
        <span></span>
        {else}
        <img id="sitelogo" class="cell" src="{$sitelogo}" title="{sitename}" alt="{sitename}" />
        {/if}
      </div>
      <header class="cell col-12 text-center">
      <h1>{if $lost}{['forgotpwtitle',{sitename}]|lang}
      {elseif isset($renewpw)}{['renewpwtitle',{sitename}]|lang}
      {else}{['login_sitetitle',{sitename}]|lang}{/if}</h1>
     </header>
      <div class="cell col-12 mx-auto text-center">
      {if isset($form)}{$form}{else}{include file='form.tpl'}{block name=form}{/block}{/if}
      </div>
      {if $lost}
       <div tabindex="0" role="alertdialog" class="cell col-12 message information mt-2 pt-2">
        {'forgotpwprompt'|lang}
       </div>
      {elseif isset($renewpw)}
       <div tabindex="0" role="alertdialog" class="cell col-12 message warning mt-2 pt-2">
        {'renewpwprompt'|lang}
       </div>
{*      {elseif !empty($changepwhash)}
       <div tabindex="0" role="alertdialog" class="cell col-12 message information mt-2 pt-2">
        {'passwordchange'|lang}
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
        <a class="cell col-12 small" href="{root_url}" title="{['goto',{sitename}]|lang}"><span class="fas fa-chevron-circle-left" aria-hidden="true"></span> {'viewsite'|lang}</a>
        {if !($lost || isset($renewpw))}
        <a href="login.php?forgotpw=1" title="{'recover_start'|lang}" class="cell col-12 small"><span class="fas fa-question-circle" aria-hidden="true"></span> {'lostpw'|lang}</a>
        {/if}
       </div>
      </div>
     </div>
     <div class="cell col-12 mx-auto text-center">
      <a rel="external" href="http://www.cmsmadesimple.org">
       <img class="img-fluid" src="themes/assets/images/cmsms-logotext-dark.svg" onerror="this.onerror=null;this.src='themes/assets/images/cmsms-logotext-dark.png';" style="width:11em;margin-top:3px" alt="CMS Made Simple" />
      </a>
     </div>
    </div>
   </div>
  </div>
  {$bottom_includes|default:''}
 </body>
</html>
