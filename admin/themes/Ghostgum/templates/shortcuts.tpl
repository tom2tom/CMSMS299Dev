{block name=shortcuts}
{strip}
<div id="shortcuts">
  <ul class="cf">
    <li class="user">
      {if isset($myaccount)}
       <a href="myaccount.php?{$secureparam}" title="{lang('myaccount')} - {$username}"><span class="shortcuticon">&nbsp;</span></a></span>
      {else}
       {lang('signed_in',{$username})}
      {/if}
    </li>

    <li class="help">
      {if isset($module_help_url)}
      <a href="{$module_help_url}" title="{lang('module_help')}"><span class="shortcuticon">&nbsp</span></a>
      {else}
      <a href="https://docs.cmsmadesimple.org/" rel="external" title="{lang('documentationtip')}"><span class="shortcuticon">&nbsp;</span></a>
      {/if}
    </li>
    {if isset($marks)}
    <li class="favorites open">
      <a href="listbookmarks.php?{$secureparam}" title="{lang('bookmarks')}"><span class="shortcuticon">&nbsp;</span></a>
    </li>
    {/if}
    <li class="mainsite">
      <a href="{root_url}/index.php" rel="external" target="_blank" title="{lang('viewsite')}"><span class="shortcuticon">&nbsp;</span></a>
    </li>
    {$my_alerts=$theme->get_my_alerts()}{$num_alerts=count($my_alerts)}
    {if $num_alerts > 0}
      {if $num_alerts > 10}{$txt='&#2295'}{else}{$txt=$num_alerts}{/if}
      <li class="notice">
        <a id="alerts" title="{lang('notifications_to_handle2',$num_alerts)}"><span class="shortcuticon">&nbsp;</span><span class="bubble">{$txt}</span></a>
      </li>
    {/if}
    <li class="logout">
      <a href="logout.php?{$secureparam}" title="{lang('logout')}" {if isset($is_sitedown)}onclick="cms_confirm_linkclick(this,'{lang('maintenance_warning')|escape:'javascript'}');return false;"{/if}><span class="shortcuticon">&nbsp;</span></a>
    </li>
  </ul>
</div>{*shortcuts*}
{if isset($marks)}
<div class="dialog invisible" role="dialog" title="{lang('bookmarks')}">
  {if is_array($marks) && count($marks)}
  <h3>{lang('user_created')}</h3>
  <ul>
    {foreach $marks as $mark}
    <li>
      <a href="{$mark->url}"{if $mark->bookmark_id > 0} class="bookmark"{/if} title="{$mark->title}">{$mark->title}</a>
    </li>
    {/foreach}
  </ul>
  {/if}
  <h3>{lang('help')}</h3>
  <ul>
    <li><a rel="external" class="external" href="https://docs.cmsmadesimple.org" title="{lang('documentation')}">{lang('documentation')}</a></li>
    <li><a rel="external" class="external" href="https://forum.cmsmadesimple.org" title="{lang('forums')}">{lang('forums')}</a></li>
    <li><a rel="external" class="external" href="http://cmsmadesimple.org/main/support/IRC">{lang('irc')}</a></li>
  </ul>
</div>
{/if}
{if !empty($my_alerts)}
<!-- alerts go here -->
<div id="alert-dialog" class="alert-dialog" role="dialog" title="{lang('alerts')}" style="display: none;">
  <ul>
    {foreach $my_alerts as $one}
    <li class="alert-box" data-alert-name="{$one->get_prefname()}">
      <div class="alert-head {if $one->priority == '_high'}dialog-critical{elseif $one->priority != '_low'}dialog-warning{else}dialog-information{/if}">
       {$icon=$one->get_icon()} {if $icon}
        <img class="alert-icon" alt="" src="{$icon}" />
       {else}
        <span class="alert-icon {if $one->priority != '_low'}image-warning{else}image-info{/if}"></span>
       {/if}
        <span class="alert-title">{$one->get_title()|default:lang('alert')}</span>
        <span class="alert-remove image-close" title="{lang('remove_alert')}"></span>
        <div class="alert-msg">{$one->get_message()}</div>
      </div>
    </li>
    {/foreach}
  </ul>
  <div id="alert-noalerts" class="pageinfo" style="display:none;">{lang('info_noalerts')}</div>
</div>
{/if}
<!-- alerts-end -->
{/strip}
{/block}
