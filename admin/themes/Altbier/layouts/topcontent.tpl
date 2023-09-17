<div id="topcontent_wrap">
{strip}
{foreach $nodes as $node}
 {if $node.show_in_menu && $node.url}
 <div class="dashboard-box{if $node@index % 3 == 2} last cf{/if}">
  <nav class="dashboard-inner cf">
   <h3 class="dashboard-icon">
{*TODO no link to self *}
    <a href="{$node.url}"
    {if isset($node.target)} target="{$node.target}"{/if}
    {if !empty($node.selected)} class="selected"{/if}
    >{if !empty($node.iconclass)}<i class="{$node.iconclass}"></i> {elseif !empty($node.img)}{$node.img} {/if}{$node.title|adjust:'strip_tags'}
    </a>
   </h3>
   {if $node.description}
   <span class="description">{$node.description}</span>
   {/if}
   {if !empty($node.children)}
   <h4>{lang('subitems')}</h4>
   <ul class="subitems cf">
   {foreach $node.children as $one}
    <li><a href="{$one.url}"{if isset($one.target)} target="{$one.target}"{/if}
    {if isset($is_sitedown) && strncmp($one.url,'logout',6 == 0)} class="outwarn"{/if}
    {if !empty($one.description)} title="{$one.description|adjust:'strip_tags'}"{/if}
    >{$one.title|adjust:'strip_tags'}
    </a></li>
   {/foreach}
   </ul>
   {/if}
  </nav>
 </div>
 {/if}
{/foreach}
{/strip}
</div>
