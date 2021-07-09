<div id="topmenu_wrap">
{foreach $nodes as $node}{if $node.show_in_menu}
  <div class="topmenu_section">
  {if strpos($node.url,'section')!==false}
  <h3 class="{$node.name}">
    {if $node.url}
     <a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}>{$node.title}</a>
    {else}
     {$node.title}
    {/if}
  </h3>
  {/if}
  {if !empty($node.children)}
    {if $node.description}<p class="topmenu_description">{$node.description}</p>{/if}
    {foreach $node.children as $one}{strip}
      {if !empty($one.children)}
       {* TODO recurse *}
      {else}
       <a {if isset($sitedown) && strpos($one.url,'logout')!==false}id="logoutitem" {/if}
        class="topmenu_item" href="{$one.url}"
        {if isset($one.target)} target="{$one.target}"{/if}
        >{$one.title}</a><br />
      {/if}
    {/strip}{/foreach}
  {/if}
  </div>
{/if}{/foreach}
</div>
