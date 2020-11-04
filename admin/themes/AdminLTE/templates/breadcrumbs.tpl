{if count($items)}
	<nav aria-label="breadcrumb float-sm-right">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="{$admin_url}/index.php?{$secureparam}" title="{'home'|lang}">{'home'|lang}</a></li>
		{$additional_class = ''}
		{$additional_params = ''}
		{foreach from=$items item='one' name='breadcrumb'}
			{if $smarty.foreach.breadcrumb.last}
				{$additional_class = 'active'}
				{$additional_params = 'aria-current="page'}
			{/if}
			<li class="breadcrumb-item {$additional_class}" {$additional_params}>
				{if !empty($one.url)}
			<a href="{$one.url}" title="{if !empty($one.description)}{$one.description}{else}{$one.title}{/if}">{$one.title}</a>
		{else}
			{$one.title}
		{/if}
			</li>
		{/foreach}
  </ol>
</nav>
{/if}