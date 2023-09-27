{block name=shortcuts}
{strip}
<div class="shortcuts">
  <ul class="cf">
    {if isset($marksmenu)}
    <li class="favorites open">
      <span context-menu="Marks" title="{lang('bookmarks')}" style="cursor:pointer"><i class="fa fa-bookmark"></i></a>
    </li>
    {/if}
    {$my_alerts=$theme->get_my_alerts()}
    {if !empty($my_alerts)}
      {$num_alerts=count($my_alerts)}
      {if $num_alerts > 0}
        {if $num_alerts > 10}{$txt='&#2295'}{else}{$txt=$num_alerts}{/if}
         <li class="notifications">
        <a id="alerts" title="{_la('notifications_to_handle2',$num_alerts)}">
          <i class="fa fa-bell-o" aria-hidden="true"></i><span class="bubble">{$txt}</span></a>
        </li>
      {/if}
    {/if}
    <li class="help">
      <a href="javascript:MG.aboutToggle();" title="{lang('about')}"><i class="fa fa-info"></i></a>
    </li>
    <li class="help">
      <a href="https://forum.cmsmadesimple.org" rel="external" title="{lang('forums')}"><i class="fa fa-comments-o"></i></a>
    </li>
    <li class="help">
    {if isset($module_help_url)}
      <a href="{$module_help_url}" title="{lang('module_help')}"><i class="fa fa-question-circle"></i></a>
    {else}
      <a href="https://docs.cmsmadesimple.org/" rel="external" title="{lang('documentation')}"><i class="fa fa-question"></i></a>
    {/if}
    </li>
    <li class="help">
    {if isset($site_help_url)}
      <a href="{$site_help_url}" title="{lang('site_support')}"><i class="fa fa-life-ring"></i></a>
    {else}
      <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{lang('site_support')}"><i class="fa fa-life-ring"></i></a>
    {/if}
    </li>
    {if isset($myaccount)}
    <li class="settings">
      <a href="usersettings.php?{$secureparam}" title="{lang('title_mysettings')}"><i class="fa fa-sliders fa-rotate-90"></i></a>
    </li>
    {/if}
    <li class="view-site">
      <a href="{root_url}/index.php" rel="external" target="_blank" title="{lang('viewsite')}"><i class="cfi-mainsite"></i></a>
    </li>
    <li class="logout">
{*TODO replace onclick handler*}
      <a href="logout.php?{$secureparam}" title="{lang('logout')}"{if isset($is_sitedown)} onclick="return confirm('{lang("maintenance_warning")|escape:"javascript"}');"{/if}><i class="cfi-logout"></i></a>
    </li>
  </ul>
</div>
<a id="aboutinfo" style="display:none" href="javascript:MG.aboutToggle()">CMSMS {lang('version')} {cms_version} &ldquo;{cms_versionname}&rdquo;</a>
{if isset($marksmenu)}{$marksmenu}{/if}
{if !empty($my_alerts)}
<!-- alerts go here -->
<div id="alert-dialog" role="dialog" title="{_la('alerts')}" style="display:none">
  <ul>
  {foreach $my_alerts as $one}
  <li class="alert-box" data-alert-name="{$one->get_prefname()}">
    <div class="alert-head ui-corner-all {if $one->priority == '_high'}ui-state-error red{elseif $one->priority == '_normal'}ui-state-highlight orange{else}ui-state-highlightblue{/if}">
     {$icon=$one->get_icon()}
     {if $icon}
     <img class="alert-icon ui-icon" alt="" src="{$icon}" title="{_la('remove_alert')}">
     {else}
     <span class="alert-icon ui-icon {if $one->priority != '_low'}ui-icon-alert{else}ui-icon-info{/if}" title="{_la('remove_alert')}"></span>
     {/if}
     <span class="alert-title">{$one->get_title()|default:_la('alert')}</span>
     <span class="alert-remove ui-icon ui-icon-close" title="{_la('remove_alert')}"></span>
     <div class="alert-msg">{$one->get_message()}</div>
  </div>
  </li>
  {/foreach}
  </ul>
  <div id="alert-noalerts" class="information" style="display:none">{_la('info_noalerts')}</div>
</div>
{/if}
<!-- alerts-end -->
{/strip}
{/block}
