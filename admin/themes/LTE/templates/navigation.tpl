{strip}
{$depth = $depth|default:0}
{if $depth==0}
	<nav>
		<ul class="nav nav-pills nav-sidebar flex-column{* nav-child-indent*}" data-widget="treeview" role="menu" data-accordion="false">
{/if}

	{foreach $nav as $navitem}{$li_active = ''}{$active = ''}{*$module = ''*}{* <pre>{$navitem|print_r:1}</pre> *}
		{if $navitem.show_in_menu}
{*			{if !isset($navitem.system) && (isset($navitem.module) || isset($navitem.firstmodule))}{$module = 'module'}{/if}*}
			{if !empty($navitem.selected) || (isset($smarty.get.section) && $smarty.get.section == $navitem.name|lower)}
				{if $depth>0}{$active = 'active'}{else}{$li_active = 'menu-open'}{/if}
			{/if}
			<li class="nav-item{if $depth==0} has-treeview{/if} {$li_active}">
				<a href="{$navitem.url}" class="nav-link {$active}"
					{if isset($navitem.target)} target="_blank"{/if} title="{if !empty($navitem.description)}{$navitem.description|strip_tags}{else}{$navitem.title|strip_tags}{/if}"
					{if substr($navitem.url,0,6) == 'logout' and isset($is_sitedown)} onclick="return confirm('{"maintenance_warning"|lang|escape:"javascript"}');"{/if}
				>
					<i class="nav-icon fas fa-03-{$navitem.name}"></i>&nbsp;{$navitem.title}
					{if $depth==0}<i class="right fas fa-angle-down"></i>{/if}
				</a>
				{if isset($navitem.children)}
					{if $depth==0}<ul class="nav nav-treeview">{/if}
						{include file=$smarty.template nav=$navitem.children depth=$depth+1}
					{if $depth==0}</ul>{/if}
				{/if}
			</li>
		{/if}
	{/foreach}

{if $depth==0}
		</ul>
	</nav>
{/if}
{/strip}
