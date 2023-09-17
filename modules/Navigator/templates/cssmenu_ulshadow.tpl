{* cssmenu_ulshadow navigation
variables:
 aclass: is used to build a string containing class names given to the a tag if one is used
 liclass: is used to build a string containing class names given to the li tag
 nodes: flatlist of Navigator Node objects plus a stdClass for notional 'root'
 node: the current Node in nodes array
CSS:
 #menuwrapper - the containing div
 .cf - applied to the containing div
 #primary-nav the outermost ul
 .unli - ul's other than the outermost one
 .first_child
 .last_child
 .menuactive - the current/active page
 .parent - an ancestor of the current/active page
 .menuparent - an item which has children
 .sectionheader
 .separator
 .once
*}
{* NOTE this function may only be defined once *}
{function cssmenu_ulshadow}
<ul{if $depth==0} id="primary-nav"{else} class="unli"{/if}>
{strip}
  {foreach $items as $id}
    {* setup classes for the anchor and list item *}
    {$liclass=''}{*$liclass=' depth'|cat:$depth*}
    {$aclass=''}

    {if count($items) > 1}
      {* the first child gets a special class *}
      {if $id@first}{$liclass=$liclass|cat:' first_child'}{/if}

      {* the last child gets a special class *}
      {if $id@last}{$liclass=$liclass|cat:' last_child'}{/if}
    {/if}

    {$node=nodes.$id}
    {if $node->current}{* this is the current page *}
      {$liclass=$liclass|cat:' menuactive'}
      {$aclass=$aclass|cat:' menuactive'}
    {elseif $node->parent}{* this is an ancestor of the current page *}
      {$liclass=$liclass|cat:' parent'}
      {$aclass=$aclass|cat:' parent'}
    {/if}
    {if isset($node->children)}
      {$liclass=$liclass|cat:' menuparent'}
      {$aclass=$aclass|cat:' menuparent'}
    {/if}

    {* build the menu item *}{$type=$node->type}
    {if $type == 'sectionheader'}
      <li class="sectionheader{if $liclass} {$liclass}{/if}"><span>{$node->menutext}</span>
        {if !empty($node->children)}
          {cssmenu_ulshadow items=$node->children depth=$depth+1}
        {/if}
      </li>
    {elseif $type == 'separator'}
      <li class="separator{if $liclass} {$liclass}{/if}"><hr class="separator"></li>
    {else}{* regular item *}
      <li{if $liclass} class="{$liclass}"{/if}>
        {$t=$node->target}
        <a{if $aclass} class="{$aclass}"{/if} href="{$node->url}"{if $t} target="{$t}"{/if}><span>{$node->menutext}</span></a>
        {if !empty($node->children)}
          {cssmenu_ulshadow items=$node->children depth=$depth+1}
        {/if}
      </li>
    {/if}
  {/foreach}
  {if $depth > 0}
    <li class="separator once" style="list-style-type:none">&nbsp;</li>
  {/if}
{/strip}
</ul>
{/function}

{if isset($nodes[-1])}
<div id="menuwrapper" class="cf">
  {cssmenu_ulshadow items=$nodes[-1]->children depth=0}
</div>
{/if}
