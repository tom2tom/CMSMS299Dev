{block name=shortcuts}
{strip}
<div id="shortcuts">
    {$my_alerts=$theme->get_my_alerts()}{$num_alerts=count($my_alerts)}
    {if $num_alerts > 0}
      {if $num_alerts > 10}{$txt='&#2295'}{else}{$txt=$num_alerts}{/if}
      <span class="icon">
        <a id="alerts" title="{_la('notifications_to_handle2',$num_alerts)}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#notice"/></svg></a>
      </span><span class="bubble">{$txt}</span>
    {/if}
    <span class="icon">
      {if isset($module_help_url)}
      <a href="{$module_help_url}" title="{_la('module_help')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#cmsmshelp"/></svg></a>
      {else}
      <a href="https://docs.cmsmadesimple.org/" rel="external" title="{_la('documentationtip')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#cmsmshelp"/></svg></a>
      {/if}
    </span>
    <span class="icon">
      {if isset($site_help_url)}
      <a href="{$site_help_url}" title="{_la('site_support')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#support"/></svg></a>
      {else}
      <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{_la('site_support')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#support"/></svg></a>
      {/if}
    </span>
    {if isset($marks)}
    <span class="icon">
      <a href="listbookmarks.php?{$secureparam}" title="{_la('bookmarks')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#mybookmarks"/></svg></a>
    </span>
    {/if}
    <span class="icon">
      <a href="{root_url}/index.php" rel="external" target="_blank" title="{_la('viewsite')}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#site"/></svg></a>
    </span>
    <span class="icon">
      {if isset($myaccount)}
       <a href="useraccount.php?{$secureparam}" title="{_la('myaccount')} - {$username}"><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#myaccount"/></svg></a>
      {else}
       {_la('signed_in',{$username})}
      {/if}
    </span>
    <span class="icon">
{*TODO replace onclick handler*}
      <a href="logout.php?{$secureparam}" title="{_la('logout')}" {if isset($is_sitedown)}onclick="cms_confirm_linkclick(this,'{_la('maintenance_warning')|escape:'javascript'}');return false;"{/if}><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#logout"/></svg></a>
    </span>
</div>{*shortcuts*}
{/strip}
{/block}{*shortcuts*}

{block name=shortcutdialogs}
{strip}
{if isset($marks)}
<div class="dialog invisible" role="dialog" title="{_la('bookmarks')}">
  {if is_array($marks) && count($marks)}
  <h3>{_la('user_created')}</h3>
  <ul>
    {foreach $marks as $mark}
    <li>
      <a href="{$mark->url}"{if $mark->bookmark_id > 0} class="bookmark"{/if} title="{$mark->title}">{$mark->title}</a>
    </li>
    {/foreach}
  </ul>
  {/if}
  <h3>{_la('help')}</h3>
  <ul>
    <li><a rel="external" class="external" href="https://docs.cmsmadesimple.org" title="{_la('documentation')}">{_la('documentation')}</a></li>
    <li><a rel="external" class="external" href="https://forum.cmsmadesimple.org" title="{_la('forums')}">{_la('forums')}</a></li>
    <li><a rel="external" class="external" href="http://cmsmadesimple.org/main/support/IRC">{_la('irc')}</a></li>
  </ul>
</div>
{/if}
{if !empty($my_alerts)}
<!-- alerts go here -->
<div id="alert-dialog" class="alert-dialog" role="dialog" title="{_la('alerts')}" style="padding:0; display: none;">
    {foreach $my_alerts as $one}
    <div class="alert-box jqtoast {if $one->priority == '_high'}error{elseif $one->priority != '_low'}warn{else}info{/if}" data-alert-name="{$one->get_prefname()}">
        <div class="jqt-heading">{$one->get_title()|default:_la('alert')}
        <span class="jqt-close alert-remove" title="{_la('remove_alert')}"></span>
      </div>
        <span>{$one->get_message()}</span>
    </div>
    {/foreach}
</div>
{/if}
{*<div id="alert-noalerts" class="jqtoast info" style="display:none;">{_la('info_noalerts')}</div>*}
<!-- alerts-end -->
{/strip}
{/block}
