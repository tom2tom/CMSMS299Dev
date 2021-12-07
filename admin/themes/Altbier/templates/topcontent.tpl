{strip}
<div id="topcontent_wrap">
{foreach $nodes as $node}
 {if $node.show_in_menu && $node.url}
 <div class="dashboard-box{if $node@index % 3 == 2} last{/if}">
  <nav class="dashboard-inner cf">
   <a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}{if !empty($node.selected)} class="selected"{/if} tabindex="-1"></a>
   <h3 class="dashboard-icon {$node.name}">
    <a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}{if !empty($node.selected)} class="selected"{/if}>{$node.title}</a>
   </h3>
   {if $node.description}
   <span class="description">{$node.description}</span>
   {/if}
   {if !empty($node.children)}
   <h4>{'subitems'|lang}</h4>
   <ul class="subitems cf">
   {foreach $node.children as $one}
    <li><a href="{$one.url}"{if isset($one.target)} target="{$one.target}"{/if}
{*TODO replace onclick handler*}
    {if strncmp($one.url,'logout',6 == 0) && isset($is_sitedown)} onclick="return confirm('{'maintenance_warning'|lang|escape:'javascript'}')"{/if}
    >{$one.title}</a></li>
   {/foreach}
   </ul>
   {/if}
  </nav>
 </div>
 {if $node@index % 3 == 2}<div class="clear"></div>{/if}
 {/if}
{/foreach}
</div>
{/strip}
