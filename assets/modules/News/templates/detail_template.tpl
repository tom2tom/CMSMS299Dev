{* set a canonical variable that can be used in the head section if process_whole_template is false in the config.php *}
{if isset($entry->canonical)}
  {* note this syntax ensures that the canonical variable is set into global scope *}
  {$canonical=$entry->canonical scope=global}
{/if}
{*
{if $entry->start}
  <div id="NewsPostDetailDate">
    {$entry->start|cms_date_format:'timed'}
  </div>
{/if}
*}
<h3 id="NewsPostDetailTitle">{$entry->title|cms_escape|default:'&lt;missing title&gt;'}</h3>

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
{* Note: for security purposes, because news-articles might come from untrusted sources, the content won't have been passed through Smarty *}
  {$entry->content}
</div>

{if $entry->extra}
  <div id="NewsPostDetailExtra">
    {$extra_label} {$entry->extra}
  </div>
{/if}

{if !empty($entry->image_url)}
  <div class="NewsDetailField">{* deprecated residual from earlier version *}
    <img id="NewsPostDetailImage" src="{$entry->image_url}" alt="{$entry->image_alt|default:'news-item image'}" />
  </div>
{/if}

{if $return_url}
  <div id="NewsPostDetailReturnLink">{$return_url}{if $category_name != ''} - {$category_link}{/if}</div>
{/if}
