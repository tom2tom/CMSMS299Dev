{* default breadcrumbs template
variables:
 starttext: optional lead-in text
 rtl: optional right-to-left locale flag
 nodelist: flatlist of Node objects plus a stdClass for notional 'root'
 node: the current Node in nodelist array
CSS:
 #primary-nav - the containing ul
 .breadcrumb (div and span)
 .current
*}
<div class="breadcrumb">
{strip}
  {if isset($starttext)}{$starttext}:&nbsp;{/if}
  {$items=$nodelist[-1]->children}
  {foreach $items as $id}{$node=$nodelist.$id}
    <span class="breadcrumb{if $node->current} current{/if}">
      {if $id@last || $node->type == 'sectionheader'}
        {$node->menutext}
      {else}
        <a href="{$node->url}" title="{$node->name}">{$node->menutext}</a>
      {/if}
    </span>
    {if !$id@last}&nbsp;{if !empty($rtl)}&laquo;{else}&raquo;{/if} {/if}
  {/foreach}{/strip}
</div>
