<ul>
{foreach $sections as $entry}
  <li class="section" id="sec_{$entry->id}">{$entry->lbl}&nbsp;(<span class="section_count">{$entry->count}</span>)
  <div class="section_children" style="display:none;">
   {if !empty($entry->desc)}<p>{$entry->desc}</p>{/if}
   <ul id="{$entry->id}">
   {foreach $entry->matches as $hit}<li>
   {if !empty($hit.url)}
    <a href="{$hit.url}" target="_blank"{if !empty($hit.description)} title="{$hit.description}"{/if}>{$hit.title}</a>
   {elseif !empty($hit.description)}
    <span title="{$hit.description}">{$hit.title}<span>
   {else}
    {$hit.title}
   {/if}
   <br />{$hit.text}{* might be newline-spararated multi-matches *}
   </li>
   {/foreach}
  </ul>
  </div>
  </li>
{/foreach}
</ul>
