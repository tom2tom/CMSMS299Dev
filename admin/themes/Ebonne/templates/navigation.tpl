{function menu_branch}
{if $depth == 0}
 <div id="burger">
  <svg><use xlink:href="themes/Ebonne/images/icons/system/sprite.svg#menu"/></svg>
 </div>
 <ul id="ggp_menu" class="noflash">{strip}
{else}
 <ul>{strip}
{/if}
{foreach $nav as $navitem}{$type=$navitem.name|lower}{$down=!empty($navitem.children)}{$linked=(!$down || $depth > 1)}
{$liclasses=''}
{if !isset($navitem.system) && (isset($navitem.module) || isset($navitem.firstmodule))}
{$liclasses=$liclasses|cat:'module'}{/if}
{if !empty($navitem.selected) || (isset($smarty.get.section) && $smarty.get.section == $navitem.name|lower)}
{$liclasses=$liclasses|cat:' current'}{/if}
{if $down}{$liclasses=$liclasses|cat:' descend'}{/if}
  <li{if $liclasses} class="{$liclasses|trim}"{/if} title="{if !empty($navitem.description)}{$navitem.description|strip_tags}{else}{$navitem.title|strip_tags}{/if}">
    {if $linked}
      <a href="{$navitem.url}" class="{$type} icon"{if isset($navitem.target)} target="_blank"{/if}>
    {/if}
    {if !isset($navitem.icon)}
    <svg><use xlink:href="themes/Ebonne/images/navsprite.svg#{$type}"/></svg>
    {elseif $navitem.icon}
    <img src="{$navitem.icon}" />
    {/if}
    {if $linked}
      </a>
      <a href="{$navitem.url}"{if isset($navitem.target)} target="_blank"{/if}>
    {/if}
    <span>{$navitem.title}</span>
    {if $linked}
      </a>
    {/if}
    {if $down}
      {menu_branch nav=$navitem.children depth=$depth+1}
    {/if}
  </li>
{/foreach}{/strip}
 </ul>
{/function}
{strip}
{block name=navigation}
{menu_branch nav=$nav depth=0}
{/block}
{/strip}
