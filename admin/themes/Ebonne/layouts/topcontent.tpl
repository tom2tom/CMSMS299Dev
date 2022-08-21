{strip}
<div id="topcontent_wrap">
 {foreach $nodes as $node}
{if $node.show_in_menu && $node.url}
  <div class="dashboard-box">
    <nav class="dashboard-inner">
      <a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}{if !empty($node.selected)} class="selected"{/if} tabindex="-1"></a>
      <h3 class="dashboard-icon {$node.name}">
        <a href="{$node.url}"{if isset($node.target)} target="{$node.target}"{/if}{if !empty($node.selected)} class="selected"{/if}><svg><use xlink:href="themes/Ebonne/images/navsprite.svg#{$node.name|lower}" /></svg> {$node.title}</a>
      </h3>
      {if $node.description}
      <span class="description">{$node.description}</span>
      {/if}
      {if !empty($node.children)}
      <h4>{_la('subitems')}</h4>
      <ul class="subitems">
      {foreach $node.children as $one}
        <li><a href="{$one.url}"{if isset($one.target)} target="{$one.target}"{/if}
{*TODO replace onclick handler*}
        {if strncmp($one.url,'logout',6)==0 && isset($is_sitedown)} onclick="cms_confirm_linkclick(this,'{_la('maintenance_warning')|escape:'javascript'}');return false;"{/if}
        >{$one.title}
        </a></li>
      {/foreach}
      </ul>
      {/if}
    </nav>
  </div>
{/if}
 {/foreach}
</div>
{/strip}
