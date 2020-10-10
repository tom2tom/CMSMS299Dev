{block name=shortcuts}
{strip}
<div class="col-6 px-0 shortcuts">
  <ul>
    <li class="help">
    {if isset($module_help_url)}
      <a href="{$module_help_url}" title="{'module_help'|lang}"><i aria-hidden="true" class="fas fa-question-circle"></i></a>
    {else}
      <a href="https://docs.cmsmadesimple.org/" rel="external" title="{'documentation'|lang}"><i aria-hidden="true" class="fas fa-question-circle"></i></a>
    {/if}
    </li>
    <li class="help">
    {if isset($site_help_url)}
      <a href="{$site_help_url}" title="{'site_support'|lang}"><i aria-hidden="true" class="fas fa-hands-helping"></i></a>
    {else}
      <a href="https://www.cmsmadesimple.org/support/options/" rel="external" title="{'site_support'|lang}"><i aria-hidden="true" class="fas fa-hands-helping"></i></a>
    {/if}
    </li>
        {if isset($myaccount)}
    <li class="settings">
      <a href="useraccount.php?{$secureparam}" title="{'myaccount'|lang}"><i aria-hidden="true" class="fas fa-user-edit"></i></a>
    </li>
        {/if}
    {if isset($marks)}
    <li class="favorites open">
      <a href="listbookmarks.php?{$secureparam}" title="{'bookmarks'|lang}"><i aria-hidden="true" class="fas fa-bookmark"></i></a>
    </li>
    {/if}
    <li class="view-site">
      <a href="{root_url}/index.php" rel="external" target="_blank" title="{'viewsite'|lang}"><i aria-hidden="true" class="fas fa-desktop"></i></a>
    </li>
    {$my_alerts=$theme->get_my_alerts()}
    {$num_alerts=count($my_alerts)}
    {if $num_alerts > 0}
       {if $num_alerts > 10}{$txt='&#2295'}{else}{$num=$num_alerts}{$txt="{$num}"}{/if}
      <li class="notifications">
      <a id="alerts" tabindex="0" title="{lang('notifications_to_handle2',$num_alerts)}"><i aria-hidden="true" class="fas fa-bell"></i><span class="bubble">{$txt}</span></a>
       </li>
    {/if}
    <li class="logout">
{*TODO replace onclick handler*}
      <a href="logout.php?{$secureparam}" title="{'logout'|lang}" {if isset($is_sitedown)}onclick="return confirm('{'maintenance_warning'|lang|escape:'javascript'}')"{/if}><i aria-hidden="true" class="fas fa-sign-out-alt"></i></a>
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
<div id="alert-dialog" class="alert-dialog" role="dialog" title="{lang('alerts')}" style="padding:0; display: none;">
    {foreach $my_alerts as $one}
    <div class="alert-box jqtoast {if $one->priority == '_high'}error{elseif $one->priority != '_low'}warn{else}info{/if}" data-alert-name="{$one->get_prefname()}">
        <div class="jqt-heading">{$one->get_title()|default:lang('alert')}
        <span class="jqt-close alert-remove" title="{lang('remove_alert')}"></span>
        </div>
        <span>{$one->get_message()}</span>
    </div>
    {/foreach}
</div>
{/if}
{*<div id="alert-noalerts" class="jqtoast info" style="display:none;">{lang('info_noalerts')}</div>*}
<!-- alerts-end -->
{/strip}
{/block}
