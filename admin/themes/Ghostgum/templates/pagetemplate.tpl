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
  <div id="ggp_header">{*h container*}

     <div id="site-logo">{*h boxchild *}
      <a href="{root_url}/index.php" rel="external" target="_blank" title="{lang('viewsite')}">
      {if isset($sitelogo)}
       <img src="{$sitelogo}" alt="{sitename}" />
      {else}
       {sitename}
      {/if}
      </a>
      {if isset($sitelogo)}
       <span id="site-text">{lang('adminpaneltitle')}</span>
      {else}
       <span id="site-text">- {lang('adminpaneltitle')}</span>
      {/if}
     </div>

     <div id="system-logo">{*h boxchild *}
       <span id="system-text">{lang('power_by')}</span>
       <span id="cms-logo">
        <a href="http://www.cmsmadesimple.org" rel="external" title="{lang('cms_home')}"></a>
       </span>
     </div>

     <div id="shortcuts">{*h boxchild*}
     {include file='shortcuts.tpl'}{block name=shortcuts}{/block}
     </div>
  </div>{*end header*}

  <!-- start content -->
  <div id="ggp_container">{*v boxchild and h container*}

   <div id="ggp_navwrap" class="sidebar-on">
    <div id="ggp_nav">
     {include file='navigation.tpl'}{block name=navigation}{/block}
    </div>
   </div>

   <div id="ggp_contentwrap">
    <div id="ggp_content">
     <div id="ggp_contenthead">
       {strip}{if !empty($pageicon) || !empty($pagetitle)}<h1>
       {if !empty($pageicon)}<span class="headericon">{$pageicon}</span> {/if}{$pagetitle|default:''}
       </h1>{/if}
      {if !empty($module_help_url)} <span class="helptext"><a href="{$module_help_url}">{lang('module_help')}</a></span>{/if}
      {if !empty($pagetitle) && !empty($subtitle)}
       <div class="subheader">
        <h3 class="subtitle">{$subtitle}</h3>
       </div>{/if}
     </div>{* end contenthead *}
     {/strip}
     <div id="pagecontainer">{$content}</div>
    </div>
   </div>

  </div>{*end container*}

  <!-- start footer -->
  <div id="ggp_footer">{*h container*}
   {include file='footer.tpl'}{block name=footer}{/block}
  </div>{*end footer*}
  {include file='dialogs.tpl'}{block name=hiddendialogs}{/block}
  {$bottom_includes|default:''}
 </body>
</html>
