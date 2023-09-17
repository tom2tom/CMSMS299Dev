{* still needs work *}
{$my_alerts = $theme->get_my_alerts()|default:[]}
{$num_alerts = count($my_alerts)|default:0}
{if $num_alerts > 0}
<li class="nav-item notifications">
	<a id="alerts"  class="nav-link">{$t=lang('notifications_to_handle2', $num_alerts)}
		<i class="fa fa-bell" aria-title="{$t}"></i>
		<span title="{$t}" class="badge badge-warning navbar-badge">{$num_alerts}</span>
	</a>
</li>
<!-- Alert Dialog -->
<div id="alert-dialog" class="alert-dialog" role="dialog" title="{lang('alerts')}" style="display:none">
	<ul>{$t=lang('remove_alert')}
		{foreach $my_alerts as $one}
			<li class="alert-box" data-alert-name="{$one->get_prefname()}">
				<div class="alert-head ui-corner-all {if $one->priority == '_high'}ui-state-error red{elseif $one->priority == '_normal'}ui-state-highlight orange{else}ui-state-highlight blue{/if}">
					{$icon = $one->get_icon()}
					{if $icon}
						<img class="alert-icon ui-icon" aria-hidden="true" src="{$icon}">
					{else}
						<span class="alert-icon ui-icon {if $one->priority != '_low'}ui-icon-alert{else}ui-icon-info{/if}"></span>
					{/if}
					<span class="alert-title">{$one->get_title()|default:{lang('alert')}}</span>
					<span class="alert-remove ui-icon ui-icon-close" title="{$t}" aria-title="{$t}"></span>
					<div class="alert-msg">{$one->get_message()}</div>
				</div>
			</li>
		{/foreach}
	</ul>
	<div id="alert-noalerts" class="information" style="display:none">{lang('info_noalerts')}</div>
</div>
{/if}
