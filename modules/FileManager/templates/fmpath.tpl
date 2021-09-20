<h3>{_ld($_module,'currentpath')}
   <span class="pathselector">
   {foreach $path_parts as $part}
     {if !empty($part->url)}
       <a href="{$part->url}">{$part->name}</a>
     {else}
       {$part->name}
     {/if}
     {if !$part@last}<span class="ds"> {$sep} </span>{/if}
   {/foreach}
   </span>
</h3>
