{* set a canonical variable that can be used in the head section if process_whole_template is false in the config.php *}
{if isset($entry->canonical)}
  {* note this syntax ensures that the canonical variable is set into global scope *}
  {$canonical=$entry->canonical scope=global}
{/if}
{*
{if $entry->start}
  <div id="NewsPostDetailDate">
    {$entry->start|cms_date_format}
  </div>
{/if}
*}
<h3 id="NewsPostDetailTitle">{$entry->title|cms_escape:'htmlall'}</h3>

<hr id="NewsPostDetailHorizRule" />

{if $entry->summary}
  <div id="NewsPostDetailSummary">
    <strong>
      {$entry->summary}
    </strong>
  </div>
{/if}

{if $entry->category}
  <div id="NewsPostDetailCategory">
    {$category_label} {$entry->category}
  </div>
{/if}
{if $entry->author}
  <div id="NewsPostDetailAuthor">
    {$author_label} {$entry->author}
  </div>
{/if}

<div id="NewsPostDetailContent">
    {* note, for security purposes we do not pass the content through smarty before displaying it.  This is incase your articles can come from untrusted sources. *}
  {$entry->content}
</div>

{if $entry->extra}
  <div id="NewsPostDetailExtra">
    {$extra_label} {$entry->extra}
  </div>
{/if}

{if $return_url != ""}
<div id="NewsPostDetailReturnLink">{$return_url}{if $category_name != ''} - {$category_link}{/if}</div>
{/if}
