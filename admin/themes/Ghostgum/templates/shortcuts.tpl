{block name=shortcuts}
{strip}
{$my_alerts=$theme->get_my_alerts()}{$num_alerts=count($my_alerts)}
{if $num_alerts > 0}
  {if $num_alerts > 10}{$txt='&#2295'}{else}{$txt=$num_alerts}{/if}
  <span class="icon">
    <a id="alerts" title="{_la('notifications_to_handle2',$num_alerts)}"><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#notice"/></svg></a>
  <span class="bubble">{$txt}</span></span>
{/if}
<span class="icon">
  {if isset($module_help_url)}
  <a href="{$module_help_url}" title="{_la('module_help')}"><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#cmsmshelp"/></svg></a>
  {else}
  <a href="https://docs.cmsmadesimple.org/" rel="external" title="{_la('documentationtip')}"><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#cmsmshelp"/></svg></a>
  {/if}
</span>
<span class="icon">
  {if isset($site_help_url)}
  <a href="{$site_help_url}" title="{_la('site_support')}"><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#support"/></svg></a>
  {else}
  <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{_la('site_support')}"><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#support"/></svg></a>
  {/if}
</span>
{if !empty($marks)}
<span class="icon">
  <a href="listbookmarks.php?{$secureparam}" title="{_la('bookmarks')}"><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#mybookmarks"/></svg></a>
</span>
{/if}
<span class="icon">
  <a href="{root_url}/index.php" rel="external" target="_blank" title="{_la('viewsite')}"><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#site"/></svg></a>
</span>
<span class="icon">
  {if isset($myaccount)}
   <a href="useraccount.php?{$secureparam}" title="{_la('myaccount')} - {$username}"><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#myaccount"/></svg></a>
  {else}
   {_la('signed_in',{$username})}
  {/if}
</span>
<span class="icon">
{*TODO replace onclick handler*}
  <a href="logout.php?{$secureparam}" title="{_la('logout')}" {if isset($is_sitedown)}onclick="cms_confirm_linkclick(this,'{_la('maintenance_warning')|escape:'javascript'}');return false;"{/if}><svg><use xlink:href="themes/Ghostgum/images/navsprite.svg#logout"/></svg></a>
</span>
{/strip}
{/block}
