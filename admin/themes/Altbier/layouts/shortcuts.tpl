{block name=shortcuts}
{strip}
<div class="col-auto px-0 shortcuts">
  <ul>
{*
    <li>
        <a href="https://www.cmsmadesimple.org" rel="external">
         <img  class="cmsnav" src="themes/assets/images/cmsms-icon.svg" onerror="this.onerror=null;this.src='themes/assets/images/cmsms-logo.png';" alt="CMS Made Simple" title="CMS Made Simple">
        </a>
    </li>
    <li class="help">
    {if isset($module_help_url)}
      <a href="{$module_help_url}" title="{lang('module_help')}"><i aria-hidden="true" class="fas fa-lg fa-question-circle"></i></a>
    {else}
      <a href="https://docs.cmsmadesimple.org/" rel="external" title="{lang('documentation')}"><i aria-hidden="true" class="fas fa-lg fa-question-circle"></i></a>
    {/if}
    </li>
    <li class="help">
    {if isset($site_help_url)}
      <a href="{$site_help_url}" title="{lang('site_support')}"><i aria-hidden="true" class="fas fa-lg fa-hands-helping"></i></a>
    {else}
      <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{lang('site_support')}"><i aria-hidden="true" class="fas fa-lg fa-hands-helping"></i></a>
    {/if}
    </li>
    {if isset($myaccount)}
    <li class="settings">
      <a href="useraccount.php?{$secureparam}" title="{lang('myaccount')}"><i aria-hidden="true" class="fas fa-lg fa-user-edit"></i></a>
    </li>
    {/if}
*}
    {$my_alerts=$theme->get_my_alerts()}
    {$num_alerts=count($my_alerts)}
    {if $num_alerts > 0}
      {if $num_alerts > 10}{$txt='&#2295'}{else}{$num=$num_alerts}{$txt="{$num}"}{/if}
      <li class="notifications">{$t=lang('notifications_to_handle2',$num_alerts)}
        <a id="alerts" tabindex="0" title="{$t}"><i aria-title="{$t}" class="fas fa-lg fa-bell"></i><span class="bubble">{$txt}</span></a>
      </li>
    {/if}
    <li class="settings">{$t=lang('title_mysettings')}
      <a href="usersettings.php?{$secureparam}" title="{$t}"><i aria-title="{$t}" class="fas fa-lg fa-sliders-h"></i></a>
    </li>
    {if isset($marks)}
    <li class="favorites open">{$t=lang('bookmarks')}
      <a href="listbookmarks.php?{$secureparam}" title="{$t}"><i aria-title="{$t}" class="fas fa-lg fa-bookmark"></i></a>
    </li>
    {/if}
    <li class="view-site">{$t=lang('viewsite')}
      <a href="{root_url}/index.php" rel="external" target="_blank" title="{$t}"><i aria-title="{$t}" class="fas fa-lg fa-laptop"></i></a>
    </li>
    <li class="logout">{$swap=$lang_dir|default:''}{$t=lang('logout')}
      <a href="logout.php?{$secureparam}"{if isset($is_sitedown)} class="outwarn"{/if} title="{$t}"><i aria-title="{$t}" class="fas fa-lg fa-door-open"></i></a>
    </li>
  </ul>
</div>
{if isset($marks)}
<div class="dialog invisible" role="dialog" title="{lang('bookmarks')}">
  {if is_array($marks) && count($marks)}
    <h3>{lang('user_created')}</h3>
    <ul>
    {foreach $marks as $mark}
     <li><a{if $mark->bookmark_id > 0} class="bookmark"{/if} href="{$mark->url}" title="{$mark->title}">{$mark->title}</a></li>
    {/foreach}
    </ul>
  {/if}
{*  <h3>{lang('help')}</h3>
  <ul>
    <li><a rel="external" class="external" href="https://docs.cmsmadesimple.org" title="{lang('documentation')}">{lang('documentation')}</a></li>
    <li><a rel="external" class="external" href="https://forum.cmsmadesimple.org" title="{lang('forums')}">{lang('forums')}</a></li>
    <li><a rel="external" class="external" href="https://cmsmadesimple.org/main/support/IRC">{lang('irc')}</a></li>
  </ul>
*}
</div>
{/if}
{if !empty($my_alerts)}
<!-- alerts go here -->
<div id="alert-dialog" class="alert-dialog" role="dialog" title="{lang('alerts')}" style="padding:0;display:none">
    {foreach $my_alerts as $one}
    <div class="alert-box jqtoast {if $one->priority == '_high'}error{elseif $one->priority != '_low'}warn{else}info{/if}" data-alert-name="{$one->get_prefname()}">
        <div class="jqt-heading">{$one->get_title()|default:{lang('alert')}}
        <span class="jqt-close alert-remove" title="{lang('remove_alert')}"></span>
        </div>
        <span>{$one->get_message()}</span>
    </div>
    {/foreach}
</div>
{/if}
{*<div id="alert-noalerts" class="jqtoast info" style="display:none">{lang('info_noalerts')}</div>*}
<!-- alerts-end -->
{/strip}
{/block}
