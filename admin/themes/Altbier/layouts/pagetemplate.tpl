<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
 <head>
 {$thetitle=$pagetitle}
 {if $thetitle && $subtitle}{$thetitle="{$thetitle} - {$subtitle}"}{/if}
 {if $thetitle}{$thetitle="{$thetitle} - "}{/if}
  <title>{$thetitle}{sitename}</title>
  <base href="{$admin_url}/">
  <meta charset="UTF-8">
  <meta name="generator" content="CMS Made Simple">
  <meta name="robots" content="noindex, nofollow">
  <meta name="referrer" content="origin">
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no">
  <meta name="HandheldFriendly" content="true">
  <meta name="msapplication-TileColor" content="#f89938">
  <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png">
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico">
  <link rel="apple-touch-icon" href="themes/assets/images/apple-touch-icon-iphone.png">
  <link rel="apple-touch-icon" sizes="72x72" href="themes/assets/images/apple-touch-icon-ipad.png">
  <link rel="apple-touch-icon" sizes="114x114" href="themes/assets/images/apple-touch-icon-iphone4.png">
  <link rel="apple-touch-icon" sizes="144x144" href="themes/assets/images/apple-touch-icon-ipad3.png">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&family=Red+Hat+Mono:wght@400;700&display=swap">
  {$font_includes}
  <link rel="stylesheet" href="themes/Altbier/styles/bootstrap_reboot-grid.min.css">
  {$header_includes|default:''}
 </head>
 <body id="{$pagetitle|md5}" class="ab_{$pagealias}">
  <div id="ab_container" class="col sidebar-on">
    <header id="header" class="row no-gutter between align-center" role="banner">
{*   <div class="cell col-12">
      <div class="row align-center header-top">*}
       <div class="cell col-auto admin-title">{$t='home'|lang}
        {if empty($sitelogo)}
         {sitename}
         <span id="site-text">-&nbsp;<a href="menu.php?{$secureparam}" title="{$t}">{'adminpaneltitle'|lang}</a></span>
        {else}
         <a href="menu.php?{$secureparam}" title="{$t}">
          <img src="{$sitelogo}" alt="{$t}">
         </a>
         <span id="site-text">{'adminpaneltitle'|lang}</span>
        {/if}
       </div>
       <a id="headerlogo" href="https://www.cmsmadesimple.org" rel="external" title="{'cms_home'|lang}"></a>
{*      </div>
       <div class="row between align-center header-bottom">
       <div class="col-auto welcome">
        {if isset($myaccount)}{* TODO show this message only when user != effective user * }
        <span><a class="welcome-user" href="useraccount.php?{$secureparam}" title="{'myaccount'|lang}">{*<i aria-title="username and account" class="fas fa-user-edit"></i>* }</a> {'welcome_user'|lang}: <a href="useraccount.php?{$secureparam}">{$username}</a></span>
        {else}
        <span class="welcome-user"><i class="fas fa-user" aria-hidden="true"></i> {'welcome_user'|lang}: {$username}</span>
        {/if}
       </div>
*}
       {include file='shortcuts.tpl'}{block name=shortcuts}{/block}
{*
      </div>
     </div>
*}
    </header>

    <div id="ab_admin-content" class="row nowrap">
     <div id="ab_sidebar" class="col flex-grow-2 p-0">{* TODO flex-grow etc ??*}
      <aside>
       <span id="toggle-button" class="close" title="{'open'|lang}/{'close'|lang}" role="button" tabindex="0" aria-title="{'open'|lang}/{'close'|lang}"></span>
       {include file='navigation.tpl' nav=$theme->get_navigation_tree()}
      </aside>
     </div>
     <div id="ab_mainarea" class="col">
      {strip}
      {include file='messages.tpl'}{block name=messages}{/block}
{*      <article role="main" class="content-inner"> *}
{*       <header class="pageheader{if isset($is_ie)} drop-hidden{/if}"> *}
        {if !empty($pageicon) || !empty($pagetitle)}
        <h1>
         {if !empty($pageicon)}<span class="headericon">{$pageicon}</span> {/if}{$pagetitle|default:''}
        </h1>
        {/if}
        {if isset($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{'module_help'|lang}</a></span>{/if}
{*       </header> *}
       {if $pagetitle && $subtitle}<header class="subheader"><h3 class="subtitle">{$subtitle}</h3></header>{/if}
       <section class="cf">
       <div class="pagecontainer">{$content}</div>
       </section>
{*      </article> *}
      {/strip}
     </div>
    </div>
    {include file='footer.tpl'}{block name=footer}{/block}
  </div>
  {$bottom_includes|default:''}
 </body>
</html>
