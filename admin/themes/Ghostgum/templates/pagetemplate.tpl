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
  {$header_includes|default:''}
</head>

<body lang="{$lang|truncate:'2':''}" id="{$pagetitle|md5}" class="pg_{$pagealias}">
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
   <div id="ggp_contentwrap" class="column sidebar-on">
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
     {$content}
    </div>
   </div>
   <div id="ggp_navwrap" class="column sidebar-on">
    <div id="ggp_navhead">
     <span id="toggle-button" title="{lang('open')}/{lang('close')}"></span>
    </div>
    <div id="ggp_nav">
     {include file='navigation.tpl'}
    </div>
   </div>
  </div>
  <!-- start footer -->
  <div id="ggp_footer">
    {include file='footer.tpl'}
    {$footertext|default:''}
  </div>
  <!-- end footer -->
  {$bottom_includes|default:''}
</body>
</html>
