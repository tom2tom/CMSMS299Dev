{$depth = $depth|default:0}
{if !$depth}
	<nav class="mt-2">
		<ul class="nav nav-pills nav-sidebar flex-column {* nav-child-indent*}" data-widget="treeview" role="menu" data-accordion="false">
{/if}

	{foreach from=$nav item='navitem' name='pos'}{$li_active = ''}{$active = ''}{$module = ''}{$has_children = ''} {* <pre>{$navitem|print_r:1}</pre> *}
		{if $navitem.show_in_menu}
			{if !isset($navitem.system) && (isset($navitem.module) || isset($navitem.firstmodule))}{$module = 'module'}{/if}
			{if !empty($navitem.selected) || (isset($smarty.get.section) && $smarty.get.section == $navitem.name|lower)}
				{if $depth}{$active = 'active'}{else}{$li_active = 'menu-open'}{/if}
			{/if}
			{if !$depth}{$has_children = 'has-treeview'}{/if}
			<li class="nav-item {$has_children} {$li_active}">
				<a href="{$navitem.url}" class="nav-link {$active}"
					{if isset($navitem.target)} target="_blank"{/if} title="{if !empty($navitem.description)}{$navitem.description|strip_tags}{else}{$navitem.title|strip_tags}{/if}"
					{if substr($navitem.url,0,6) == 'logout' and isset($is_sitedown)}onclick="return confirm('{'maintenance_warning'|lang|escape:'javascript'}')"{/if}
				>
				{if !$depth}<i class="nav-icon fas fa-03-{$navitem.name|lower}"></i>{else}<i class="fa fa-link nav-icon"></i>{/if}
					<p>
						{$navitem.title}
						{if !$depth}<i class="right fas fa-angle-left"></i>{/if}
					</p>
				</a>
				{if isset($navitem.children)}
					{if !$depth}<ul class="nav nav-treeview">{/if}
						{include file=$smarty.template nav=$navitem.children depth=$depth+1}
					{if !$depth}</ul>{/if}
				{/if}
			</li>
		{/if}
	{/foreach}
	
{if !$depth}
		</ul>
	</nav>
{/if}