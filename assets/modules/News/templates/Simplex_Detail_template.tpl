{* this is a sample detail template that works with the Simplex theme *}
{* set a canonical variable that can be used in the head section if process_whole_template is false in the config.php *}
{if isset($entry->canonical)}
  {$canonical=$entry->canonical scope=global}
  {$main_title=$entry->title scope=global}
{/if}

{* <h2>{$entry->title|cms_escape:htmlall}</h2> *}
{if $entry->summary}{$entry->summary}{/if}
{$entry->content}
{if $entry->extra}{$extra_label} {$entry->extra}{/if}
{if $return_url}
<br />
<span class='back'>&#8592; {$return_url}{if $category_name != ''} - {$category_link}{/if}</span>
{/if}

<footer class='news-meta'>
 {if $entry->startdate}{$entry->startdate|cms_date_format}{/if}
 {if $entry->category}<strong>{$category_label}</strong> {$entry->category}{/if}
 {if $entry->author}<strong>{$author_label}</strong> {$entry->author}{/if}
</footer>
