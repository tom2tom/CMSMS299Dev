<!-- Notifications Dropdown Menu -->
{* still a lot of work needs to be done *}

{$my_alerts = $theme->get_my_alerts()|default:[]}
{$num_alerts = count($my_alerts)|default:''}

<li class="nav-item notifications">
	<a id="alerts"  class="nav-link">
		<i class="fa fa-bell"></i>
		{if $num_alerts}
			<span title="{_la('notifications_to_handle2', $num_alerts)}" class="badge badge-warning navbar-badge">{$num_alerts}</span>
		{else}
			<span title="{_la('notifications_to_handle2', $num_alerts)}"  class="badge navbar-badge">0</span>
		{/if}
	</a>
</li>

<!-- Alert Dialog -->
{if !empty($my_alerts)}
	<div id="alert-dialog" class="alert-dialog" role="dialog" title="{_la('alerts')}" style="display: none;">
		<ul>
			{foreach $my_alerts as $one}
				<li class="alert-box" data-alert-name="{$one->get_prefname()}">
					<div class="alert-head ui-corner-all {if $one->priority == '_high'}ui-state-error red{elseif $one->priority == '_normal'}ui-state-highlight orange{else}ui-state-highlightblue{/if}">
						{$icon = $one->get_icon()}
						{if $icon}
							<img class="alert-icon ui-icon" alt="" src="{$icon}" title="{_la('remove_alert')}"/>
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
		<div id="alert-noalerts" class="information" style="display: none;">{_la('info_noalerts')}</div>
	</div>
{/if}
