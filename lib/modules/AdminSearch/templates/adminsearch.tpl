<ul>
{foreach $sections as $entry}
  <li class="section" id="sec_{$entry->id}">{$entry->lbl}&nbsp;(<span class="section_count">{$entry->count}</span>)
  <div class="section_children" style="display:none;">
   {if !empty($entry->desc)}<p>{$entry->desc}</p>{/if}
   <ul id="{$entry->id}">
   {foreach $entry->matches as $hit}<li>
   {if !empty($hit.url)}
    <a href="{$hit.url}" target="_blank" title="{$hit.description}">{$hit.title}</a>
   {else}
    {$hit.title}
   {/if}
   <br />{$hit.text}
   </li>
   {/foreach}
  </ul>
  </div>
  </li>
{/foreach}
</ul>
