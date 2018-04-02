<div id="admin-alerts" class="notification" role="alert">
  <div class="box-shadow">&nbsp;</div>
  {strip}
  {$cnt=count($items)}{if $cnt}
  <a href="#" class="open" title="{lang('notifications')}">
    <span>
    {if $cnt > 1}{lang('notifications_to_handle'):$cnt}
    {else}{lang('notification_to_handle'):$cnt}
    {/if}
    </span>
  </a>
  <div class="alert-dialog dialog" role="alertdialog" title="{lang('alerts')}">
    <ul>
      {foreach $items as $one}
      <li class="alert-box" data-alert-name="{$one->get_prefname()}">
      <div class="alert-head {if $one->priority == '_high'}dialog-critical{elseif $one->priority != '_low'}dialog-warning{else}dialog-information{/if}">
       {$icon=$one->get_icon()} {if $icon}
          <img class="alert-icon" alt="" src="{$icon}" title="{lang('remove_alert')}" />
        {else}
          <span class="alert-icon {if $one->priority != '_low'}image-warning{else}image-info{/if}" title="{lang('remove_alert')}"></span>
        {/if}
          <span class="alert-title">{$one->title|default:lang('notice')}</span>
          <span class="alert-remove image-close" title="{lang('remove_alert')}"></span>
          <div class="alert-msg">{$one->get_message()}</div>
        </div>
      </li>
      {/foreach}
    </ul>
  </div>
  {/if}
{/strip}
</div>
