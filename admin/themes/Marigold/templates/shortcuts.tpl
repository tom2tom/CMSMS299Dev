{block name=shortcuts}
{strip}
<div class="shortcuts">
  <ul class="cf">
    <li class="help">
    {if isset($module_help_url)}
      <a href="{$module_help_url}" title="{'module_help'|lang}"><i class="fa fa-question-circle"></i></a>
    {else}
      <a href="https://docs.cmsmadesimple.org/" rel="external" title="{'documentation'|lang}"><i class="fa fa-question-circle"></i></a>
    {/if}
    </li>
    <li class="help">
    {if isset($site_help_url)}
      <a href="{$site_help_url}" title="{'site_support'|lang}"><i class="fa fa-hands-helping"></i></a>
    {else}
      <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{'site_support'|lang}"><i class="fa fa-hands-helping"></i></a>
    {/if}
    </li>
    {if isset($myaccount)}
    <li class="settings">
      <a href="useraccount.php?{$secureparam}" title="{'myaccount'|lang}"><i class="fa fa-user-circle-o"></i></a>
    </li>
    {/if}
    {if isset($marks)}
    <li class="favorites open">
      <a href="listbookmarks.php?{$secureparam}" title="{'bookmarks'|lang}"><i class="fa fa-bookmark"></i></a>
    </li>
    {/if}
    <li class="view-site">
      <a href="{root_url}/index.php" rel="external" target="_blank" title="{'viewsite'|lang}"><i class="mgfa-website"></i></a>
    </li>
    {$my_alerts=$theme->get_my_alerts()}
    {if !empty($my_alerts)}
      {$num_alerts=count($my_alerts)}
      {if $num_alerts > 0}
        {if $num_alerts > 10}{$txt='&#2295'}{else}{$txt=$num_alerts}{/if}
         <li class="notifications">
        <a id="alerts" title="{_ld('admin','notifications_to_handle2',$num_alerts)}">
          <i class="fa fa-bell"></i><span class="bubble">{$txt}</span></a>
        </li>
      {/if}
        {/if}
    <li class="logout">
{*TODO replace onclick handler*}
      <a href="logout.php?{$secureparam}" title="{'logout'|lang}" {if isset($is_sitedown)}onclick="return confirm('{'maintenance_warning'|lang|escape:'javascript'}')"{/if}><i class="fa fa-sign-out"></i></a>
    </li>
  </ul>
</div>
{if isset($marks)}
<div class="dialog invisible" role="dialog" title="{'bookmarks'|lang}">
  {if is_array($marks) && count($marks)}
    <h3>{'user_created'|lang}</h3>
    <ul>
    {foreach $marks as $mark}
     <li><a{if $mark->bookmark_id > 0} class="bookmark"{/if} href="{$mark->url}" title="{$mark->title}">{$mark->title}</a></li>
    {/foreach}
    </ul>
  {/if}
  <h3>{'help'|lang}</h3>
  <ul>
    <li><a rel="external" class="external" href="https://docs.cmsmadesimple.org" title="{'documentation'|lang}">{'documentation'|lang}</a></li>
    <li><a rel="external" class="external" href="https://forum.cmsmadesimple.org" title="{'forums'|lang}">{'forums'|lang}</a></li>
    <li><a rel="external" class="external" href="http://cmsmadesimple.org/main/support/IRC">{'irc'|lang}</a></li>
  </ul>
</div>
{/if}

{if !empty($my_alerts)}
<!-- alerts go here -->
<div id="alert-dialog" role="dialog" title="{_ld('admin','alerts')}" style="display: none;">
  <ul>
  {foreach $my_alerts as $one}
  <li class="alert-box" data-alert-name="{$one->get_prefname()}">
    <div class="alert-head ui-corner-all {if $one->priority == '_high'}ui-state-error red{elseif $one->priority == '_normal'}ui-state-highlight orange{else}ui-state-highlightblue{/if}">
     {$icon=$one->get_icon()}
     {if $icon}
     <img class="alert-icon ui-icon" alt="" src="{$icon}" title="{_ld('admin','remove_alert')}"/>
     {else}
     <span class="alert-icon ui-icon {if $one->priority != '_low'}ui-icon-alert{else}ui-icon-info{/if}" title="{_ld('admin','remove_alert')}"></span>
     {/if}
     <span class="alert-title">{$one->get_title()|default:_ld('admin','alert')}</span>
     <span class="alert-remove ui-icon ui-icon-close" title="{_ld('admin','remove_alert')}"></span>
     <div class="alert-msg">{$one->get_message()}</div>
  </div>
  </li>
  {/foreach}
  </ul>
  <div id="alert-noalerts" class="information" style="display: none;">{_ld('admin','info_noalerts')}</div>
</div>
{/if}
<!-- alerts-end -->
{/strip}
{/block}
