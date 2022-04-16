{strip}
{function menu_branch}
<ul>
{foreach $nav as $node}
  <li data-pid="{$node.id}">
    <span{if $node.id == $current} class="stack-menu__link--active"{/if}{if $node.title} title="{$node.title}"{/if}>{if $node.name}{$node.name}{else}{$node.menutext}{/if}</span>
    {if ($node.children)}
      {menu_branch nav=$node.children}
    {/if}
  </li>
{/foreach}
</ul>
{/function}
{/strip}
{menu_branch nav=$nodes}