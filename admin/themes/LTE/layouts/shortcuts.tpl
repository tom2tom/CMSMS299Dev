<ul class="navbar-nav navbar-expand">
	<a id="aboutinfo" style="display:none" href="javascript:LT.aboutToggle()">CMSMS {lang('version')} {cms_version} &ldquo;{cms_versionname}&rdquo;</a>
	<li>{$t=lang('about')}
		<a href="javascript:LT.aboutToggle();" class="nav-link" title="{$t}"><i aria-title="{$t}" class="fa fa-info-circle"></i></a>
	</li>

	{include file='notifications.tpl'}

	{if isset($myaccount)}
	<li>{$t=lang('title_mysettings')}
		<a href="usersettings.php?{$secureparam}" class="nav-link" title="{$t}"><i aria-title="{$t}" class="fa fa-sliders-h"></i></a>
	</li>
	{/if}

	<li>
  {if isset($module_help_url)}{$t=lang('module_help')}
		<a href="{$module_help_url}" rel="external" class="nav-link" title="{$t}"><i aria-title="{$t}" class="fa fa-question-circle"></i></a>
  {else}{$t=lang('documentation')}
		<a href="https://docs.cmsmadesimple.org/" rel="external" class="nav-link" title="{$t}"><i aria-title="{$t}" class="fa fa-question-circle"></i></a>
  {/if}
	</li>
	<li>{$t=lang('site_support')}
		<a href="{if isset($site_help_url)}{$site_help_url}{else}https://www.cmsmadesimple.org/support/options/{/if}" rel="external" class="nav-link" title="{$t}"><i aria-title="{$t}" class="fa fa-life-ring"></i></a>
	</li>
{*
	<li>{$t=  }
		<a class="nav-link disabled">{ * <i aria-title="{$t}" class="fa fa-desktop"></i>* }|</a>
	</li>
*}
 {if isset($marks)}
	<li>{$t=lang('bookmarks')}
		<a class="nav-link" title="{$t}" data-scrollbar-theme="os-theme-light" data-widget="control-sidebar" data-controlsidebar-slide="true" href="javascript:void()"><i aria-title="{$t}" class="fa fa-bars"></i></a>
	</li>
 {/if}

	<li>{$t=lang('viewsite')}
		 <a href="{root_url}/index.php" rel="external" target="_blank" class="nav-link" title="{$t}"><i aria-title="{$t}" class="fa fa-desktop"></i></a>
	</li>

	<li>{$t=lang('logout')}
		<a href="logout.php?{$secureparam}" class="nav-link{if isset($is_sitedown)} outwarn{/if}" title="{$t}">
			<i aria-title="{$t}" class="fa fa-door-open"></i>
		</a>
	</li>

</ul>
{*
<div class="shortcuts">
	<ul class="cf">
		<li class="help">
			{if isset($module_help_url)}{$t=lang('module_help')}
				<a href="{$module_help_url}" title="{$t}"><i aria-title="{$t}" class="fa fa-question-circle"></i></a>
			{else}{$t=lang('documentation')}
				<a href="https://docs.cmsmadesimple.org/" rel="external" title="{$t}"><i aria-title="{$t}" class="fa fa-question-circle"></i></a>
			{/if}
		</li>

		{if isset($myaccount)}
			<li class="settings">{$t=lang('myaccount')}
				<a href="useraccount.php?{$secureparam}" title="{$t}"><i aria-title="{$t}" class="fa fa-gear"></i></a>
			</li>
		{/if}

		{if isset($marks)}
			<li class="favorites open">{$t=lang('bookmarks')}
				<a href="listbookmarks.php?{$secureparam}" title="{$t}"><i aria-title="{$t}" class="fa fa-star-o"></i></a>
			</li>
		{/if}

		<li class="view-site">{$t=lang('viewsite')}
			<a href="{root_url}/index.php" rel="external" target="_blank" title="{$t}"><i aria-title="{$t}" class="fa fa-desktop"></i></a>
		</li>

		{$my_alerts = $theme->get_my_alerts()}
		{if !empty($my_alerts)}
			{$num_alerts = count($my_alerts)}
			{if $num_alerts > 0}
				{if $num_alerts > 10}{$txt='&#2295'}{else}{$num=$num_alerts}{$txt="{$num}"}{/if}
 				<li class="notifications">{$t=lang('notifications_to_handle2',$num_alerts)}
					<a id="alerts" title="{$t}"><i aria-title="{$t}" class="fa fa-bell-o"></i><span class="bubble">{$txt}</span></a>
				</li>
			{/if}
		{/if}
		<li class="logout">{$t=lang('logout')}
			<a href="logout.php?{$secureparam}" title="{$t}"{if isset($is_sitedown)} onclick="return confirm('{lang("maintenance_warning")|escape:"javascript"}');"{/if}>
				<i aria-title="{$t}" class="fa fa-sign-out"></i>
			</a>
		</li>
	</ul>
</div>
*}
{*
{if isset($marks)}{$t=}
	<div class="dialog invisible" role="dialog" title="{lang('bookmarks')}">
		{if is_array($marks) && count($marks)}
			<h3>{lang('user_created')}</h3>
			<ul>
				{foreach $marks as $mark}
					<li><a{if $mark->bookmark_id > 0} class="bookmark"{/if} href="{$mark->url}" title="{$mark->title}">{$mark->title}</a></li>
				{/foreach}
			</ul>
			<hr>
		{/if}

		<h3>{lang('help')}</h3>

		<ul>
			<li><a href="https://docs.cmsmadesimple.org" rel="external" class="external" title="{lang('documentation')}">{lang('documentation')}</a></li>
			<li><a href="https://forum.cmsmadesimple.org"rel="external" class="external" title="{lang('forums')}">{lang('forums')}</a></li>
			<!--<li><a href="https://www.cmsmadesimple.org/support/documentation/chat/" rel="external" class="external">{lang('irc')}</a></li>-->
		</ul>
	</div>
{/if}
*}
{*
{if !empty($my_alerts)}
	<div id="alert-dialog" class="alert-dialog" role="dialog" title="{lang('alerts')}" style="display:none">
		<ul>
			{foreach $my_alerts as $one}
				<li class="alert-box" data-alert-name="{$one->get_prefname()}">
					<div class="alert-head ui-corner-all {if $one->priority == '_high'}ui-state-error red{elseif $one->priority == '_normal'}ui-state-highlight orange{else}ui-state-highlight blue{/if}">
						{$icon = $one->get_icon()}
						{if $icon}
							<img class="alert-icon ui-icon" alt="" src="{$icon}" title="{lang('remove_alert')}">
						{else}
							<span class="alert-icon ui-icon {if $one->priority != '_low'}ui-icon-alert{else}ui-icon-info{/if}" title="{lang('remove_alert')}"></span>
						{/if}
						<span class="alert-title">{$one->get_title()|default:{lang('alert')}}</span>
						<span class="alert-remove ui-icon ui-icon-close" title="{lang('remove_alert')}"></span>
						<div class="alert-msg">{$one->get_message()}</div>
					</div>
				</li>
			{/foreach}
		</ul>
		<div id="alert-noalerts" class="information" style="display:none">{lang('info_noalerts')}</div>
	</div>
{/if}
*}
