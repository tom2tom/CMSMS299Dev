{if isset($force_english)}
	<div class="pagewarn">{$mod->Lang('help_forceenglish')}</div>
{/if}

<div class="pageheader">
	{admin_icon module=$module_name}
	{if !empty($friendly_name)}
		{$friendly_name} <em>({$module_name})</em>
	{else}
		{$module_name}
	{/if}

	<span class="helptext">
		{if isset($englang_url)}
			<a href="{$englang_url}">{$englang_text}</a>&nbsp;
		{elseif isset($mylang_url)}
			<a href="{$mylang_url}">{$mylang_text}</a>&nbsp;
		{/if}
	</span>
</div>

{$help_page}

<p class="pageback" style="margin-top:2em;">
	<a href="{$back_url}" class="link_button icon back">{$mod->Lang('back')}</a>
</p>
