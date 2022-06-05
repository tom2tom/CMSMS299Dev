{* simple navigation
variables:
 aclass: is used to build a string containing class names given to the a tag if one is used
 liclass: is used to build a string containing class names given to the li tag
 nodes: flatlist of Navigator Node objects plus a stdClass for 'root'
 node: the current Node in nodes array
CSS:
 .menudepthN - N = 0,1,2,...
 .first_child
 .last_child
 .menuactive - the current/active item
 .menuparent - an ancestor of the the current/active item
 .parent - an item which has child(ren)
 .sectionheader - a sectionheader item
 .separator - a separator item (li and hr) 
*}
{* NOTE this function may only be defined once *}
{function Nav_menu}
<ul>
{strip}
  {foreach $items as $id}
    {* setup classes for the anchor and list item *}
    {$liclass='menudepth'|cat:$depth}
    {$aclass=''}

    {if count($items) > 1}
      {* the first child gets a special class *}
      {if $id@first}{$liclass=$liclass|cat:' first_child'}{/if}

      {* the last child gets a special class *}
      {if $id@last}{$liclass=$liclass|cat:' last_child'}{/if}
    {/if}

    {$node=$nodes.$id}
    {if $node->current}{* this is the current page *}
      {$liclass=$liclass|cat:' menuactive'}
      {$aclass=$aclass|cat:' menuactive'}
    {/if}

    {if $node->parent}{* this is an ancestor of the current page *}
      {$liclass=$liclass|cat:' menuactive menuparent'}
      {$aclass=$aclass|cat:' menuactive menuparent'}
    {/if}

    {if $node->children_exist}
      {$liclass=$liclass|cat:' parent'}
      {$aclass=$aclass|cat:' parent'}
    {/if}

    {* build the menu item *}{$type=$node->type}
    {if $type == 'sectionheader'}
      <li class="sectionheader{if $liclass} {$liclass}{/if}"><span>{$node->menutext}</span>
        {if !empty($node->children)}
          {Nav_menu items=$node->children depth=$depth+1}
        {/if}
      </li>
    {elseif $type == 'separator'}
      <li class="separator{if $liclass} {$liclass}{/if}"><hr class="separator" /></li>
    {else}
      {* regular item *}
      <li{if $liclass} class="{$liclass}"{/if}>
        {$t=$node->target}
        <a{if $aclass} class="{$aclass}"{/if} href="{$node->url}"{if $t} target="{$t}"{/if}><span>{$node->menutext}</span></a>
        {if !empty($node->children)}
          {Nav_menu items=$node->children depth=$depth+1}
        {/if}
      </li>
    {/if}
  {/foreach}{/strip}
</ul>
{/function}

{if isset($nodes[-1])}
{Nav_menu items=$nodes[-1]->children depth=0}
{/if}
