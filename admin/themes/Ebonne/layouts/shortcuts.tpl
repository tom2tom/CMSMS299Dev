{block name=shortcuts}
{strip}
{$my_alerts=$theme->get_my_alerts()}{$num_alerts=count($my_alerts)}
{if $num_alerts > 0}
  {if $num_alerts > 10}{$txt='&#2295'}{else}{$txt=$num_alerts}{/if}
  <span class="icon">
    <a id="alerts" title="{_la('notifications_to_handle2',$num_alerts)}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#notice" /></svg></a>
  <span class="bubble">{$txt}</span></span>
{/if}
{*
<span class="icon">
  {if isset($module_help_url)}
  <a href="{$module_help_url}" title="{_la('module_help')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#cmsmshelp" /></svg></a>
  {else}
  <a href="https://docs.cmsmadesimple.org/" rel="external" title="{_la('documentationtip')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#cmsmshelp" /></svg></a>
  {/if}
</span>
<span class="icon">
  {if isset($site_help_url)}
  <a href="{$site_help_url}" title="{_la('site_support')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#support" /></svg></a>
  {else}
  <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{_la('site_support')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#support" /></svg></a>
  {/if}
</span>
*}
{if !empty($marks)}
<span class="icon">
  <a href="listbookmarks.php?{$secureparam}" title="{_la('bookmarks')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#mybookmarks" /></svg></a>
</span>
{/if}
<span class="icon">
  {if isset($myaccount)}
   <a href="usersettings.php?{$secureparam}" title="{_la('title_mysettings')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#mysettings" /></svg></a>
{* TODO if effective UID != UID
  {else}
   {_la('signed_in',{$username})}
*}
  {/if}
</span>
<span class="icon">
  <a href="{root_url}/index.php" rel="external" target="_blank" title="{_la('viewsite')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#site" /></svg></a>
</span>
<span class="icon">
  <a href="logout.php?{$secureparam}"{if isset($is_sitedown)} class="outwarn"{/if} title="{_la('logout')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#logout" /></svg></a>
</span>
{/strip}
{/block}
