{strip}
{function menu_branch}
{if $depth == 0}
<nav id="ab_menu" role="navigation">
 <ul id="ab_pagemenu">
{else}
 <ul>
{/if}
{foreach $nav as $item}
 {if $item.show_in_menu}
  <li class="nav
 {if !empty($item.children)} sub{/if}
 {if !isset($item.system) && (isset($item.module) || isset($item.firstmodule))} module{/if}
 {if !empty($item.selected) || (isset($smarty.get.section) && $smarty.get.section == $item.name|lower)} current{/if}">
 {if !empty($item.children)}
  <i class="nav-mark" aria-hidden="true"></i>{$cn="{$item.name|lower} icon"}
 {else}{$cn=''}
 {/if}
 {if isset($is_sitedown) && substr($item.url,0,6) == 'logout'}{$cn=$cn|cat:' outwarn'|trim}{/if}
 {$t=$item.title|strip_tags}
  <a href="{$item.url}"{if isset($item.target)} target="_blank"{/if}{if $cn} class="{$cn}"{/if} title="{if !empty($item.description)}{$item.description|strip_tags}{else}{$t}{/if}"
  >
 {if $depth > 0 && empty($item.children)}{$t}{/if}
  </a>
 {if !empty($item.children)}
  <span title="{['togglemenu', {$t}]|lang}">{$t}</span>
  {menu_branch nav=$item.children depth=$depth+1}
 {/if}
  </li>
 {/if}
{/foreach}
 </ul>
{if $depth == 0}</nav>{/if}
{/function}
{/strip}
{block name=navigation}
{menu_branch nav=$nav depth=0}
{/block}
