{* cssmenu navigation
variables:
 aclass: is used to build a string containing class names given to the a tag if one is used
 liclass: is used to build a string containing class names given to the li tag
 nodes: flatlist of Navigator Node objects plus a stdClass for notional 'root'
 node: the current Node in nodes array
CSS:
 #menuwrapper - the containing div
 .cf - applied to the containing div
 #primary-nav - the outermost ul
 .unli - ul's other than the outermost one
 .first_child
 .last_child
 .menuactive
 .menuparent
 .sectionheader
 .menu_separator
*}
{* NOTE this function may only be defined once *}
 {function Nav_cssmenu}
 <ul{if $depth == 0} id="primary-nav"{else} class="unli"{/if}>
{strip}
{foreach $items as $id}
  {* setup classes for the anchor and list item *}
  {$liclass=[]}
  {$aclass=[]}

  {if count($items) > 1}
    {* the first child gets a special class *}
    {if $id@first}{$liclass[]='first_child'}{/if}

    {* the last child gets a special class *}
    {if $id@last}{$liclass[]='last_child'}{/if}
  {/if}

  {$node=$nodes.$id}
  {if $node->current}{* this is the current page *}
    {$liclass[]='menuactive'}
    {$aclass[]='menuactive'}
  {elseif $node->parent}{* this is an ancestor of the current page *}
    {$liclass[]='menuactive'}
    {$aclass[]='menuactive'}
  {/if}
  {if $node->has_children}{* this is a parent page *}
    {$liclass[]='menuparent'}
    {$aclass[]='menuparent'}
  {/if}

  {* build the menu item *}{$type=$node->type}
  {if $type == 'sectionheader'}
    <li{if $liclass} class="{implode(' ',$liclass)}"{/if}><a{if $aclass} class="{implode(' ',$aclass)}"{/if}><span class="sectionheader">{$node->menutext}</span></a>
      {if !empty($node->children)}
        {Nav_cssmenu items=$node->children depth=$depth+1}
      {/if}
    </li>
  {elseif $type == 'separator'}
    <li style="list-style-type: none;"><hr class="menu_separator"></li>
  {else}{* regular item *}
    <li{if $liclass} class="{implode(' ',$liclass)}"{/if}>
      {$t=$node->target}
      <a{if $aclass} class="{implode(' ',$aclass)}"{/if} href="{$node->url}"{if $t} target="{$t}"{/if}><span>{$node->menutext}</span></a>
      {if !empty($node->children)}
        {Nav_cssmenu items=$node->children depth=$depth+1}
      {/if}
    </li>
  {/if}
{/foreach}{/strip}
</ul>
{/function}

{if isset($nodes[-1])}
<div id="menuwrapper" class="cf">
  {Nav_cssmenu items=$nodes[-1]->children depth=0}
</div>
{/if}
