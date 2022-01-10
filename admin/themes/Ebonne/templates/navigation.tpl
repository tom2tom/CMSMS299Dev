{strip}
{function menu_branch}
{if $depth == 0}
 <ul id="ggp_menu">
{else}
 <ul>
{/if} {$prompt='maintenance_warning'|lang|escape:'javascript'}
{foreach $nav as $navitem}{$type=$navitem.name|lower}
  <li class="nav{if !isset($navitem.system) && (isset($navitem.module) || isset($navitem.firstmodule))} module{/if}{if !empty($navitem.selected) || (isset($smarty.get.section) && $smarty.get.section == $navitem.name|lower)} current{/if}">
{*TODO replace onclick handler*}
    <a href="{$navitem.url}" class="{$type} icon"{if isset($navitem.target)} target="_blank"{/if} title="{if !empty($navitem.description)}{$navitem.description|strip_tags}{else}{$navitem.title|strip_tags}{/if}" {if substr($navitem.url,0,6) == 'logout' && isset($is_sitedown)}onclick="cms_confirm_linkclick(this,'{$prompt}');return false;"{/if}>
    {if empty($navitem.icon)}<svg><use xlink:href="themes/Ebonne/images/navsprite.svg#{$type}"/></svg>
    {elseif $navitem.icon}<img src="{$navitem.icon}" />{/if}
    {if $depth > 0} {$navitem.title}{/if}
    </a>
    {if $depth == 0}
      {if !empty($navitem.children)}
        <span class="open-nav" title="{_la('togglemenu', {$navitem.title|strip_tags})}">{$navitem.title}</span>
      {else}
{*TODO replace onclick handler*}
        <a href="{$navitem.url}"{if isset($navitem.target)} target="_blank"{/if} class="no-nav" title="{if !empty($navitem.description)}{$navitem.description|strip_tags}{else}{$navitem.title|strip_tags}{/if}" {if substr($navitem.url,0,6) == 'logout' && isset($is_sitedown)}onclick="cms_confirm_linkclick(this,'{$prompt}');return false;"{/if}>
        {$navitem.title}
        </a>
      {/if}
    {/if}
    {if !empty($navitem.children)}
      {menu_branch nav=$navitem.children depth=$depth+1}
    {/if}
  </li>
{/foreach}
 </ul>
{/function}
{/strip}
{block name=navigation}
{menu_branch nav=$nav depth=0}
{/block}
