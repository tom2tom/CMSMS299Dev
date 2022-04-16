{strip}
{function menu_branch}
{if $depth == 0}
<nav>
	<ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
{else}
	<ul class="nav nav-treeview">
{/if}
	{foreach $nav as $navitem}{$li_active = ''}{$active = ''}{*$module = ''*}{* <pre>{$navitem|print_r:1}</pre> *}
		{if $navitem.show_in_menu}
			{if !empty($navitem.selected) || (isset($smarty.get.section) && $smarty.get.section == $navitem.name|lower)}
				{if $depth > 0}{$active = ' active'}{else}{$li_active = 'menu-open'}{/if}
			{/if}{$t=$navitem.title|strip_tags}
			<li class="nav-item{if $depth==0} has-treeview{/if} {$li_active}">
				<a href="{$navitem.url}"{if isset($navitem.target)} target="_blank"{/if}
				class="nav-link{if isset($is_sitedown) && substr($item.url,0,6) == 'logout'} outwarn{/if}{$active}"
				title="{if !empty($navitem.description)}{$navitem.description|strip_tags}{else}{$t}{/if}"
				>
					<i class="nav-icon fas fa-03-{$navitem.name}" aria-hidden="true"></i><span class="nav-text">{$t}</span>
					{if !empty($navitem.children)}<i class="right fas fa-angle-down" aria-hidden="true"></i>{/if}
				</a>
				{if !empty($navitem.children)}
				{menu_branch nav=$navitem.children depth=$depth+1}
				{/if}
			</li>
		{/if}
	{/foreach}
	</ul>
{if $depth == 0}</nav>{/if}
{/function}
{/strip}
{menu_branch nav=$nav depth=0}
