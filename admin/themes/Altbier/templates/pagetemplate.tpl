<!doctype html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
 <head>
 {$thetitle=$pagetitle}
 {if $thetitle && $subtitle}{$thetitle="{$thetitle} - {$subtitle}"}{/if}
 {if $thetitle}{$thetitle="{$thetitle} - "}{/if}
  <title>{$thetitle}{sitename}</title>
  <base href="{$admin_url}/" />
  <meta charset="utf-8" />
  <meta name="generator" content="CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="referrer" content="origin" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
  <meta name="HandheldFriendly" content="true" />
  <meta name="msapplication-TileColor" content="#f89938" />
  <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png" />
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
  <link rel="apple-touch-icon" href="themes/assets/images/apple-touch-icon-iphone.png" />
  <link rel="apple-touch-icon" sizes="72x72" href="themes/assets/images/apple-touch-icon-ipad.png" />
  <link rel="apple-touch-icon" sizes="114x114" href="themes/assets/images/apple-touch-icon-iphone4.png" />
  <link rel="apple-touch-icon" sizes="144x144" href="themes/assets/images/apple-touch-icon-ipad3.png" />
  {$font_includes}
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Open+Sans:400,400i,600,600i" />
  <link rel="stylesheet" href="themes/Altbier/css/bootstrap_reboot-grid.min.css" />
  {$header_includes|default:''}
 </head>
 <body id="{$pagetitle|md5}" class="ac_{$pagealias}">
  <div id="ac_container" class="sidebar-on">
   <div class="container-fluid">
    <header role="banner" class="header row">
     <div class="col-12">
      <div class="header-top row pt-1">
       <div class="cms-logo">
        <a href="http://www.cmsmadesimple.org" rel="external">
         <img class="img-fluid" src="themes/assets/images/cmsms-logotext-dark.svg" onerror="this.onerror=null;this.src='themes/assets/images/cmsms-logotext-dark.png';" alt="CMS Made Simple" title="CMS Made Simple" />
        </a>
       </div>
       <div class="col admin-title">{sitename} - {'adminpaneltitle'|lang}</div> {*col-12 col-sm-6*}
      </div>
      <div class="header-bottom row">
       <div class="col-6 welcome">
        {if isset($myaccount)}
        <span><a class="welcome-user" href="useraccount.php?{$secureparam}" title="{'myaccount'|lang}"><i aria-label="username and account" class="fas fa-user-edit"></i></a> {'welcome_user'|lang}: <a href="useraccount.php?{$secureparam}">{$username}</a></span>
        {else}
        <span class="welcome-user"><i aria-hidden="true" class="fas fa-user"></i> {'welcome_user'|lang}: {$username}</span>
        {/if}
       </div>
       {include file='shortcuts.tpl'}{block name=shortcuts}{/block}
      </div>
     </div>
    </header>

    <div id="ac_admin-content" class="row flex-nowrap">
     <div id="ac_sidebar" class="col flex-grow-0 flex-shrink-0 p-0">
      <aside>
       <span title="{'open'|lang}/{'close'|lang}" role="button" tabindex="0" aria-label="{'open'|lang}/{'close'|lang}" class="toggle-button close"></span>
       {include file='navigation.tpl' nav=$theme->get_navigation_tree()}
      </aside>
     </div>
     <div id="ac_mainarea" class="col p-0">
      {strip}
      {include file='messages.tpl'}{block name=messages}{/block}
      <article role="main" class="content-inner">
       <header class="pageheader{if isset($is_ie)} drop-hidden{/if} cf">
        {if !empty($pageicon) || !empty($pagetitle)}
        <h1>
         {if !empty($pageicon)}<span class="headericon">{$pageicon}</span> {/if}{$pagetitle|default:''}
        </h1>
        {/if}
        {if isset($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{'module_help'|lang}</a></span>{/if}
       </header>
       {if $pagetitle && $subtitle}<header class="subheader"><h3 class="subtitle">{$subtitle}</h3></header>{/if}
       <section class="cf">
       <div class="pagecontainer">{$content}</div>
       </section>
      </article>
      {/strip}
     </div>
    </div>
    {include file='footer.tpl'}{block name=footer}{/block}
   </div>
  </div>
  {$bottom_includes|default:''}
 </body>
</html>
