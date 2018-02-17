{strip}
{* TODO what are these <i></i> critters? *}
<div class="shortcuts">
  <ul class="cf">
    <li class="help">
      {if isset($module_help_url)}
      <a href="{$module_help_url}" title="{lang('module_help')}"><i class="fa fa-question-circle"></i></a>
      {else}
      <a href="https://docs.cmsmadesimple.org/" rel="external" title="{lang('documentation')}"><i class="fa fa-question-circle"></i></a>
      {/if}
    </li>
    {if isset($myaccount)}
    <li class="settings">
      <a href="myaccount.php?{$secureparam}" title="{lang('myaccount')}"><i class="fa fa-gear"></i></a>
    </li>
    {/if}
    {if isset($marks)}
    <li class="favorites open">
      <a href="listbookmarks.php?{$secureparam}" title="{lang('bookmarks')}"><i class="fa fa-star-o"></i></a>
    </li>
    {/if}
    <li class="view-site">
      <a href="{root_url}/index.php" rel="external" target="_blank" title="{lang('viewsite')}"><i class="fa fa-desktop"></i></a>
    </li>
    {$my_alerts=$theme->get_my_alerts()} {$num_alerts=count($my_alerts)} {if $num_alerts > 0} {if $num_alerts > 10}{$txt='&#2295'}{else}{$num=$num_alerts}{$txt="{$num}"}{/if}
    <li class="notifications">
      <a id="alerts" title="{lang('notifications_to_handle2',$num_alerts)}"><i class="fa fa-bell-o"></i><span class="bubble">{$txt}</span></a>
    </li>
    {/if}
    <li class="logout">
      <a href="logout.php?{$secureparam}" title="{lang('logout')}" {if isset($is_sitedown)}onclick="return confirm('{lang('maintenance_warning')|escape:'javascript'}')" {/if}><i class="fa fa-sign-out"></i></a>
    </li>
  </ul>
</div>
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
      <div class="alert-head ui-corner-all {if $one->priority == '_high'}ui-state-error red{elseif $one->priority == '_normal'}ui-state-highlight orange{else}ui-state-highlightblue{/if}">
       {$icon=$one->get_icon()} {if $icon}
        <img class="alert-icon ui-icon" alt="" src="{$icon}" title="{lang('remove_alert')}" />
       {else}
        <span class="alert-icon ui-icon {if $one->priority != '_low'}ui-icon-alert{else}ui-icon-info{/if}" title="{lang('remove_alert')}"></span>
       {/if}
        <span class="alert-title">{$one->get_title()|default:lang('alert')}</span>
        <span class="alert-remove ui-icon ui-icon-close" title="{lang('remove_alert')}"></span>
        <div class="alert-msg">{$one->get_message()}</div>
      </div>
    </li>
    {/foreach}
  </ul>
  <div id="alert-noalerts" class="information" style="display: none;">{lang('info_noalerts')}</div>
</div>
{/if}
<!-- alerts-end -->
{/strip}
