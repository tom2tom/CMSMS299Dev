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
  <meta name="copyright" content="Ted Kulp, CMS Made Simple" />
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
      <!-- end header-top -->
      <!-- start header-bottom -->
      <div class="header-bottom cf">
        <!-- welcome -->
        <div class="welcome">
          {if isset($myaccount)}
          <span><a class="welcome-user" href="myaccount.php?{$secureparam}" title="{lang('myaccount')}">{lang('signed_in',{$user->username})}</a></span>
          {else}
          <span>{lang('signed_in',{$user->username})}</span>
          {/if}
        </div>
        <!-- bookmarks -->
        {include file='shortcuts.tpl'}
      </div>
      <!-- end header-bottom -->
    </header>
    <!-- end header -->
    <div class="shadow">&nbsp;</div>
    <!-- start content -->
    <div id="pg_content">
      <!-- start sidebar -->
      <div id="pg_sidebar">
        <aside>
          <span title="{lang('open')}/{lang('close')}" class="toggle-button close"></span> {include file='navigation.tpl'}
        </aside>
      </div>
      <!-- end sidebar -->
      <!-- start main -->
      <div id="pg_mainarea" class="cf">
        {strip}
        <article role="main" class="content-inner">
          <header class="pageheader{if isset($is_ie)} drop-hidden{/if} cf">
            {if !empty($icon_url) || !empty($pagetitle)}<h1>
            {if isset($icon_url)}<img src="{$icon_url}" alt="{$icon_alt|default:''}" class="headericon" />{/if}{$pagetitle|default:''}
            </h1>{/if}
            {if !empty($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{lang('module_help')}</a></span>{/if}
          </header>
          {if !empty($pagetitle) && !empty($subtitle)}
          <header class="subheader">
            <h3 class="subtitle">{$subtitle}</h3>
          </header>{/if}
          <section class="cf">
            <div class="pagecontainer">
            {$content}
            </div>
          </section>
        </article>
        {/strip}
      </div>
      <!-- end main -->
      <div class="spacer">
        &nbsp;
      </div>
    </div>
    <!-- end content -->
    <!-- start footer -->
    {include file='footer.tpl'}
    <!-- end footer -->
    {$footertext|default:''}
  </div>
  <!-- end container -->
</body>
{bottom_includes}
{$pagelast|default:''}
</html>
