{* minimal navigation recurses via repeated file-inclusion
variables:
 aclass: is used to build a string containing class names given to the a tag if one is used
 liclass: is used to build a string containing class names given to the li tag
 nodes: flatlist of Navigator Node objects plus a stdClass for notional 'root'
 node: the current Node in nodes array
CSS:
 .currentpage - the active/current page
 .activeparent - the ancestors of the active/current page
 .sectionheader - section headers
 .separator - the ruler for separators
*}
{function Min_Menu}
<ul>
{strip}
  {foreach $items as $id}{$node=$nodes.$id}{$type=$node->type}
    {if $type == 'sectionheader'}
      <li class="sectionheader{if $node->parent} activeparent{/if}">
        {$node->menutext}
        {if !empty($node->children)}
          {Min_Menu items=$node->children depth=$depth+1}
        {/if}
      </li>
    {elseif $type == 'separator'}
      <li style="list-style-type:none"><hr class="separator"></li>
    {else}{* regular item, link etc *}
      {if $node->current}
        {$liclass='currentpage'}
        {$aclass='currentpage'}
      {elseif $node->parent}
        {$liclass='activeparent'}
        {$aclass='activeparent'}
      {else}
        {$liclass=''}
        {$aclass=''}
      {/if}
      <li{if $liclass} class="{$liclass}"{/if}>
        {$t=$node->target}
        <a{if $aclass} class="{$aclass}"{/if} href="{$node->url}"{if $t} target="{$t}"{/if}>{$node->menutext}</a>
        {if !empty($node->children)}
          {Min_Menu items=$node->children depth=$depth+1}
        {/if}
      </li>
    {/if}
  {/foreach}{/strip}
</ul>
{/function}

{if isset($nodes[-1])}
{Min_Menu items=$nodes[-1]->children depth=0}
{/if}

