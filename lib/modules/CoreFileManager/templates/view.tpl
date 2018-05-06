{if !empty($acts)}
<div class="actions">
{foreach $acts as $one}{$act}{/foreach}
</div>
{/if}
{if !empty($about)}
<div class="vbox">
{foreach $about as $one}<div class="hbox"><div class="boxchild">{$one@key}:</div><div class="boxchild">{$one}</div></div>{/foreach}
</div>
{/if}
{if $ftype == 'archive'}
 {if $filenames}
  <code class="maxheight">
  {foreach $filenames as $fn}
   {strip}{if $fn.folder}
    <strong>{$fn.name}</strong>
   {else}
    {$fn.name} ({$fn.filesize})
   {/if}<br />{/strip}
  {/foreach}
  </code>
 {else}
  <p>Error while fetching archive info</p>
 {/if}
{elseif $ftype == 'image'}
  <p><img src="{$file_url}" alt=""{if !empty($setsize)} width="100"{/if} class="preview-img"></p>
{elseif $ftype == 'video'}
  <div class="preview-video"><video src="{$file_url}" width="640" height="360" controls preload="metadata"></video></div>
{elseif $ftype == 'audio'}
  <p><audio src="{$file_url}" controls preload="metadata"></audio></p>
{elseif $ftype == 'text'}
<div id="Editor" class="editor">{$content}</div>
{/if}
