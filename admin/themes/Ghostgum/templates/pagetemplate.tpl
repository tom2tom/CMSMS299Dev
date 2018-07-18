<!doctype html>
<html lang="{$lang|truncate:'2':''}" dir="{$lang_dir}">
<head>
  <title>{strip}
  {if !empty($pagetitle)}{$thetitle=$pagetitle}{else}{$thetitle=''}{/if}
  {if $thetitle && $subtitle}{$thetitle="{$thetitle} - {$subtitle}"}{/if}
  {if $thetitle}{$thetitle="{$thetitle} - "}{/if}
  {if $thetitle}{$thetitle}{/if}{sitename}
  {/strip}</title>
  <meta charset="utf-8" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
  <meta name="copyright" content="CMS Made Simple Foundation" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="referrer" content="origin" />
  <meta name="HandheldFriendly" content="true" />
  <meta name="msapplication-TileColor" content="#f79838" />
  <meta name="msapplication-TileImage" content="{$assets_url}/images/ms-application-icon.png" />
  <base href="{$admin_url}/" />
  <link rel="shortcut icon" href="{$assets_url}/images/cmsms-favicon.ico" />
  <link rel="apple-touch-icon" href="{$assets_url}/images/apple-touch-icon-iphone.png" />
  <link rel="apple-touch-icon" sizes="72x72" href="{$assets_url}/images/apple-touch-icon-ipad.png" />
  <link rel="apple-touch-icon" sizes="114x114" href="{$assets_url}/images/apple-touch-icon-iphone4.png" />
  <link rel="apple-touch-icon" sizes="144x144" href="{$assets_url}/images/apple-touch-icon-ipad3.png" />
  {$header_includes|default:''}
</head>
<body>
  <!-- start header -->
  <div id="ggp_header">
     <div id="site-logo">
     <a href="{root_url}/index.php" rel="external" target="_blank" title="{lang('viewsite')}">
     {if isset($sitelogo)}
      <img src="{$sitelogo}" alt="{sitename}" />
     {else}
      {sitename}
     {/if}
     </a>
     {if !isset($sitelogo)}
       <span class="site-text">- {lang('adminpaneltitle')}</span>
     {/if}
     </div>
   {if isset($sitelogo)}
     <div class="site-text">{lang('adminpaneltitle')}</div>
   {/if}
    <div class="header-links">
    <div>
      <!-- logo -->
      <div id="cms-logo">
        <a href="http://www.cmsmadesimple.org" rel="external">
          <span title="CMS Made Simple">&nbsp;</span>
        </a>
      </div>
      <!-- logotext -->
      <div id="cms-text">{lang('power_by')}</div>
    </div>
    <div>
      <!-- shortcuts -->
      {include file='shortcuts.tpl'}
      {if isset($myaccount)}
       <span class="user"><a href="myaccount.php?{$secureparam}" title="{lang('myaccount')}">{lang('signed_in',{$user->username})}</a></span>
      {else}
       <span class="user">{lang('signed_in',{$user->username})}</span>
      {/if}
    </div>
    </div>
    <div class="clear"></div>
  </div>
  <!-- end header -->
  <div id="ggp_container">
   <div id="ggp_contentwrap">
     <div id="ggp_contenthead">
{*      <div class="{if isset($is_ie)}drop-hidden {/if}"> *}
      {if !empty($icon_tag) || !empty($pagetitle)}<h1>
       {if !empty($icon_tag)}<span class="headericon">{$icon_tag}</span>{/if}{$pagetitle|default:''}
       </h1>{/if}

       {if !empty($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{lang('module_help')}</a></span>{/if}
{*      </div> *}
    {if !empty($pagetitle) && !empty($subtitle)}
      <div class="subheader">
      <h3 class="subtitle">{$subtitle}</h3>
      </div>
    {/if}
    </div>
    <div id="ggp_content">
     <div style="float:none"></div>
     <div class="pagecontainer">{$content}</div>
    </div>
   </div>
   <div id="ggp_navwrap" class="sidebar-on">
    <div id="ggp_navhead">
     <ul><li class="nav">
      <a href="javascript:ggjs.clickSidebar()" class="icon" title="{lang('open')}/{lang('close')}"></a>
      <span class="open-nav" title="{lang('close')}" onclick="ggjs.clickSidebar();">&nbsp;</span>
     </li></ul>
    </div>
    <div id="ggp_nav">
     {include file='navigation.tpl'}
    </div>
   </div>
  </div>
  <!-- start footer -->
  <div id="ggp_footer">
    {include file='footer.tpl'}
  </div>
  <!-- end footer -->
  {$bottom_includes|default:''}
 </body>
</html>
