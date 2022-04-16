{strip}
{if $items}
<nav aria-title="breadcrumbs" class="float-sm-right">
	<ol class="breadcrumb">{$t='home'|lang}
		<li class="breadcrumb-item"><a href="{$admin_url}/index.php?{$secureparam}" title="{$t}">{$t}</a></li>
		{foreach $items as $one}
			{if $one@last}
				{$additional_class=' active'}
				{$additional_params=' aria-current="page"'}
			{else}
				{$additional_class=''}
				{$additional_params=''}
			{/if}
			<li class="breadcrumb-item{$additional_class}"{$additional_params}>
			{$t=$one.title|strip_tags}
			{if !$one@last && !empty($one.url)}
				<a href="{$one.url}" title="{if !empty($one.description)}{$one.description|strip_tags}{else}{$t}{/if}">{$t}</a>
			{else}
				{$t}
			{/if}
			</li>
		{/foreach}
	</ol>
</nav>
{/if}
{/strip}
