<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}"{if !empty($lang_dir) && $lang_dir|lower == 'rtl'} dir="rtl"{/if}>
 <head>
  <title>{strip}
  {if !empty($pagetitle)}{$thetitle=$pagetitle}{else}{$thetitle=''}{/if}
  {if $thetitle && $subtitle}{$thetitle="{$thetitle} - {$subtitle}"}{/if}
  {if $thetitle}{$thetitle="{$thetitle} - "}{/if}
  {if $thetitle}{$thetitle}{/if}{sitename}
  {/strip}</title>
  <base href="{$admin_url}/" />
  <meta charset="utf-8" />
  <meta name="generator" content="CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
  <meta name="referrer" content="origin" />
  <meta name="HandheldFriendly" content="true" />
  <meta name="msapplication-TileColor" content="#f79838" />
  <meta name="msapplication-TileImage" content="themes/assets/images/ms-application-icon.png" />
  <link rel="shortcut icon" href="themes/assets/images/cmsms-favicon.ico" />
  <link rel="apple-touch-icon" href="themes/assets/images/apple-touch-icon-iphone.png" />
  <link rel="apple-touch-icon" sizes="72x72" href="themes/assets/images/apple-touch-icon-ipad.png" />
  <link rel="apple-touch-icon" sizes="114x114" href="themes/assets/images/apple-touch-icon-iphone4.png" />
  <link rel="apple-touch-icon" sizes="144x144" href="themes/assets/images/apple-touch-icon-ipad3.png" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Open+Sans:ital,wght@0,400;0,700;1,400;1,700&family=Red+Hat+Mono:wght@400;700&display=swap">
  {$header_includes|default:''}
 </head>
 <body>
  <!-- start header -->
  <div id="ggp_header" class="row no-gutter between align-center">

    <div id="site-logo" class="cell">
     {if empty($sitelogo)}
      {sitename}
      <span id="site-text">-&nbsp;<a href="menu.php?{$secureparam}" title="{_la('home')}">{_la('adminpaneltitle')}</a></span>
     {else}
      <a href="menu.php?{$secureparam}" title="{_la('home')}">
        <img src="{$sitelogo}" alt="{_la('home')}" />
      </a>
      <span id="site-text">{_la('adminpaneltitle')}</span>
     {/if}
    </div>

    <div id="system-logo" class="cell">
      <span id="system-text">{_la('power_by')}</span>
      <span id="cms-logo">
       <a href="http://www.cmsmadesimple.org" rel="external" title="{_la('cms_home')}"></a>
      </span>
    </div>

    <div id="shortcuts" class="cell">
     {include file='shortcuts.tpl'}{block name=shortcuts}{/block}
    </div>
  </div>{*end header*}

  <!-- start menu -->
  <div id="ggp_navwrap">
   {include file='navigation.tpl'}{block name=navigation}{/block}
  </div>

  <!-- start content -->
{*  <div id="ggp_container">{*v boxchild and h container*}
  <div id="ggp_contentwrap">
    <div id="ggp_contenthead">
       {strip}{if !empty($pageicon) || !empty($pagetitle)}<h1>
       {if !empty($pageicon)}<span class="headericon">{$pageicon}</span> {/if}{$pagetitle|default:''}
       </h1>{/if}
      {if !empty($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{_la('module_help')}</a></span>{/if}
      {if !empty($pagetitle) && !empty($subtitle)}
       <div class="subheader">
        <h3 class="subtitle">{$subtitle}</h3>
       </div>{/if}
    </div>{* end contenthead *}
     {/strip}
{*  <div id="ggp_content">*}
    <div class="pagecontainer">{$content}</div>
{*   </div>*}
  </div>
{*  </div>{* end container *}

  <!-- start footer -->
  <div id="ggp_footer">{*h container*}
   {include file='footer.tpl'}{block name=footer}{/block}
  </div>{*end footer*}
  {include file='dialogs.tpl'}{block name=hiddendialogs}{/block}
  {$bottom_includes|default:''}
 </body>
</html>
