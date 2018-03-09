<!doctype html>
<html lang="{$lang|truncate:'2':''}" dir="{$lang_dir}">

<head>
  <meta charset="utf-8" />
  <title>{strip}
  {$thetitle=$pagetitle}
  {if $thetitle && $subtitle}{$thetitle="{$thetitle} - {$subtitle}"}{/if}
  {if $thetitle}{$thetitle="{$thetitle} - "}{/if}
  {if $thetitle}{$thetitle}{/if}{sitename}
  {/strip}</title>
  <base href="{$config.admin_url}/" />
  <meta name="generator" content="CMS Made Simple - Copyright (C) 2004-2018 Ted Kulp. All rights reserved." />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="referrer" content="origin" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0" />
  <meta name="HandheldFriendly" content="true" />
  {header_includes}
  {$headertext|default:''}
</head>

<body lang="{$lang|truncate:'2':''}" id="{$pagetitle|md5}" class="pg_{$pagealias}">
  <!-- start container -->
  <div id="pg_container" class="sidebar-on">
    <!-- start header -->
    <header role="banner" class="cf header">
      <!-- start header-top -->
      <div class="header-top cf">
       {if isset($sitelogo)}
        <div class="admin-title">
          <img src="{$sitelogo}" alt="{sitename}" />
        </div>
        <div class="admin-title">
          {lang('adminpaneltitle')}
        </div>
       {else}
        <div class="admin-title">
         {sitename} - {lang('adminpaneltitle')}
        </div>
        {/if}
        <!-- logo -->
        <div class="cms-logo">
          <a href="http://www.cmsmadesimple.org" rel="external"><img src="{$config.admin_url}/themes/Marigold/images/cmsms_logotext.png" width="185" height="36" alt="CMS Made Simple" title="CMS Made Simple" /></a>
        </div>
        <div class="cms-text">{lang('power_by')}</div>
        <!-- title -->
      </div>
      <div class='clear'></div>
      <!-- end header-top //-->
      <!-- start header-bottom -->
      <div class="header-bottom cf">
        <!-- welcome -->
        <div class="welcome">
          {if isset($myaccount)}
          <span><a class="welcome-user" href="myaccount.php?{$secureparam}" title="{lang('myaccount')}"><i class="fa fa-user"></i></a>
              <a href="myaccount.php?{$secureparam}">{lang('signed_in',{$user->username})}</a></span>
          {else}
          <span><a class="welcome-user"><i class="fa fa-user"></i></a> {lang('signed_in',{$user->username})}</span>
          {/if}
        </div>
        <!-- bookmarks -->
        {include file='shortcuts.tpl'}
      </div>
      <!-- end header-bottom //-->
    </header>
    <!-- end header //-->
    <!-- start content -->
    <div id="pg_content">
      <div class="shadow">
        &nbsp;
      </div>
      <!-- start sidebar -->
      <div id="pg_sidebar">
        <aside>
          <span title="{lang('open')}/{lang('close')}" class="toggle-button close"></span> {include file='navigation.tpl' nav=$theme->get_navigation_tree()}
        </aside>
      </div>
      <!-- end sidebar //-->
      <!-- start main -->
      <div id="pg_mainarea" class="cf">
        {strip}
        <article role="main" class="content-inner">
          <header class="pageheader{if isset($is_ie)} drop-hidden{/if} cf">
            {if isset($module_icon_url) || isset($pagetitle)}
            <h1>{if isset($module_icon_url)}<img src="{$module_icon_url}" alt="{$module_name|default:''}" class="module-icon" />{/if}{$pagetitle|default:''}
            </h1>
            {/if} {if isset($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{lang('module_help')}</a></span>{/if}
          </header>
          {if $pagetitle && $subtitle}
          <header class="subheader">
            <h3 class="subtitle">{$subtitle}</h3>
          </header>{/if}
          <section class="cf">
            {$content}
          </section>
        </article>
        {/strip}
      </div>
      <!-- end main //-->
      <div class="spacer">
        &nbsp;
      </div>
    </div>
    <!-- end content //-->
    <!-- start footer -->
    {include file='footer.tpl'}
    <!-- end footer //-->
    {$footertext|default:''}
  </div>
  <!-- end container //-->
</body>
{bottom_includes}
{$pagelast|default:''}
</html>
