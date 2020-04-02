<!DOCTYPE html>
<html lang="{$lang_code|truncate:'2':''}" dir="{$lang_dir|default:'ltr'}">
 <head>
  <title>{strip}
  {if !empty($pagetitle)}{$thetitle=$pagetitle}{else}{$thetitle=''}{/if}
  {if $thetitle && $subtitle}{$thetitle="{$thetitle} - {$subtitle}"}{/if}
  {if $thetitle}{$thetitle="{$thetitle} - "}{/if}
  {if $thetitle}{$thetitle}{/if}{sitename}
  {/strip}</title>
  <meta charset="utf-8" />
  <meta name="generator" content="CMS Made Simple" />
  <meta name="robots" content="noindex, nofollow" />
  <meta name="viewport" content="initial-scale=1.0 maximum-scale=1.0 user-scalable=no" />
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
   <div>

     <div id="site-logo">
     <a href="{root_url}/index.php" rel="external" target="_blank" title="{lang('viewsite')}">
     {if isset($sitelogo)}
      <img src="{$sitelogo}" alt="{sitename}" />
     {else}
      {sitename}
     {/if}
     </a>
     {if isset($sitelogo)}
      <div class="site-text">{lang('adminpaneltitle')}</div>
     {else}
      <span class="site-text">- {lang('adminpaneltitle')}</span>
     {/if}
     </div>

     <div>
       <!-- logotext -->
       <span id="cms-text">{lang('power_by')}</span>
       <!-- logo -->
       <div id="cms-logo">
        <a href="http://www.cmsmadesimple.org" rel="external" title="{lang('cms_home')}">
        </a>
       </div>
     </div>
      <!-- shortcuts -->
      {include file='shortcuts.tpl'}{block name=shortcuts}{/block}

   </div>{*flex horz*}
  </div>{* end header *}

  <!-- start body -->
  <div id="ggp_container">
   <div id="ggp_navwrap" class="sidebar-on">
    <div id="ggp_navhead">
      <a href="#0" id="ggp_headlink" class="icon" title="{lang('open')}/{lang('close')}">
      <svg class="navshut"><use xlink:href="themes/Ghostgum/images/navsprite.svg#ltr"/></svg>
      <svg class="navopen"><use xlink:href="themes/Ghostgum/images/navsprite.svg#rtl"/></svg>
      </a>
      <span id="ggp_headzone" title="{lang('close')}">&nbsp;</span>
    </div>
    <div id="ggp_nav">
     {include file='navigation.tpl'}{block name=navigation}{/block}
    </div>
   </div>
   <div id="ggp_contentwrap">
     <div id="ggp_contenthead">
      {if !empty($pageicon) || !empty($pagetitle)}<h1>
        {if !empty($pageicon)}<span class="headericon">{$pageicon}</span> {/if}{$pagetitle|default:''}
      </h1>{/if}
      {if !empty($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{lang('module_help')}</a></span>{/if}
    {if !empty($pagetitle) && !empty($subtitle)}
      <div class="subheader">
       <h3 class="subtitle">{$subtitle}</h3>
      </div>
    {/if}
    </div>
    <div id="ggp_content">
     <div class="pagecontainer">{$content}</div>
    </div>
   </div>
  </div>{* end nested content container row *}

  <!-- start footer -->
  <div id="ggp_footer">
   {include file='footer.tpl'}{block name=footer}{/block}
  </div>{*-- end footer --*}

  {$bottom_includes|default:''}
 </body>
</html>
