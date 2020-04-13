<!-- Start News Display Template -->
{* this section displays a tree of clickable News categories which have displayable items *}
{if $count > 0}
<ul class="list1">
{foreach $cats as $node}
 {if $node.depth > $node.prevdepth}
  {repeat string="<ul>" times=$node.depth-$node.prevdepth}
 {elseif $node.depth < $node.prevdepth}
  {repeat string="</li></ul>" times=$node.prevdepth-$node.depth}
</li>
 {elseif $node.index > 0}</li>
 {/if}
<li{if $node.index == 0} class="firstnewscat"{/if}>
 {if $node.count > 0}
  <a href="{$node.url}">{$node.news_category_name}</a>{else}<span>{$node.news_category_name} </span>
 {/if}
{/foreach}
{repeat string="</li></ul>" times=$node.depth-1}</li>
</ul>
{/if}
{strip}
{* this displays the category name if you're browsing by category *}
{if $category_name}
<h1>{$category_name}</h1>
{/if}

{* if you don't want category-browsing on your summary page, remove this line and everything above it and the following line *}
{/strip}
{if $pagecount > 1}
  <p>
{if $pagenumber > 1}
{$firstpage}&nbsp;{$prevpage}&nbsp;
{/if}
{$pagetext}&nbsp;{$pagenumber}&nbsp;{$oftext}&nbsp;{$pagecount}
{if $pagenumber < $pagecount}
&nbsp;{$nextpage}&nbsp;{$lastpage}
{/if}
</p>
{/if}
{foreach $items as $entry}
<div class="NewsSummary">
{*
{if $entry->start}
  <div class="NewsSummaryPostdate">
    {$entry->start|cms_date_format}
  </div>
{/if}
*}
<div class="NewsSummaryLink">
<a href="{$entry->moreurl}" title="{$entry->title|cms_escape:htmlall}">{$entry->title|cms_escape}</a>
</div>

<div class="NewsSummaryCategory">
  {$category_label} {$entry->category}
</div>

{if $entry->author}
  <div class="NewsSummaryAuthor">
    {$author_label} {$entry->author}
  </div>
{/if}

{if $entry->summary}
  {* note, for security purposes, because News articles might come from untrusted sources, we do not pass the summary or content through smarty in the default templates *}
  <div class="NewsSummarySummary">
    {$entry->summary}
  </div>

  <div class="NewsSummaryMorelink">
    [{$entry->morelink}]
  </div>

{else if $entry->content}
  {* note, for security purposes, because News articles might come from untrusted sources, we do not pass the summary or content through smarty in the default templates *}
  <div class="NewsSummaryContent">
    {$entry->content}
  </div>
{/if}

{if isset($entry->extra)}
  <div class="NewsSummaryExtra">
    {$entry->extra}
  {* {cms_module module='Uploads' mode='simpleurl' upload_id=$entry->extravalue} *}
  </div>
{/if}
</div>
{/foreach}
<!-- End News Display Template -->
